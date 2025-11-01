<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Http\Requests\ReportPostRequest;
use App\Models\Post;
use App\Models\Report;
use App\Services\ContentRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private ContentRankingService $rankingService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Use smart algorithm for anonymous users, user preferences for authenticated users
        $posts = $user ? $this->getPostsForAuthenticatedUser($user, $request) : $this->getPostsForAnonymousUser($request);

        return Inertia::render('Home/Index', [
            'posts' => Inertia::scroll(fn () => $posts->paginate(12)
                ->through(function (Post $post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'excerpt' => $post->excerpt,
                        'difficulty' => $post->difficulty,
                        'type' => [
                            'value' => $post->type->value,
                            'label' => $post->getTypeLabel(),
                            'icon' => $post->getTypeIcon(),
                            'color' => $post->getTypeColor(),
                        ],
                        'author' => [
                            'name' => $post->author_name,
                            'avatar' => $post->author_avatar,
                        ],
                        'published_at' => $post->published_at?->diffForHumans(),
                        'views_count' => $post->views_count,
                        'likes_count' => $post->likes_count,
                        'duration' => $post->formatted_duration,
                        'tags' => $post->tags,
                        'featured_image' => $post->featured_image,
                        'meta' => $post->meta,
                        'source_url' => $post->source_url,
                        // User interactions
                        'is_liked' => $post->isLikedBy(auth()->user()),
                        'is_bookmarked' => $post->isBookmarkedBy(auth()->user()),
                        'is_seen' => $post->isSeenBy(auth()->user()),
                        // Algorithm metadata for debugging (only in development)
                        'ranking_score' => app()->environment('local') ? $this->rankingService->calculateContentScore($post) : null,
                    ];
                })
            ),
            'stats' => Inertia::defer(fn () => [
                'total_posts' => Post::published()->count(),
                'posts_by_type' => [
                    'posts' => Post::posts()->published()->count(),
                    'videos' => Post::videos()->published()->count(),
                    'podcasts' => Post::podcasts()->published()->count(),
                ],
                'trending_tags' => $this->getTrendingTags(),
            ]),
            'userSources' => Auth::check() ? Auth::user()->sources()
                ->orderByDesc('is_active')
                ->orderByDesc('posts_count')
                ->limit(10)
                ->get(['id', 'name', 'url', 'favicon_url', 'is_active', 'posts_count']) : null,
            'filters' => [
                'types' => PostType::options(),
            ],
            // Hero content for anonymous users
            'heroContent' => ! $user ? $this->rankingService->getHeroContent(3)->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'type' => [
                        'value' => $post->type->value,
                        'label' => $post->getTypeLabel(),
                        'icon' => $post->getTypeIcon(),
                        'color' => $post->getTypeColor(),
                    ],
                    'author' => [
                        'name' => $post->author_name,
                        'avatar' => $post->author_avatar,
                    ],
                    'published_at' => $post->published_at?->diffForHumans(),
                    'views_count' => $post->views_count,
                    'likes_count' => $post->likes_count,
                    'featured_image' => $post->featured_image,
                    'tags' => $post->tags,
                ];
            }) : null,

            // Trending content for anonymous users
            'trendingContent' => ! $user ? $this->rankingService->getTrendingPosts(5)->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'type' => [
                        'value' => $post->type->value,
                        'label' => $post->getTypeLabel(),
                        'icon' => $post->getTypeIcon(),
                        'color' => $post->getTypeColor(),
                    ],
                    'author' => [
                        'name' => $post->author_name,
                        'avatar' => $post->author_avatar,
                    ],
                    'published_at' => $post->published_at?->diffForHumans(),
                    'views_count' => $post->views_count,
                    'likes_count' => $post->likes_count,
                    'featured_image' => $post->featured_image,
                ];
            }) : null,
        ]);
    }

    /**
     * Get posts for authenticated users (based on their sources)
     */
    private function getPostsForAuthenticatedUser($user, Request $request)
    {
        $query = Post::query()->published();

        // Filter by user's sources if they have any
        $userSources = $user->sources()->where('is_active', true)->pluck('url');
        if ($userSources->isNotEmpty()) {
            $query->whereIn('source_url', $userSources);
        }

        // Apply additional filters from request
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->latest('published_at');
    }

    /**
     * Get algorithmically ranked posts for anonymous users
     */
    private function getPostsForAnonymousUser(Request $request)
    {
        // Use efficient ranking with source diversity
        // Get more posts than we paginate to ensure diversity works well
        $rankedPosts = $this->rankingService->getRankedPostsForAnonymousUser(200);

        // Apply any additional filters to the ranked results
        if ($request->filled('type')) {
            $rankedPosts = $rankedPosts->where('type', $request->type);
        }

        $postIds = $rankedPosts->pluck('id')->toArray();

        if (empty($postIds)) {
            return Post::query()->whereRaw('1 = 0'); // Empty query
        }

        // Return as query builder for pagination, maintaining order
        // Use more efficient ordering for smaller result sets
        if (count($postIds) <= 100) {
            $orderCases = [];
            foreach ($postIds as $index => $id) {
                $orderCases[] = "WHEN id = {$id} THEN {$index}";
            }

            return Post::query()
                ->whereIn('id', $postIds)
                ->orderByRaw('CASE '.implode(' ', $orderCases).' END');
        } else {
            // For larger sets, use the database ordering
            return Post::query()
                ->whereIn('id', $postIds)
                ->orderByDesc('ranking_score')
                ->orderByDesc('published_at');
        }
    }

    public function show(Post $post): Response
    {
        if (! $post->is_published) {
            abort(404);
        }

        $post->incrementViews();

        $previousPost = Post::query()
            ->published()
            ->where('published_at', '<', $post->published_at)
            ->orderBy('published_at', 'desc')
            ->first(['id', 'title', 'slug', 'type']);

        $nextPost = Post::query()
            ->published()
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at', 'asc')
            ->first(['id', 'title', 'slug', 'type']);

        return Inertia::render('Posts/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'type' => [
                    'value' => $post->type->value,
                    'label' => $post->getTypeLabel(),
                    'icon' => $post->getTypeIcon(),
                    'color' => $post->getTypeColor(),
                ],
                'status' => [
                    'value' => $post->status->value,
                    'label' => $post->getStatusLabel(),
                    'color' => $post->getStatusColor(),
                ],
                'author' => [
                    'name' => $post->author_name,
                    'email' => $post->author_email,
                    'avatar' => $post->author_avatar,
                ],
                'published_at' => $post->published_at?->diffForHumans(),
                'formatted_published_at' => $post->published_at?->format('F j, Y'),
                'views_count' => $post->views_count,
                'likes_count' => $post->likes_count,
                'duration' => $post->formatted_duration,
                'tags' => $post->tags,
                'categories' => $post->categories,
                'featured_image' => $post->featured_image,
                'meta' => $post->meta,
                'source_url' => $post->source_url,
                'file_url' => $post->file_url,
                'file_size' => $post->formatted_file_size,
                'file_type' => $post->file_type,
                // User interactions
                'is_liked' => $post->isLikedBy(auth()->user()),
                'is_bookmarked' => $post->isBookmarkedBy(auth()->user()),
                'is_seen' => $post->isSeenBy(auth()->user()),
            ],
            'relatedPosts' => Post::query()
                ->published()
                ->where('id', '!=', $post->id)
                ->where('type', $post->type)
                ->latest('published_at')
                ->limit(3)
                ->get(['id', 'title', 'slug', 'excerpt', 'type', 'author_name', 'author_avatar', 'published_at', 'views_count', 'likes_count', 'duration', 'featured_image'])
                ->map(function (Post $relatedPost) {
                    return [
                        'id' => $relatedPost->id,
                        'title' => $relatedPost->title,
                        'slug' => $relatedPost->slug,
                        'excerpt' => $relatedPost->excerpt,
                        'type' => [
                            'value' => $relatedPost->type->value,
                            'label' => $relatedPost->getTypeLabel(),
                            'icon' => $relatedPost->getTypeIcon(),
                            'color' => $relatedPost->getTypeColor(),
                        ],
                        'author' => [
                            'name' => $relatedPost->author_name,
                            'avatar' => $relatedPost->author_avatar,
                        ],
                        'published_at' => $relatedPost->published_at?->diffForHumans(),
                        'views_count' => $relatedPost->views_count,
                        'likes_count' => $relatedPost->likes_count,
                        'duration' => $relatedPost->formatted_duration,
                        'featured_image' => $relatedPost->featured_image,
                    ];
                }),
            'navigation' => [
                'previous' => $previousPost ? [
                    'title' => $previousPost->title,
                    'slug' => $previousPost->slug,
                    'type' => [
                        'label' => $previousPost->getTypeLabel(),
                        'icon' => $previousPost->getTypeIcon(),
                    ],
                ] : null,
                'next' => $nextPost ? [
                    'title' => $nextPost->title,
                    'slug' => $nextPost->slug,
                    'type' => [
                        'label' => $nextPost->getTypeLabel(),
                        'icon' => $nextPost->getTypeIcon(),
                    ],
                ] : null,
            ],
        ]);
    }

    private function getTrendingTags(): array
    {
        return cache()->remember('trending_tags', 3600, function () {
            $allTags = Post::published()
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->countBy()
                ->sortDesc()
                ->take(10)
                ->map(fn ($count, $tag) => [
                    'name' => $tag,
                    'count' => $count,
                ])
                ->values()
                ->toArray();

            return $allTags;
        });
    }

    public function toggleLike(Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $isLiked = $post->toggleLike($user);

        return response()->json([
            'is_liked' => $isLiked,
            'likes_count' => $post->fresh()->likes_count,
        ]);
    }

    public function toggleBookmark(Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $isBookmarked = $post->toggleBookmark($user);

        return response()->json([
            'is_bookmarked' => $isBookmarked,
        ]);
    }

    public function markAsSeen(Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $isSeen = $post->markAsSeen($user);

        return response()->json([
            'is_seen' => $isSeen,
        ]);
    }

    public function markAsUnseen(Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $post->markAsUnseen($user);

        return response()->json([
            'is_seen' => false,
        ]);
    }

    public function reportPost(ReportPostRequest $request, Post $post): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $existingReport = Report::query()
            ->where([
                ['user_id', $user->id],
                ['post_id', $post->id],
                ['type', $request->type],
            ])
            ->exists();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this post for this reason.',
            ], 422);
        }

        Report::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        \Log::info('Post reported', [
            'post_id' => $post->id,
            'post_title' => $post->title,
            'reporter_id' => $user->id,
            'reporter_email' => $user->email,
            'report_type' => $request->type,
            'description' => $request->description,
            'reported_at' => now(),
        ]);

        return response()->json([
            'message' => 'Report submitted successfully. Thank you for helping keep our community safe.',
        ]);
    }
}
