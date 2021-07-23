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
        $this->vfs?->unmount();
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
                    'options' => []
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
                    'options' => []
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
                    'options' => []
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
                    'options' => []
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
                    'options' => []
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
                    'id' => '1',
                    'value' => 'Lorem ipsum dolor',
                ],
                [
                    'id' => '2',
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
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= 3',
                    'parameters' => []
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                ],
            ])->getBuilder(),
        );
    }

    public function testLookup(): void
    {
        $service = new Service();

        $this->assertBuildsTransformerTransformsExactly(
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
            [
                [
                    'id' => 1,
                    'value' => 'Lorem ipsum dolor',
                    'additionalValue' => 'Aenean at iaculis',
                ],
                [
                    'id' => 2,
                    'value' => 'Sit amet consecutir',
                    'additionalValue' => 'Sed nec venenatis',
                ],
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL, additionalValue VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value, additionalValue) VALUES (1, "Lorem ipsum dolor", "Aenean at iaculis")',
                        'INSERT INTO foo (id, value, additionalValue) VALUES (2, "Sit amet consecutir", "Sed nec venenatis")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'lookup' => [
                    'query' => 'SELECT additionalValue FROM foo WHERE id = :id',
                    'parameters' => [
                        [
                            'key' => 'id',
                            'value' => new Expression('input["id"]'),
                        ]
                    ],
                    'merge' => [
                        'map' => [
                            [
                                'field' => '[additionalValue]',
                                'expression' => 'lookup["additionalValue"]',
                            ]
                        ]
                    ]
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                    'options' => [
                        'persistent' => true
                    ]
                ],
            ])->getBuilder(),
        );
    }

    public function testConditionalLookup(): void
    {
        $service = new Service();

        $this->assertBuildsTransformerTransformsExactly(
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
            [
                [
                    'id' => 1,
                    'value' => 'Lorem ipsum dolor',
                    'additionalValue' => 'Aenean at iaculis',
                ],
                [
                    'id' => 2,
                    'value' => 'Sit amet consecutir'
                ],
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL, additionalValue VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value, additionalValue) VALUES (1, "Lorem ipsum dolor", "Aenean at iaculis")',
                        'INSERT INTO foo (id, value, additionalValue) VALUES (2, "Sit amet consecutir", "Sed nec venenatis")',
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
                            'condition' => new Expression('input["id"] === 1'),
                            'query' => 'SELECT additionalValue FROM foo WHERE id = 1',
                            'merge' => [
                                'map' => [
                                    [
                                        'field' => '[additionalValue]',
                                        'expression' => 'lookup["additionalValue"]',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                    'options' => [
                        'persistent' => true
                    ]
                ],
            ])->getBuilder()
        );
    }

    public function testLoader(): void
    {
        $service = new Service();

        $this->assertBuildsLoaderLoadsExactly(
            [
                [
                    'id' => '2',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '3',
                    'value' => 'Sit',
                ]
            ],
            [
                [
                    'id' => '2',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '3',
                    'value' => 'Sit',
                ]
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
                    ],
                ],
                'after' => [
                    'queries' => [
                        'DROP TABLE foo',
                    ],
                ],
                'loader' => [
                    'query' => 'INSERT INTO foo (id, value) VALUES (:id, :value)',
                    'parameters' => [
                        [
                            'key' => 'id',
                            'value' => new Expression('input["id"]')
                        ],
                        [
                            'key' => 'value',
                            'value' => new Expression('input["value"]'),
                        ]
                    ]
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
//                    'dsn' => 'sqlite:'.__DIR__.'/db.sqlite',
                ],
            ])->getBuilder(),
        );
    }

    public function testConditionalLoader(): void
    {
        $service = new Service();

        $this->assertBuildsLoaderLoadsExactly(
            [
                [
                    'id' => '2',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '3',
                    'value' => 'Ut sed',
                ]
            ],
            [
                [
                    'id' => '2',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '3',
                    'value' => 'Ut sed',
                ]
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                        'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")',
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
                            'condition' =>  new Expression('input["id"] == 2'),
                            'query' => 'INSERT INTO foo (id, value) VALUES (:id, :value)',
                            'parameters' => [
                                [
                                    'key' => 'id',
                                    'value' => new Expression('input["id"]')
                                ],
                                [
                                    'key' => 'value',
                                    'value' => new Expression('input["value"]'),
                                ]
                            ]
                        ],
                        [
                            'condition' => new Expression('input["id"] == 3'),
                            'query' => 'UPDATE foo SET value = :value WHERE id = 1',
                            'parameters' => [
                                [
                                    'key' => 'value',
                                    'value' => new Expression('input["value"]'),
                                ]
                            ]
                        ]
                    ]
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
//                    'dsn' => 'sqlite:'.__DIR__.'/db.sqlite'
                ],
            ])->getBuilder(),
        );
    }
}
