<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIService
{
    public function explainCompetency(string $studentName, array $ratings): string
    {
        $prompt = $this->buildPrompt($studentName, $ratings);

        $response = Http::timeout(15)->post('http://localhost:11434/api/generate', [
            'model' => 'phi3:mini',
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if ($response->failed()) {
            return $this->fallbackTemplate($studentName, $ratings);
        }

        return $response->json('response');
    }

    private function buildPrompt(string $studentName, array $ratings): string
    {
        $ratingsText = collect($ratings)
            ->map(fn ($level, $subStrand) => "$subStrand: $level")
            ->implode(', ');

        return "Explain in simple, encouraging language for a parent with no education "
            . "background what these CBC competency ratings mean for {$studentName}: "
            . "{$ratingsText}. Suggest one practical activity for any rating below ME.";
    }

    private function fallbackTemplate(string $studentName, array $ratings): string
    {
        return "{$studentName}'s results are ready to view on the dashboard. "
            . "An automatic summary could not be generated right now.";
    }
}
