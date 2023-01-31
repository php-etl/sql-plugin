<?php declare(strict_types=1);

namespace functional\Kiboko\Plugin\SQL;

use Kiboko\Component\PHPUnitExtension\Assert;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Plugin\SQL\Service;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;

final class ServiceTest extends TestCase
{
    use Assert\ExtractorBuilderAssertTrait;
    use Assert\TransformerBuilderAssertTrait;
    use Assert\LoaderBuilderAssertTrait;

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
                        'identifier' => [
                            'value' => new Expression('3')
                        ]
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
                    'extractor' => [
                        'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= :identifier',
                        'parameters' => [
                            'identifier' => [
                                'value' => '@=3'
                            ]
                        ],
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
                        'identifier' => [
                            'value' => new Expression('3'),
                        ]
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
                            'identifier' => [
                                'value' => '@=3',
                            ]
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
                                'identifier' => [
                                    'value' => new Expression('3'),
                                ]
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
                                'identifier' => [
                                    'value' => new Expression('3'),
                                ]
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
                                    'identifier' => [
                                        'value' => '@=3',
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
                                    'identifier' => [
                                        'value' => '@=3',
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
                        'identifier' => [
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
                            'identifier' => [
                                'value' => '@=3',
                            ],
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
                                'identifier' => [
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
                                'identifier' => [
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
                                    'identifier' => [
                                        'value' => '@=3',
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
                                    'identifier' => [
                                        'value' => '@=3',
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
                    'query' => 'SELECT * FROM foo WHERE value IS NOT NULL AND id <= 3',
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
                        'id' => [
                            'value' => new Expression('input["id"]')
                        ],
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
                    'id' => '1',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '2',
                    'value' => 'Sit',
                ]
            ],
            [
                [
                    'id' => '1',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '2',
                    'value' => 'Sit',
                ]
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
                    ],
                ],
                'loader' => [
                    'query' => 'INSERT INTO foo (id, value) VALUES (:id, :value)',
                    'parameters' => [
                        'id' => [
                            'value' => new Expression('input["id"]'),
                        ],
                        'value' => [
                            'value' => new Expression('input["value"]')
                        ],
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

        /**
         * Check if rows are inserted
         */
        $results = $this->checkData('SELECT * FROM foo');
        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '2',
                    'value' => 'Sit',
                ]
            ],
            $results
        );
    }

    public function testLoaderWithAfterQueries(): void
    {
        $service = new Service();

        $this->assertBuildsLoaderLoadsExactly(
            [
                [
                    'id' => '1',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '2',
                    'value' => 'Sit',
                ]
            ],
            [
                [
                    'id' => '1',
                    'value' => 'Sit amet consecutir',
                ],
                [
                    'id' => '2',
                    'value' => 'Sit',
                ]
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)',
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
                        'id' => [
                            'value' => new Expression('input["id"]'),
                        ],
                        'value'=> [
                            'value' => new Expression('input["value"]'),
                        ],
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

        /**
         * Check if foo table is dropped
         */
        $results = $this->checkData("SELECT name FROM sqlite_master WHERE type='table' AND name='foo'");
        $this->assertEquals(
            [],
            $results
        );
    }

    public function testLoaderWithTypedParam(): void
    {
        $service = new Service();

        $this->assertBuildsLoaderLoadsExactly(
            [
                [
                    'id' => '1',
                    'value' => false,
                ],
                [
                    'id' => '2',
                    'value' => true,
                ]
            ],
            [
                [
                    'id' => '1',
                    'value' => false,
                ],
                [
                    'id' => '2',
                    'value' => true,
                ]
            ],
            $service->compile([
                'expression_language' => [],
                'before' => [
                    'queries' => [
                        'CREATE TABLE IF NOT EXISTS foo (id INTEGER NOT NULL, value BOOLEAN NOT NULL)',
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
                        'id' => [
                            'value' => new Expression('input["id"]'),
                        ],
                        'value' => [
                            'value' => new Expression('input["value"]'),
                            'type' => 'boolean'
                        ],
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

        /**
         * Check if foo table is dropped
         */
        $results = $this->checkData("SELECT name FROM sqlite_master WHERE type='table' AND name='foo'");
        $this->assertEquals(
            [],
            $results
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
                'loader' => [
                    'conditional' => [
                        [
                            'condition' => new Expression('input["id"] == 2'),
                            'query' => 'INSERT INTO foo (id, value) VALUES (:id, :value)',
                            'parameters' => [
                                'id' => [
                                    'value' => new Expression('input["id"]'),
                                ],
                                'value' => [
                                    'value' => new Expression('input["value"]'),
                                ],
                            ]
                        ],
                        [
                            'condition' => new Expression('input["id"] == 3'),
                            'query' => 'UPDATE foo SET value = :value WHERE id = 1',
                            'parameters' => [
                                'value' => [
                                    'value' => new Expression('input["value"]'),
                                ],
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

        /**
         * Check if row is inserted
         */
        $results = $this->checkData('SELECT * FROM foo WHERE id = 2');
        $this->assertEquals(
            [
                [
                    'id' => '2',
                    'value' => 'Sit amet consecutir'
                ]
            ],
            $results
        );

        /**
         * Check if row is updated
         */
        $results = $this->checkData('SELECT * FROM foo WHERE id = 1');
        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'value' => 'Ut sed'
                ]
            ],
            $results
        );
    }

    public function testConditionalLoaderWithAfterQueries(): void
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
                            'condition' => new Expression('input["id"] == 2'),
                            'query' => 'INSERT INTO foo (id, value) VALUES (:id, :value)',
                            'parameters' => [
                                'id' => [
                                    'value' => new Expression('input["id"]'),
                                ],
                                'value' => [
                                    'value' => new Expression('input["value"]'),
                                ],
                            ]
                        ],
                        [
                            'condition' => new Expression('input["id"] == 3'),
                            'query' => 'UPDATE foo SET value = :value WHERE id = 1',
                            'parameters' => [
                                'value' => [
                                    'value' => new Expression('input["value"]'),
                                ],
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

        /**
         * Check if foo table is dropped
         */
        $results = $this->checkData("SELECT name FROM sqlite_master WHERE type='table' AND name='foo'");
        $this->assertEquals(
            [],
            $results
        );
    }

    private function checkData(string $query): array
    {
        $connection = new \PDO('sqlite::memory:', null, null, [
            \PDO::ATTR_PERSISTENT => true
        ]);

        $stmt = $connection->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_NAMED);
    }

    public function testExtractorConfigurationWithPersistentConnection(): void
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
                        'identifier' => [
                            'value' => new Expression('3')
                        ]
                    ],
                ],
                'connection' => [
                    'dsn' => 'sqlite::memory:',
                    'options' => [
                        'persistent' => true
                    ]
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
                            'identifier' => [
                                'value' => '@=3'
                            ]
                        ]
                    ],
                    'connection' => [
                        'dsn' => 'sqlite::memory:',
                        'options' => [
                            'persistent' => true
                        ]
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
