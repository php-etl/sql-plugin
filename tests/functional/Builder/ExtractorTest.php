<?php

declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL;

use Kiboko\Component\PHPUnitExtension\Assert;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Plugin\SQL\Factory\Extractor;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExtractorTest extends TestCase
{
    use Assert\PipelineBuilderAssertTrait;
    use Assert\ExtractorBuilderAssertTrait;

    private ?vfsStreamDirectory $fs = null;

    protected function setUp(): void
    {
        $this->fs = vfsStream::setup();
    }

    protected function tearDown(): void
    {
        $this->fs = null;
    }

    protected function getBuilderCompilePath(): string
    {
        return $this->fs->url();
    }

    public function testValidatingConfiguration(): void
    {
        $extractor = new Extractor(new ExpressionLanguage());

        $this->assertTrue(
            $extractor->validate([
                [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        'identifier' => [
                            'value' => '@=3',
                        ],
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
                    'identifier' => [
                        'value' => new Expression('3'),
                    ],
                ],
            ],
            $extractor->normalize([
                [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        'identifier' => [
                            'value' => '@=3',
                        ],
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
