<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected ?AiSetting $settings = null;

    public function __construct()
    {
        $this->settings = AiSetting::getActive();
    }

    /**
     * Generate a proposal using AI.
     */
    public function generateProposal(array $project, array $portfolios = [], ?string $userName = null): string
    {
        if (!$this->settings || !$this->settings->api_key) {
            Log::warning('AiService: No active AI settings or API key');
            return $this->getDefaultProposal($project, $portfolios);
        }

        try {
            $prompt = $this->buildPrompt($project, $portfolios, $userName);
            
            return match ($this->settings->ai_model) {
                'openai' => $this->generateWithOpenAI($prompt),
                'anthropic' => $this->generateWithAnthropic($prompt),
                default => $this->generateWithOpenAI($prompt),
            };
        } catch (\Exception $e) {
            Log::error('AiService: Error generating proposal', [
                'error' => $e->getMessage(),
                'model' => $this->settings->ai_model ?? 'unknown',
            ]);
            return $this->getDefaultProposal($project, $portfolios);
        }
    }

    /**
     * Build the prompt for AI generation.
     */
    protected function buildPrompt(array $project, array $portfolios, ?string $userName): string
    {
        $basePrompt = $this->settings->prompt_template ?? $this->getDefaultPromptTemplate();
        
        // Replace placeholders
        $projectTitle = $project['title'] ?? 'this project';
        $projectDescription = $project['description'] ?? '';
        $projectBudget = $project['budget']['minimum'] ?? $project['budget']['maximum'] ?? 'negotiable';
        
        $portfolioSection = '';
        if (!empty($portfolios)) {
            $portfolioSection = "\n\nRelevant Portfolio Items:\n";
            foreach ($portfolios as $portfolio) {
                $portfolioSection .= "- {$portfolio->title}";
                if ($portfolio->url) {
                    $portfolioSection .= " ({$portfolio->url})";
                }
                if ($portfolio->description) {
                    $portfolioSection .= ": {$portfolio->description}";
                }
                if ($portfolio->technologies) {
                    $portfolioSection .= " [Technologies: " . implode(', ', $portfolio->technologies) . "]";
                }
                $portfolioSection .= "\n";
            }
        }

        $technologies = '';
        if (isset($project['jobs']) && is_array($project['jobs'])) {
            $techNames = array_map(function ($job) {
                return $job['name'] ?? '';
            }, $project['jobs']);
            $technologies = implode(', ', array_filter($techNames));
        }

        $prompt = str_replace([
            '{project_title}',
            '{project_description}',
            '{project_budget}',
            '{technologies}',
            '{portfolio_items}',
            '{user_name}',
        ], [
            $projectTitle,
            $projectDescription,
            is_numeric($projectBudget) ? '$' . number_format($projectBudget, 2) : $projectBudget,
            $technologies,
            $portfolioSection,
            $userName ?? 'I',
        ], $basePrompt);

        return $prompt;
    }

    /**
     * Generate proposal using OpenAI API.
     */
    protected function generateWithOpenAI(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->api_key,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional freelancer writing a compelling proposal for a project. Write a concise, professional, and engaging proposal that highlights relevant experience and skills.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';
        }

        throw new \Exception('OpenAI API error: ' . $response->body());
    }

    /**
     * Generate proposal using Anthropic API.
     */
    protected function generateWithAnthropic(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->settings->api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 500,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['content'][0]['text'] ?? '';
        }

        throw new \Exception('Anthropic API error: ' . $response->body());
    }

    /**
     * Get default proposal if AI generation fails.
     */
    protected function getDefaultProposal(array $project, array $portfolios): string
    {
        $projectTitle = $project['title'] ?? 'this project';
        $proposal = "I am interested in working on {$projectTitle}.\n\n";
        
        if (!empty($portfolios)) {
            $proposal .= "Relevant Portfolio:\n";
            foreach ($portfolios as $portfolio) {
                $proposal .= "- {$portfolio->title}";
                if ($portfolio->url) {
                    $proposal .= " ({$portfolio->url})";
                }
                $proposal .= "\n";
            }
        }

        $proposal .= "\nI have the skills and experience needed to complete this project successfully. I'm ready to start immediately and deliver high-quality results.";

        return $proposal;
    }

    /**
     * Get default prompt template.
     */
    protected function getDefaultPromptTemplate(): string
    {
        return "Write a professional proposal for the following project:\n\n" .
            "Project Title: {project_title}\n" .
            "Description: {project_description}\n" .
            "Budget: {project_budget}\n" .
            "Required Technologies: {technologies}\n" .
            "{portfolio_items}\n\n" .
            "Write a compelling proposal that:\n" .
            "1. Shows understanding of the project requirements\n" .
            "2. Highlights relevant experience and skills\n" .
            "3. Mentions relevant portfolio items\n" .
            "4. Demonstrates enthusiasm and professionalism\n" .
            "5. Is concise (2-3 paragraphs)\n\n" .
            "Sign the proposal as: {user_name}";
    }
}

