<?php

namespace App\Jobs;


use App\Models\Post;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use App\Enums\PostDifficultyLevel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Prompts\PostDifficultyPrompt;

class AnalyzePostDifficulty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $postId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = Post::find($this->postId);

        if ($post->difficulty) {
            return;
        }

        $prompt = PostDifficultyPrompt::build($post);

        // Call OpenAI via Prism
        $prism = new Prism();
        $response = $prism->text()
            ->using(Provider::OpenAI, 'gpt-3.5-turbo')
            ->withSystemPrompt('You are a precise classifier of developer learning levels.')
            ->withPrompt($prompt)
            ->asText();

        $difficultyEnum = PostDifficultyLevel::fromString($response->text ?? null);

        // Update post
        $post->update([
            'difficulty' => $difficultyEnum->value,
        ]);
    }
}
