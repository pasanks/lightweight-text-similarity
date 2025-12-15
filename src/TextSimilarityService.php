<?php

declare(strict_types=1);

namespace Pasanks\TextSimilarity;

/**
 * Lightweight TF-only cosine similarity service.
 *
 * Why TF-only (no IDF)?
 * - This is intentionally simple for baseline ATS demos and deterministic scoring.
 * - It keeps behavior explainable without building a full search/indexing engine.
 */
final class TextSimilarityService
{
    /**
     * Compute cosine similarity between two texts using a term-frequency vector.
     */
    public function cosineSimilarity(string $leftText, string $rightText): float
    {
        $leftVector  = $this->vectorize($leftText);
        $rightVector = $this->vectorize($rightText);

        // If either side has no tokens, similarity is not meaningful.
        if ($leftVector === [] || $rightVector === []) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $leftMagnitudeSquared = 0.0;
        $rightMagnitudeSquared = 0.0;

        // Compute dot product + left magnitude in one pass.
        foreach ($leftVector as $term => $weight) {
            $leftMagnitudeSquared += $weight * $weight;

            if (isset($rightVector[$term])) {
                $dotProduct += $weight * $rightVector[$term];
            }
        }

        // Compute right magnitude.
        foreach ($rightVector as $weight) {
            $rightMagnitudeSquared += $weight * $weight;
        }

        // Guard against division by zero.
        if ($leftMagnitudeSquared <= 0.0 || $rightMagnitudeSquared <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($leftMagnitudeSquared) * sqrt($rightMagnitudeSquared));
    }

    /**
     * Convert text into a simple term-frequency (TF) vector.
     *
     * @return array<string, int> map(term => count)
     */
    private function vectorize(string $text): array
    {
        $tokens = $this->extractTokens($text);

        if ($tokens === []) {
            return [];
        }

        $termFrequencies = [];

        foreach ($tokens as $token) {
            $termFrequencies[$token] = ($termFrequencies[$token] ?? 0) + 1;
        }

        return $termFrequencies;
    }

    /**
     * Extract normalized tokens from text.
     *
     * - lowercases text
     * - removes punctuation (keeps letters and digits)
     * - drops tiny tokens (<3 chars)
     * - drops pure numbers
     *
     * @return string[]
     */
    private function extractTokens(string $text): array
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';

        $parts = preg_split('/\s+/', trim($normalized)) ?: [];

        $tokens = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) < 3) {
                continue;
            }

            if (preg_match('/^\d+$/', $part)) {
                continue;
            }

            $tokens[] = $part;
        }

        return $tokens;
    }

    /**
     * Find a snippet of text around a case-insensitive match.
     *
     * Useful for displaying evidence in UI.
     */
    public function findEvidenceSnippet(string $text, string $query, int $radius = 80): ?string
    {
        $matchPosition = mb_stripos($text, $query);

        if ($matchPosition === false) {
            return null;
        }

        $startIndex = max(0, $matchPosition - $radius);
        $snippetLength = mb_strlen($query) + ($radius * 2);

        $snippet = mb_substr($text, $startIndex, $snippetLength);

        // Collapse whitespace for cleaner UI output
        return trim(preg_replace('/\s+/', ' ', $snippet) ?? '');
    }
}
