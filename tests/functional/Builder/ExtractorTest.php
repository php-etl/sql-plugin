<?php declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL;

use Kiboko\Component\PHPUnitExtension\Assert;
use Kiboko\Plugin\SQL\Factory\Extractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExtractorTest extends TestCase
{
    use Assert\PipelineBuilderAssertTrait;
    use Assert\ExtractorBuilderAssertTrait;

    public function testValidatingConfiguration(): void
    {
        $extractor = new Extractor(new ExpressionLanguage());

        $this->assertTrue(
            $extractor->validate([
                [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        'identifier' => '@=3',
                    ],
                ],
            ]),
        );
    }

    public function testNormalizingConfiguration(): void
    {
        $extractor = new Extractor(new ExpressionLanguage());

        $this->assertEquals(
            [
                'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                'parameters' => [
                    'identifier' => new Expression('3'),
                ],
            ],
            $extractor->normalize([
                [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        'identifier' => '@=3',
                    ],
                ],
            ]),
        );
    }
}
