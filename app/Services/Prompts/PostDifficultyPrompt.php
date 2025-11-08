<?php

namespace App\Services\Prompts;

use App\Models\Post;

class PostDifficultyPrompt
{
    /**
     * Build the difficulty analysis prompt for a given post.
     */
    public static function build(Post $post): string
    {
        return <<<PROMPT
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
        {$post->content}
        PROMPT;
    }
}
