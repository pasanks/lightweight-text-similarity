<?php

declare(strict_types=1);

namespace Pasanks\TextSimilarity\Tests;

use Pasanks\TextSimilarity\TextSimilarityService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class TextSimilarityServiceTest extends TestCase
{
    private TextSimilarityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TextSimilarityService();
    }

    #[Test]
    #[TestDox('Ensure cosine similarity is ~1.0 for identical text')]
    public function cosineSimilarityIdenticalText(): void
    {
        $left  = 'Laravel developer with AWS experience.';
        $right = 'Laravel developer with AWS experience.';

        $score = $this->service->cosineSimilarity($left, $right);

        $this->assertSame(1.0, round($score, 12));
    }

    #[Test]
    #[TestDox('Ensure cosine similarity normalizes case and punctuation')]
    public function cosineSimilarityNormalizesText(): void
    {
        $left  = 'Laravel, PHP; AWS!';
        $right = 'laravel php aws';

        $score = $this->service->cosineSimilarity($left, $right);

        $this->assertSame(1.0, round($score, 12));
    }

    #[Test]
    #[TestDox('Ensure cosine similarity returns 0.0 when no usable tokens exist')]
    public function cosineSimilarityReturnsZeroWhenNoTokens(): void
    {
        $left  = '@@@ !!! 12 34';
        $right = '$$$ 99';

        $score = $this->service->cosineSimilarity($left, $right);

        $this->assertSame(0.0, $score);
    }

    #[Test]
    #[TestDox('Ensure cosine similarity is 0.0 for disjoint tokens')]
    public function cosineSimilarityDisjointText(): void
    {
        $left  = 'laravel php mysql';
        $right = 'kubernetes terraform helm';

        $score = $this->service->cosineSimilarity($left, $right);

        $this->assertSame(0.0, $score);
    }

    #[Test]
    #[TestDox('Ensure evidence snippet returns null when query not found')]
    public function evidenceSnippetReturnsNull(): void
    {
        $text = 'Experienced backend engineer with Laravel and MySQL.';
        $query = 'Kubernetes';

        $snippet = $this->service->findEvidenceSnippet($text, $query, 40);

        $this->assertNull($snippet);
    }

    #[Test]
    #[TestDox('Ensure evidence snippet returns a newline-free snippet around a match')]
    public function evidenceSnippetReturnsSnippetAroundMatch(): void
    {
        $text = "I have worked with Laravel for 5 years.\n\nAlso comfortable with AWS SQS and SNS.";
        $query = 'AWS';

        $snippet = $this->service->findEvidenceSnippet($text, $query, 20);

        $this->assertNotNull($snippet);
        $this->assertStringContainsString('AWS', $snippet);
        $this->assertStringNotContainsString("\n", $snippet);
        $this->assertStringNotContainsString("\r", $snippet);
    }
}
