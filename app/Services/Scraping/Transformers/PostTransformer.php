<?php

namespace App\Services\Scraping\Transformers;

use App\Models\Post;
use App\Enums\PostType;
use App\Enums\PostStatus;
use Illuminate\Support\Str;
use App\Jobs\AnalyzePostDifficulty;
use App\Services\Scraping\Core\ScrapedData;
use App\Services\Scraping\Contracts\TransformableInterface;

class PostTransformer implements TransformableInterface
{
    public function transform(ScrapedData $data): array
    {
        $publishedAt = $this->parsePublishedAt($data->publishedAt) ?: now();
        
        return [
            'title' => $data->title,
            'slug' => $this->generateSlug($data->title, $data->url),
            'excerpt' => $data->excerpt ?: $this->generateExcerpt($data->content),
            'content' => $this->cleanContent($data->content),
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'featured_image' => $data->featuredImage,
            'meta' => array_merge($data->meta, [
                'original_url' => $data->url,
                'reading_time' => $data->readingTime,
            ]),
            'source_url' => $data->sourceUrl,
            'author_name' => $data->author,
            'author_avatar' => $data->authorAvatar,
            'author_email' => $data->authorEmail,
            'published_at' => $publishedAt,
            'tags' => $data->tags,
            'categories' => $data->categories,
        ];
    }

    public function createPost(ScrapedData $data): Post
    {
        $attributes = $this->transform($data);

        $post = Post::create($attributes);

        AnalyzePostDifficulty::dispatch($post);
        
        return $post;
    }

    public function updatePost(Post $post, ScrapedData $data): Post
    {
        $attributes = $this->transform($data);
        
        unset($attributes['slug']);
        
        $post->update($attributes);
        
        return $post->fresh();
    }

    protected function generateSlug(string $title, string $url): string
    {
        $slug = Str::slug($title);
        
        if (Post::where('slug', $slug)->exists()) {
            $urlSlug = $this->extractSlugFromUrl($url);
            if ($urlSlug && !Post::where('slug', $urlSlug)->exists()) {
                return $urlSlug;
            }
            
            $counter = 1;
            while (Post::where('slug', $slug . '-' . $counter)->exists()) {
                $counter++;
            }
            return $slug . '-' . $counter;
        }
        
        return $slug;
    }

    protected function extractSlugFromUrl(string $url): ?string
    {
        $parts = explode('/', parse_url($url, PHP_URL_PATH));
        $lastPart = end($parts);
        
        if ($lastPart && $lastPart !== '/') {
            return Str::slug($lastPart);
        }
        
        return null;
    }

    protected function generateExcerpt(string $content, int $length = 200): string
    {
        $plainText = strip_tags($content);
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        
        if (strlen($plainText) <= $length) {
            return $plainText;
        }
        
        return substr($plainText, 0, $length) . '...';
    }

    protected function cleanContent(string $content): string
    {
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        return $content;
    }

    protected function parsePublishedAt(?string $publishedAt): ?\Carbon\Carbon
    {
        if (!$publishedAt) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($publishedAt);
        } catch (\Exception $e) {
            return null;
        }
    }
}