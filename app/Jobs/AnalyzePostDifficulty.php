<?php

namespace App\Jobs;


use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

class AnalyzePostDifficulty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post;

    /**
     * Create a new job instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->post->difficulty) {
            return;
        }

        // $prompt = "Analyze the difficulty level of this post (beginner, intermediate, advanced):\n\n{$this->post->content}\n\nReturn only one word.";

        $prompt = <<<PROMPT
        You are an expert software content classifier.

        Your task is to:
        1. Identify the primary programming language or topic used in the post (e.g., PHP, JavaScript, Python, Laravel, HTML, CSS, etc.).
        2. Determine the technical difficulty level of the content based on its vocabulary, code complexity, and assumed reader knowledge.

        The possible difficulty levels are:
        - beginner
        - intermediate
        - advanced

        Return only the difficulty level as one word (beginner, intermediate, or advanced).
        If the content is not technical or the language is unclear, return "beginner".

        Post content:
        {$this->post->content}
        PROMPT;

        // Call OpenAI via Prism
        $prism = new Prism();
        $response = $prism->text()
            ->using(Provider::OpenAI, 'gpt-3.5-turbo')
            ->withSystemPrompt('You are a precise classifier of developer learning levels.')
            ->withPrompt($prompt)
            ->asText();

        $difficulty = strtolower(trim($response->text ?? ''));

        // Validate response
        if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'])) {
            $difficulty = 'beginner';
        }

        // Update post
        $this->post->update([
            'difficulty' => $difficulty,
        ]);
    }
}
