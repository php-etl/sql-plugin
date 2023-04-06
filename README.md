SQL Plugin
===

[![Mutations](https://github.com/php-etl/sql-plugin/actions/workflows/infection.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/infection.yaml)
[![PHPUnit](https://github.com/php-etl/sql-plugin/actions/workflows/phpunit.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/phpunit.yaml)
[![Quality](https://github.com/php-etl/sql-plugin/actions/workflows/quality.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/quality.yaml)
[![PHPStan level 5](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-5.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-5.yaml)
[![PHPStan level 6](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-6.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-6.yaml)
[![PHPStan level 7](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-7.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-7.yaml)
[![PHPStan level 8](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-8.yaml/badge.svg)](https://github.com/php-etl/sql-plugin/actions/workflows/phpstan-8.yaml)
![PHP](https://img.shields.io/packagist/php-v/php-etl/sql-plugin)

## What is it ?

The SQL plugin allows you to write your own SQL queries and use them into the Pipeline stack.

SQL, Structured Query Language, is a language for manipulating databases.

## Installation

```shell
composer require php-etl/sql-plugin
```

## Usage

### Database connection
The SQL plugin uses the PDO extension and relies on its interface to access databases using
the `dsn`, `username` and `password` parameters.

This connection must be present in any case, whether it be when defining the extractor,
loader or lookup.

```yaml
connection:
    dsn: 'mysql:host=127.0.0.1;port=3306;dbname=kiboko'
    username: username
    password: password
```

It is possible to specify options at the time of this connection using `options`. Currently, it is only possible to 
specify if the database connection should be persistent.

```yaml
connection:
    # ...
    options:
      persistent: true
```

### Building an extractor
```yaml
sql:
  extractor:
    query: 'SELECT * FROM table1'
  connection:
    dsn: 'mysql:host=127.0.0.1;port=3306;dbname=kiboko'
    username: username
    password: password
```
### Building a lookup

```yaml
sql:
  lookup:
    query: 'SELECT * FROM table2 WHERE bar = foo'
    merge:
      map:
        - field: '[options]'
          expression: 'lookup["name"]'
  connection:
    dsn: 'mysql:host=127.0.0.1;port=3306;dbname=kiboko'
    username: username
    password: password

```

### Building a loader
```yaml
sql:
  loader:
    query: 'INSERT INTO table1 VALUES (bar, foo, barfoo)'
  connection:
    dsn: 'mysql:host=127.0.0.1;port=3306;dbname=kiboko'
    username: username
    password: password

```

## Advanced Usage

### Using params in your queries

Thanks to the SQL plugin, it is possible to write your queries with parameters.

If you write a prepared statement using named parameters (`:param`), your parameter key in the configuration will be 
the name of your parameter without the `:` :

```yaml
sql:
  loader:
    query: 'INSERT INTO table1 VALUES (:value1, :value2, :value3)'
    parameters:
      - key: value1
        value: '@=input["value1"]'
      - key: value2
        value: '@=input["value3"]'
      - key: value3
        value: '@=input["value3"]'
    # ... 
```

If you are using a prepared statement using interrogative markers (`?`), your parameter key in the
configuration will be its position (starting from 1) :

```yaml
sql:
  loader: 
    query: 'INSERT INTO table1 VALUES (?, ?, ?)'
    parameters:
      - key: 1
        value: '@=input["value1"]'
      - key: 2
        value: '@=input["value3"]'
      - key: 3
        value: '@=input["value3"]'
  # ... 
```

### Creating before and after queries

In some cases, you may need to run queries in order to best prepare for the execution of your pipeline.

#### Before queries
Before queries will be executed before performing the query written in the configuration. Often, these are
queries that set up the database.

```yaml
sql:
  before:
    queries:
      - 'CREATE TABLE foo (id INTEGER NOT NULL, value VARCHAR(255) NOT NULL)'
      - 'INSERT INTO foo (id, value) VALUES (1, "Lorem ipsum dolor")'
      - 'INSERT INTO foo (id, value) VALUES (2, "Sit amet consecutir")'
  # ...
```

#### After queries
After queries will be executed after performing the query written in the configuration. Often, these are
queries that clean up the database.

```yaml
sql:
  after:
    queries:
      - 'DROP TABLE foo'
  # ...
```
