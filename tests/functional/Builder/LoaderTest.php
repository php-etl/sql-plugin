<?php declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL;

use Kiboko\Component\PHPUnitExtension\Assert;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Plugin\SQL\Factory\Loader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class LoaderTest extends TestCase
{
    use Assert\PipelineBuilderAssertTrait;
    use Assert\ExtractorBuilderAssertTrait;

    public function testValidatingConfiguration(): void
    {
        $extractor = new Loader(new ExpressionLanguage());

        $this->assertTrue(
            $extractor->validate([
                [
                    'query' => 'INSERT INTO foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        'identifier' => '@=3',
                        'value' => '@=36',
                    ],
                ],
            ]),
        );
    }

    public function testNormalizingConfiguration(): void
    {
        $extractor = new Loader(new ExpressionLanguage());

        $this->assertEquals(
            [
                'query' => 'INSERT INTO foo WHERE value IS NOT NULL AND id <= :identifier',
                'parameters' => [
                    'identifier' => new Expression('3'),
                    'value' => new Expression('36'),
                ],
            ],
            $extractor->normalize([
                [
                    'query' => 'INSERT INTO foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        'identifier' => '@=3',
                        'value' => '@=36',
                    ],
                ],
            ]),
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
