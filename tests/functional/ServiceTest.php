<?php declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL;

use Kiboko\Component\PHPUnitExtension\Assert;
use Kiboko\Plugin\SQL\Service;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Vfs\FileSystem;

final class ServiceTest extends TestCase
{
    use Assert\ExtractorBuilderAssertTrait;
    use Assert\TransformerBuilderAssertTrait;
    use Assert\LoaderBuilderAssertTrait;

    private ?FileSystem $vfs = null;

    protected function setUp(): void
    {
        $this->vfs = FileSystem::factory();
        $this->vfs->mount();
    }

    protected function tearDown(): void
    {
        if ($this->vfs !== null) {
            $this->vfs->unmount();
        }
    }

    public function testValidatingExtractorConfiguration(): void
    {
        $service = new Service();

        $this->assertEquals(
            [
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                        'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'extractor' => [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        [
                            'key' => 'identifier',
                            'value' => new Expression('3'),
                        ],
                    ]
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ],
            $service->normalize([
                [
                    'expression_language' => [],
                    'before' => [
                        'queries' => [
                            'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                            'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                            'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                        ],
                    ],
                    'after' => [
                        'queries' => [
                            'DROP TABLE foo',
                        ],
                    ],
                    'extractor' => [
                        'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                        'parameters' => [
                            [
                                'key' => 'identifier',
                                'value' => new Expression('3'),
                            ]
                        ]
                    ],
                    'connection' => [
                        'dsn' => 'sqlite::memory:',
                    ],
                ],
            ]),
        );
    }

    public function testValidatingLookupConfiguration(): void
    {
        $service = new Service();

        $this->assertEquals(
            [
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                        'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'lookup' => [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        [
                            'key' => 'identifier',
                            'value' => new Expression('3'),
                        ],
                    ],
                    'merge' => [
                        'map' => [
                            [
                                'field' => '[foo]',
                                'copy' => '[foo]',
                            ]
                        ],
                    ],
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ],
            $service->normalize([
                [
                    'expression_language' => [],
                    'before' => [
                        'queries' => [
                            'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                            'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                            'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                        ],
                    ],
                    'after' => [
                        'queries' => [
                            'DROP TABLE foo',
                        ],
                    ],
                    'lookup' => [
                        'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                        'parameters' => [
                            [
                                'key' => 'identifier',
                                'value' => new Expression('3'),
                            ],
                        ],
                        'merge' => [
                            'map' => [
                                [
                                    'field' => '[foo]',
                                    'copy' => '[foo]',
                                ]
                            ],
                        ],
                    ],
                    'connection' => [
                        'dsn' => 'sqlite::memory:',
                    ],
                ],
            ]),
        );
    }

    public function testValidatingConditionalLookupConfiguration(): void
    {
        $service = new Service();

        $this->assertEquals(
            [
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                        'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'lookup' => [
                    'conditional' => [
                        [
                            'condition' => new Expression('(input["identifier"] % 2) == 0'),
                            'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                            'parameters' => [
                                [
                                    'key' => 'identifier',
                                    'value' => new Expression('3'),
                                ],
                            ],
                            'merge' => [
                                'map' => [
                                    [
                                        'field' => '[foo]',
                                        'copy' => '[foo]',
                                    ]
                                ],
                            ],
                        ],
                        [
                            'condition' => new Expression('(input["identifier"] % 2) == 1'),
                            'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                            'parameters' => [
                                [
                                    'key' => 'identifier',
                                    'value' => new Expression('3'),
                                ],
                            ],
                            'merge' => [
                                'map' => [
                                    [
                                        'field' => '[foo]',
                                        'copy' => '[foo]',
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ],
            $service->normalize([
                [
                    'expression_language' => [],
                    'before' => [
                        'queries' => [
                            'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                            'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                            'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                        ],
                    ],
                    'after' => [
                        'queries' => [
                            'DROP TABLE foo',
                        ],
                    ],
                    'lookup' => [
                        'conditional' => [
                            [
                                'condition' => new Expression('(input["identifier"] % 2) == 0'),
                                'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                                'parameters' => [
                                    [
                                        'key' => 'identifier',
                                        'value' => new Expression('3'),
                                    ],
                                ],
                                'merge' => [
                                    'map' => [
                                        [
                                            'field' => '[foo]',
                                            'copy' => '[foo]',
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'condition' => new Expression('(input["identifier"] % 2) == 1'),
                                'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                                'parameters' => [
                                    [
                                        'key' => 'identifier',
                                        'value' => new Expression('3'),
                                    ],
                                ],
                                'merge' => [
                                    'map' => [
                                        [
                                            'field' => '[foo]',
                                            'copy' => '[foo]',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'connection' => [
                        'dsn' => 'sqlite::memory:',
                    ],
                ],
            ]),
        );
    }

    public function testValidatingLoaderConfiguration(): void
    {
        $service = new Service();

        $this->assertEquals(
            [
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                        'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'loader' => [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        [
                            'key' => 'identifier',
                            'value' => new Expression('3'),
                        ],
                    ]
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ],
            $service->normalize([
                [
                    'expression_language' => [],
                    'before' => [
                        'queries' => [
                            'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                            'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                            'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                        ],
                    ],
                    'after' => [
                        'queries' => [
                            'DROP TABLE foo',
                        ],
                    ],
                    'loader' => [
                        'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                        'parameters' => [
                            [
                                'key' => 'identifier',
                                'value' => new Expression('3'),
                            ]
                        ]
                    ],
                    'connection' => [
                        'dsn' => 'sqlite::memory:',
                    ],
                ],
            ]),
        );
    }

    public function testValidatingConditionalLoaderConfiguration(): void
    {
        $service = new Service();

        $this->assertEquals(
            [
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                        'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'loader' => [
                    'conditional' => [
                        [
                            'condition' => new Expression('(input["identifier"] % 2) == 0'),
                            'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                            'parameters' => [
                                [
                                    'key' => 'identifier',
                                    'value' => new Expression('3'),
                                ],
                            ],
                            'merge' => [
                                'map' => [
                                    [
                                        'field' => '[foo]',
                                        'copy' => '[foo]',
                                    ]
                                ],
                            ],
                        ],
                        [
                            'condition' => new Expression('(input["identifier"] % 2) == 1'),
                            'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                            'parameters' => [
                                [
                                    'key' => 'identifier',
                                    'value' => new Expression('3'),
                                ],
                            ],
                            'merge' => [
                                'map' => [
                                    [
                                        'field' => '[foo]',
                                        'copy' => '[foo]',
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ],
            $service->normalize([
                [
                    'expression_language' => [],
                    'before' => [
                        'queries' => [
                            'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                            'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                            'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                        ],
                    ],
                    'after' => [
                        'queries' => [
                            'DROP TABLE foo',
                        ],
                    ],
                    'loader' => [
                        'conditional' => [
                            [
                                'condition' => new Expression('(input["identifier"] % 2) == 0'),
                                'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                                'parameters' => [
                                    [
                                        'key' => 'identifier',
                                        'value' => new Expression('3'),
                                    ],
                                ],
                                'merge' => [
                                    'map' => [
                                        [
                                            'field' => '[foo]',
                                            'copy' => '[foo]',
                                        ]
                                    ],
                                ],
                            ],
                            [
                                'condition' => new Expression('(input["identifier"] % 2) == 1'),
                                'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                                'parameters' => [
                                    [
                                        'key' => 'identifier',
                                        'value' => new Expression('3'),
                                    ],
                                ],
                                'merge' => [
                                    'map' => [
                                        [
                                            'field' => '[foo]',
                                            'copy' => '[foo]',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'connection' => [
                        'dsn' => 'sqlite::memory:',
                    ],
                ],
            ]),
        );
    }

    public function testExtractor(): void
    {
        $service = new Service();

        $this->assertBuildsExtractorExtractsExactly(
            [
                [
                    'id' => 1,
                    'value' => 'Lorem ipsum dolor',
                ],
                [
                    'id' => 2,
                    'value' => 'Sit amet consecutir',
                ],
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                        'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'extractor' => [
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                    'parameters' => [
                        [
                            'key' => 'identifier',
                            'value' => new Expression('3'),
                        ]
                    ]
                ],
                'connection' => [
//                    'dsn' => 'sqlite::memory:',
                    'dsn' => __DIR__.'/db.sqlite',
                ],
            ])->getBuilder(),
        );
    }

    public function testLookup(): void
    {}

    public function testConditionalLookup(): void
    {}

    public function testLoader(): void
    {}

    public function testConditionalLoader(): void
    {}
}
