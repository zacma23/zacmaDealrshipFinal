<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function getProviderName(): string;

    /**
     * Generate content from prompt
     *
     * @param string $prompt
     * @param array $options (model, system_instruction, max_tokens, temperature)
     * @return array ['success' => bool, 'text' => string, 'tokens' => int, 'raw' => array]
     */
    public function generateText(string $prompt, array $options = []): array;
}
