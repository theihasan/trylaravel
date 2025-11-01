<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContentRankingService
{
    /**
     * Algorithm weights for content ranking
     */
    private const WEIGHTS = [
        'source_authority' => 0.35,
        'recency' => 0.3,
        'engagement' => 0.25,
        'source_diversity' => 0.1,
    ];

    /**
     * Source authority scores based on domain reputation
     * Scale: 1-10 (10 being the highest authority)
     */
    private const SOURCE_AUTHORITY = [
        // Official Laravel sources
        'laravel.com' => 10,
        'blog.laravel.com' => 10,

        // High authority Laravel community sites
        'laracasts.com' => 9,
        'laravel-news.com' => 9,
        'codecourse.com' => 9,
        'laraveldaily.com' => 9,

        // Well-known Laravel developers
        'freek.dev' => 8,
        'mattstauffer.com' => 8,
        'stitcher.io' => 8,
        'christoph-rumpel.com' => 8,
        'dyrynda.com.au' => 8,

        // Popular Laravel community blogs
        'tighten.co' => 7,
        'spatie.be' => 7,
        'beyondco.de' => 7,
        'nunomaduro.com' => 7,

        // Medium authority sources
        'dev.to' => 6,
        'medium.com' => 6,
        'hackernoon.com' => 6,

        // Lower authority but still relevant
        'blog.*' => 5, // Generic blog subdomains
        '*.dev' => 5,  // .dev domains

        // Default for unknown sources
        'default' => 3,
    ];

    /**
     * Get ranked posts with source diversity for anonymous users
     * This method uses pre-calculated ranking scores for efficiency
     * Ensures no source repeats within every 10-post window
     */
    public function getRankedPostsForAnonymousUser(int $limit = 50): EloquentCollection
    {
        // Get top posts by ranking score (much more than needed for diversification)
        $posts = Post::published()
            ->orderByDesc('ranking_score')
            ->orderByDesc('published_at') // Tie-breaker for same scores
            ->limit($limit * 3) // Get 3x more to allow for diversity filtering
            ->get();

        // return $this->applySourceDiversity($posts, $limit);

        $diverse = $this->applySourceDiversity($posts, $limit * 2);

        return $this->applyDifficultyDiversity($diverse, $limit);
    }

    /**
     * Apply source diversity algorithm - ensures fair visibility for all sources
     * Uses round-robin selection to prevent any source from dominating the feed
     */
    public function applySourceDiversity(EloquentCollection $posts, int $limit): EloquentCollection
    {
        // Group posts by source domain for fair distribution
        $postsBySource = $posts->groupBy(function ($post) {
            return $this->extractDomain($post->source_url ?: 'unknown');
        });

        // Convert to arrays with source tracking for round-robin
        $sourceQueues = [];
        $sourceUsageCount = [];

        foreach ($postsBySource as $source => $sourcePosts) {
            $sourceQueues[$source] = $sourcePosts->values();
            $sourceUsageCount[$source] = 0;
        }

        $result = new EloquentCollection;

        // Handle edge case where no posts are available
        if (empty($sourceQueues)) {
            return $result;
        }

        $roundNumber = 0;
        $maxRoundsPerSource = ceil($limit / count($sourceQueues));

        // Round-robin selection: give each source a fair turn
        while ($result->count() < $limit && ! empty($sourceQueues)) {
            $addedInThisRound = false;

            foreach ($sourceQueues as $source => $queue) {
                if ($result->count() >= $limit) {
                    break;
                }

                // Skip if this source has no more posts
                if ($queue->isEmpty()) {
                    continue;
                }

                // Limit how many posts per source in early rounds to ensure fairness
                $maxForThisRound = min(
                    $maxRoundsPerSource,
                    // In early rounds, be stricter to ensure all sources get representation
                    $roundNumber < 3 ? 1 : 2
                );

                if ($sourceUsageCount[$source] < ($roundNumber + 1) * $maxForThisRound) {
                    $post = $sourceQueues[$source]->shift();
                    $result->push($post);
                    $sourceUsageCount[$source]++;
                    $addedInThisRound = true;

                    // Remove empty queues
                    if ($sourceQueues[$source]->isEmpty()) {
                        unset($sourceQueues[$source]);
                    }
                }
            }

            // If no posts were added in this round, move to next round or break
            if (! $addedInThisRound) {
                $roundNumber++;

                // If we've done many rounds and still have empty spots, fill with remaining posts
                if ($roundNumber > 10) {
                    foreach ($sourceQueues as $source => $queue) {
                        foreach ($queue as $post) {
                            if ($result->count() >= $limit) {
                                break 2;
                            }
                            $result->push($post);
                        }
                    }
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Apply difficulty-based round-robin ordering to posts.
     * Ensures fair representation of beginner, intermediate, and advanced posts.
     */
    public function applyDifficultyDiversity(EloquentCollection $posts, int $limit): EloquentCollection
    {
        // Group posts by difficulty
        $byDifficulty = $posts->groupBy('difficulty');

        // Define round order (if any missing, skip gracefully)
        $order = ['beginner', 'intermediate', 'advanced'];

        // Initialize queues
        $queues = [];
        foreach ($order as $level) {
            $queues[$level] = $byDifficulty->get($level, collect())->values();
        }

        $result = new EloquentCollection;

        // Round-robin picking
        while ($result->count() < $limit && collect($queues)->flatten(1)->isNotEmpty()) {
            foreach ($order as $level) {
                if ($result->count() >= $limit) {
                    break;
                }

                if ($queues[$level]->isNotEmpty()) {
                    $result->push($queues[$level]->shift());
                }
            }
        }

        return $result;
    }


    /**
     * Legacy method for backward compatibility - now more efficient
     */
    public function rankForAnonymousUser(Collection $posts): Collection
    {
        if ($posts instanceof EloquentCollection) {
            // First sort by calculated score, then apply diversity
            $sortedPosts = $posts->sortByDesc(function (Post $post) {
                return $post->ranking_score ?: $this->calculateContentScore($post);
            });

            return $this->applySourceDiversity($sortedPosts, $posts->count());
        }

        // For regular collections, fall back to old method
        $rankedPosts = $posts->map(function (Post $post) {
            return [
                'post' => $post,
                'score' => $post->ranking_score ?: $this->calculateContentScore($post),
            ];
        })
            ->sortByDesc('score')
            ->pluck('post');

        return $rankedPosts;
    }

    /**
     * Calculate the overall content score for a post
     */
    public function calculateContentScore(Post $post): float
    {
        $sourceScore = $this->getSourceAuthorityScore($post);
        $recencyScore = $this->getRecencyScore($post);
        $engagementScore = $this->getEngagementScore($post);
        $diversityScore = $this->getSourceDiversityScore($post);

        return
            $sourceScore * self::WEIGHTS['source_authority'] +
            $recencyScore * self::WEIGHTS['recency'] +
            $engagementScore * self::WEIGHTS['engagement'] +
            $diversityScore * self::WEIGHTS['source_diversity'];
    }

    /**
     * Get source authority score based on the post's source URL
     */
    private function getSourceAuthorityScore(Post $post): float
    {
        if (empty($post->source_url)) {
            return self::SOURCE_AUTHORITY['default'];
        }

        $domain = $this->extractDomain($post->source_url);

        // Check for exact domain match
        if (isset(self::SOURCE_AUTHORITY[$domain])) {
            return (float) self::SOURCE_AUTHORITY[$domain];
        }

        // Check for pattern matches
        foreach (self::SOURCE_AUTHORITY as $pattern => $score) {
            if ($this->matchesDomainPattern($domain, $pattern)) {
                return (float) $score;
            }
        }

        return (float) self::SOURCE_AUTHORITY['default'];
    }

    /**
     * Calculate recency score with exponential decay
     */
    private function getRecencyScore(Post $post): float
    {
        if (! $post->published_at) {
            return 0.0;
        }

        $hoursOld = $post->published_at->diffInHours(now());

        // Exponential decay: newer content gets higher scores
        // Fresh content (< 24h) gets full score, then decays
        if ($hoursOld <= 24) {
            return 10.0; // Maximum score for very fresh content
        } elseif ($hoursOld <= 168) { // 1 week
            return 10.0 * exp(-($hoursOld - 24) / 168); // Slow decay in first week
        } else {
            return 3.0 * exp(-($hoursOld - 168) / (24 * 30)); // Faster decay after a week
        }
    }

    /**
     * Calculate engagement score based on views, likes, and interaction rates
     */
    private function getEngagementScore(Post $post): float
    {
        $hoursLive = max($post->published_at?->diffInHours(now()) ?? 1, 1);

        // Normalize engagement metrics by time
        $viewVelocity = $post->views_count / $hoursLive;
        $likeVelocity = $post->likes_count / $hoursLive;

        // Calculate engagement rate (likes per view)
        $engagementRate = $post->views_count > 0
            ? ($post->likes_count / $post->views_count) * 100
            : 0;

        // Weighted engagement score
        $rawScore = (
            $viewVelocity * 0.4 +
            $likeVelocity * 10 * 0.4 + // Weight likes more heavily
            $engagementRate * 0.2
        );

        // Scale to 0-10 range with logarithmic scaling for large numbers
        return min(10.0, log($rawScore + 1) * 2);
    }

    /**
     * Calculate source diversity score to penalize over-represented sources
     */
    private function getSourceDiversityScore(Post $post): float
    {
        if (empty($post->source_url)) {
            return 5.0; // Neutral score for unknown sources
        }

        $domain = $this->extractDomain($post->source_url);

        // Cache the source distribution for performance
        $sourceStats = Cache::remember('source_distribution_v3', 3600, function () {
            $totalPosts = Post::published()->count();
            if ($totalPosts === 0) {
                return [];
            }

            $sources = Post::published()
                ->selectRaw('source_url, COUNT(*) as count')
                ->whereNotNull('source_url')
                ->where('source_url', '!=', '')
                ->groupBy('source_url')
                ->get();

            // Group by domain since multiple URLs can belong to same domain
            $domainStats = [];
            foreach ($sources as $source) {
                $domain = $this->extractDomain($source->source_url);
                if (! isset($domainStats[$domain])) {
                    $domainStats[$domain] = ['count' => 0, 'percentage' => 0];
                }
                $domainStats[$domain]['count'] += $source->count;
            }

            // Calculate percentages after grouping
            foreach ($domainStats as $domain => $stats) {
                $domainStats[$domain]['percentage'] = ($stats['count'] / $totalPosts) * 100;
            }

            return $domainStats;
        });

        if (! isset($sourceStats[$domain])) {
            return 5.0; // Neutral score for new sources
        }

        $percentage = $sourceStats[$domain]['percentage'];

        // Balanced scoring to prevent both over and under-representation extremes
        // Target: 5-15% representation per source is ideal
        if ($percentage > 60) {
            return 1.0; // Heavy penalty for extremely dominant sources
        } elseif ($percentage > 40) {
            return 2.5; // Strong penalty for very over-represented sources
        } elseif ($percentage > 25) {
            return 4.0; // Moderate penalty for over-represented sources
        } elseif ($percentage > 15) {
            return 5.5; // Slight penalty for moderately over-represented
        } elseif ($percentage >= 5) {
            return 7.0; // Ideal range - neutral to slight boost
        } elseif ($percentage >= 2) {
            return 6.5; // Small boost for under-represented sources
        } else {
            return 5.5; // Very small sources get minimal boost to prevent gaming
        }
    }

    /**
     * Get detailed score breakdown for debugging/analytics
     */
    public function getScoreBreakdown(Post $post): array
    {
        $sourceScore = $this->getSourceAuthorityScore($post);
        $recencyScore = $this->getRecencyScore($post);
        $engagementScore = $this->getEngagementScore($post);
        $diversityScore = $this->getSourceDiversityScore($post);

        return [
            'source_authority' => [
                'score' => $sourceScore,
                'weight' => self::WEIGHTS['source_authority'],
                'weighted_score' => $sourceScore * self::WEIGHTS['source_authority'],
                'domain' => $this->extractDomain($post->source_url),
            ],
            'recency' => [
                'score' => $recencyScore,
                'weight' => self::WEIGHTS['recency'],
                'weighted_score' => $recencyScore * self::WEIGHTS['recency'],
                'hours_old' => $post->published_at?->diffInHours(now()),
            ],
            'engagement' => [
                'score' => $engagementScore,
                'weight' => self::WEIGHTS['engagement'],
                'weighted_score' => $engagementScore * self::WEIGHTS['engagement'],
                'views' => $post->views_count,
                'likes' => $post->likes_count,
                'engagement_rate' => $post->views_count > 0
                    ? round(($post->likes_count / $post->views_count) * 100, 2)
                    : 0,
            ],
            'source_diversity' => [
                'score' => $diversityScore,
                'weight' => self::WEIGHTS['source_diversity'],
                'weighted_score' => $diversityScore * self::WEIGHTS['source_diversity'],
                'domain' => $this->extractDomain($post->source_url),
            ],
            'total_score' => $this->calculateContentScore($post),
        ];
    }

    /**
     * Get trending posts with boost for high engagement velocity
     */
    public function getTrendingPosts(int $limit = 10, int $hours = 24): EloquentCollection
    {
        return Cache::remember("trending_posts_{$limit}_{$hours}", 300, function () use ($limit, $hours) {
            $posts = Post::published()
                ->where('published_at', '>=', now()->subHours($hours))
                ->get();

            return $this->rankForAnonymousUser($posts)
                ->filter(function (Post $post) {
                    // Only include posts with significant engagement
                    return $post->views_count >= 10 || $post->likes_count >= 2;
                })
                ->take($limit);
        });
    }

    /**
     * Get high-quality posts for hero content
     */
    public function getHeroContent(int $limit = 3): EloquentCollection
    {
        return Cache::remember("hero_content_{$limit}", 600, function () use ($limit) {
            $posts = Post::published()
                ->where('published_at', '>=', now()->subDays(7)) // Last week
                ->get();

            return $this->rankForAnonymousUser($posts)
                ->filter(function (Post $post) {
                    $score = $this->calculateContentScore($post);

                    return $score >= 7.0; // High-quality threshold
                })
                ->take($limit);
        });
    }

    /**
     * Extract domain from URL
     */
    private function extractDomain(?string $url): string
    {
        if (empty($url)) {
            return 'unknown';
        }

        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['host'])) {
            return 'unknown';
        }

        $host = $parsed['host'];

        // Remove www. prefix
        return preg_replace('/^www\./', '', strtolower($host));
    }

    /**
     * Check if domain matches a pattern (supports wildcards)
     */
    private function matchesDomainPattern(string $domain, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(
            ['*', '.'],
            ['.*', '\.'],
            $pattern
        );

        return preg_match("/^{$regex}$/", $domain) === 1;
    }

    /**
     * Get algorithm configuration for debugging/admin
     */
    public function getConfiguration(): array
    {
        return [
            'weights' => self::WEIGHTS,
            'source_authorities' => self::SOURCE_AUTHORITY,
            'version' => '1.0.0',
            'last_updated' => now()->toISOString(),
        ];
    }
}
