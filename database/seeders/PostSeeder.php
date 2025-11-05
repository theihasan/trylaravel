<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating posts...');

        // Create published blog posts
        Post::factory()
            ->count($this->command->ask('how many published blog posts would you like to create?', 25))
            ->blogPost()
            ->published()
            ->create();

        // Create draft blog posts
        Post::factory()
            ->count($this->command->ask('how many draft blog posts would you like to create?', 5))
            ->blogPost()
            ->draft()
            ->create();

        // // Create published videos
        // Post::factory()
        //     ->count(15)
        //     ->video()
        //     ->published()
        //     ->create();

        // // Create draft videos
        // Post::factory()
        //     ->count(3)
        //     ->video()
        //     ->draft()
        //     ->create();

        // // Create published podcasts
        // Post::factory()
        //     ->count(12)
        //     ->podcast()
        //     ->published()
        //     ->create();

        // // Create draft podcasts
        // Post::factory()
        //     ->count(2)
        //     ->podcast()
        //     ->draft()
        //     ->create();

        // // Create some popular content
        // Post::factory()
        //     ->count(5)
        //     ->published()
        //     ->popular()
        //     ->create();

        // Create featured Laravel content
        // $this->createFeaturedLaravelContent();

        $this->command->info('Posts created successfully!');
        $this->command->table(
            ['Type', 'Published', 'Draft', 'Total'],
            [
                ['Blog Posts', 25, 5, 30],
                ['Videos', 15, 3, 18],
                ['Podcasts', 12, 2, 14],
                ['Popular', 5, 0, 5],
                ['Total', 57, 10, 67],
            ]
        );
    }

    /**
     * Create some featured Laravel-specific content.
     */
    private function createFeaturedLaravelContent(): void
    {
        // Laravel tutorial posts
        Post::factory()->blogPost()->published()->create([
            'title' => 'Getting Started with Laravel 12: A Complete Guide',
            'excerpt' => 'Learn the fundamentals of Laravel 12 with this comprehensive tutorial covering installation, routing, and basic concepts.',
            'tags' => ['Laravel', 'PHP', 'Tutorial', 'Beginner'],
            'categories' => ['Tutorial', 'Web Development'],
            'views_count' => 8500,
            'likes_count' => 420,
        ]);

        Post::factory()->video()->published()->create([
            'title' => 'Building a REST API with Laravel Sanctum',
            'excerpt' => 'Step-by-step video tutorial on creating secure APIs using Laravel Sanctum for authentication.',
            'tags' => ['Laravel', 'API', 'Sanctum', 'Authentication'],
            'categories' => ['Tutorial', 'Backend'],
            'duration' => 2850, // 47:30
            'views_count' => 12000,
            'likes_count' => 890,
        ]);

        Post::factory()->podcast()->published()->create([
            'title' => 'The Future of Laravel with Taylor Otwell',
            'excerpt' => 'An in-depth discussion about Laravel\'s roadmap, new features, and the PHP ecosystem.',
            'tags' => ['Laravel', 'Interview', 'Taylor Otwell', 'Future'],
            'categories' => ['Interview', 'News'],
            'duration' => 4200, // 1:10:00
            'views_count' => 15600,
            'likes_count' => 1200,
            'meta' => [
                'episode_number' => 42,
                'season' => 3,
                'show_notes' => 'In this episode, we dive deep into Laravel\'s future plans, discuss upcoming features, and explore how the framework continues to evolve.',
            ],
        ]);

        // Advanced Laravel content
        Post::factory()->blogPost()->published()->create([
            'title' => 'Advanced Eloquent Techniques: Polymorphic Relationships',
            'excerpt' => 'Master advanced Eloquent relationships including polymorphic many-to-many and custom pivot models.',
            'tags' => ['Laravel', 'Eloquent', 'Database', 'Advanced'],
            'categories' => ['Tutorial', 'Database'],
            'views_count' => 6200,
            'likes_count' => 380,
        ]);

        Post::factory()->video()->published()->create([
            'title' => 'Laravel Performance Optimization: From 2s to 200ms',
            'excerpt' => 'Watch how we optimize a slow Laravel application using caching, database optimization, and profiling tools.',
            'tags' => ['Laravel', 'Performance', 'Optimization', 'Caching'],
            'categories' => ['Tutorial', 'Performance'],
            'duration' => 3600, // 1 hour
            'views_count' => 18500,
            'likes_count' => 1450,
        ]);
    }
}
