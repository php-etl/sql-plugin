# SQL Plugin

This plugin allows you to retrieve or insert data from/to a database.

## Installation

```
composer require php-etl/sql-plugin
```

## Basic usage

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

## Advanced Usage : using params in your queries

Thanks to the SQL plugin, it is possible to write your queries with parameters.

### With params

If you write a prepared statement using named parameters (`:param`), your parameter key in the configuration will be 
the name of your parameter without the `:` :

```yaml
sql:
  # ... 
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
  # ... 
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

## See more
If you want to see complete configurations, please go to the [examples](/examples) folder.
