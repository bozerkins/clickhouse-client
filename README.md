# Clickhouse Client
A simple version of clickhouse client (using <a href="https://clickhouse.yandex/docs/en/interfaces/http_interface.html">HTTP interface</a>). 
This version provides the closest access to HTTP interface, 
allowing you to use maximum of the <a href="https://clickhouse.yandex/">Clickhouse Database</a> capacities in your PHP applications.

[![CircleCI](https://circleci.com/gh/bozerkins/clickhouse-client/tree/master.svg?style=shield)](https://circleci.com/gh/bozerkins/clickhouse-client/tree/master)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.svg?v=103)](https://opensource.org/licenses/mit-license.php)

## Installation

Basic installation with <a href="https://getcomposer.org/download/">composer</a>

```shell
composer require bozerkins/clickhouse-client
```

## Configurations

```php
use ClickhouseClient\Client\Config;

$config = new Config(
    // basic connection information
    ['host' => '127.0.0.1', 'port' => '8123', 'protocol' => 'http'],
    // settings
    ['database' => 'default'],
    // credentials
    ['user' => 'default', 'password' => '']
);
```
If you wish to use Clickhouse settings, for example create a client only in readonly mode, you can pass settings as 2nd parameter, along with database name.

You do not need do define all of this in case you are using default configurations.
For example if in your workflow only the database is different.

```php
$config = new Config(
    // basic connection information - set to default
    [],
    // settings
    [ 'database' => 'my_shiny_database', 'readonly' => 1 ]
    // credentials - set to defauult
    []
);
```

Communication with Clickhouse Database happens using <a href="http://php.net/manual/en/book.curl.php">php-curl</a> library.
If you wish to control query execution more closely, you can pass a 4th parameter to Config constructor, containing php-curl library parameters.

For example if we would like to set a 5 second connection timeout, we would create a following config:

```php
use ClickhouseClient\Client\Config;

$config = new Config(
    // basic connection information - set to default
    [],
    // settings
    [ 'database' => 'my_shiny_database', 'readonly' => 1 ]
    // credentials - set to defauult
    [],
    // additional CURL options
    [ CURLOPT_TIMEOUT => 5 ]
);
```

Full list of supported constants can be found in <a href="http://php.net/manual/en/function.curl-setopt.php">curl_setopt function documentation</a>.

## Client

Creating a client is fairly simple.

```php
use ClickhouseClient\Client\Client;

$client= new Client($config);
```

## Reading from Clickhouse

There are several methods for reading data from clickhouse.

### Simple Query

This method is primarily used for getting statistics, aggregated data from clickhouse.

```php
# perform select
$response = $client->query(
    'SELECT * FROM system.numbers LIMIT 10'
);
# get decoded output - database response
$response->getContent();
# get raw output string - raw string received from clickhouse
$response->getOutput();
# get communication details - curl defails for the request
$response->getDetails();
# and a neat shortcut for getting http response code
$response->getHttpCode();
```

Each client query returns a response with all the information about the connection performed and response.

### Query data into Stream

It is possible to read data from clickhouse directly into a stream - a file for example.

```php
# create a stream - open a file
$stream = fopen('/tmp/file-to-read-data-into', 'r+');
# query data into the file
$client->queryStream(
    "SELECT * FROM system.numbers LIMIT 5", 
    $stream
);
```

### Query data into Closure (function-callable)

This method is useful when you intend to divide one clickhouse response into several destinations.
 
 ```php
 # open file 1
 $file1 = fopen('/tmp/file-to-read-data-into-1', 'r+');
 # open file 2
 $file2 = fopen('/tmp/file-to-read-data-into-2', 'r+');
 
 # query data, process response with anonymous function
 $client->queryClosure(
    "SELECT * FROM system.numbers LIMIT 100", 
    function($line) use ($file1, $file2) {
        $row = json_decode($line);
        if ($row['number'] % 2 === 0) {
            fwrite($file1, $line . PHP_EOL);
        } else {
            fwrite($file2, $line . PHP_EOL);
        }
     }
 );
 ```

## Writing into Clickhouse

There are several ways to writing to the database as well.

### Simple Insert

Most common way of writing to a database. 

```php
# write data to a table
$client->write('INSERT INTO myTable VALUES (1), (2), (3)');
```

> NOTE: clickhouse does not have escape mechanisms like MySQL / Oracle / etc. For save inserts please see other insert methods.

### Rows Insert

The safest and easiest way to insert data into clickhouse table is to use "writeRows" methods. 
The method takes the table to insert data to as first parameter, and php array of rows as second.
When inserting data, method "writeRows" encodes the data into appropriate format for clickhouse database to interpret.
By default it is JSON format. This ensures no manual escape of data is required.

```php
# write data to a table
$client->writeRows('INSERT INTO myTable',
    [
        ['number' => 5],
        ['number' => 6],
        ['number' => 7]
    ]
);
```


### File Insert

Another way of inserting data is directly from a file. 
 
> NOTE: the format of the data in the file should match the one clickhouse is expecting

```php
$stream = fopen('my/local/file.lines.json','r');

$client->writeStream(
    'INSERT INTO t',
    $stream
);
```

This method actually accepts data not only from a file, but from a stream.
Thus we can import data from other places, like memory (or anything that can be represented as a stream, really).

```php
# create memory stream
$stream = fopen('php://memory','r+');
# write some data into it
fwrite($stream, '{"a":8}'.PHP_EOL.'{"a":9}'.PHP_EOL );
# rewind pointer to the beginning
rewind($stream);

# insert the data
$client->writeStream(
    'INSERT INTO t',
    $stream
);
```

## System Queries

Client object supports system queries. Such queries can manage database schema, processes and more.

```php
# drop table
$client->system('DROP TABLE IF EXISTS t');
# create table
$client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');
# kill query
$client->system('KILL QUERY WHERE query_id = "SOME-QUERY-ID"');
```

In case of failure to perform the operation client throws an Exception.

## Formats

There are several formats that clickhouse support. This is used for retrieving and inserting data.
By default JSON is used. 
When you perform simple select / insert queries data is encoded into JSON and transferred between client and clickhouse.
This does not work for Stream / Closure queries/writes.
When performing any query/write you can change format, by passing a class name as the last parameter.

```php
use ClickhouseClient\Client\Format;

# select using default JSON format
$client->query('SELECT * FROM system.numbers LIMIT 5');
# select using TabSeparated format
$client->query('SELECT * FROM system.numbers LIMIT 5', Format\TabSeparatedFormat::class);

# insert usin JSON format
$client->writeRows('INSERT INTO myTable',
    [
        ['number' => 5],
        ['number' => 6],
        ['number' => 7]
    ]
);
# insert usin TabSeparated format
$client->writeRows('INSERT INTO myTable',
    [
        ['number' => 5],
        ['number' => 6],
        ['number' => 7]
    ], 
    Format\TabSeparatedFormat::class
);

# create client with differrent default format
$client = new Client($config, Format\TabSeparatedFormat::class);
# create client without default format (which would result in errors in some cases)
client = new Client($config, null);
```

## Ping

Clickhouse Database supports a ping method, yay. So this client supports it as well.

You can check if the database responds properly using "ping" method.

```php
$client->ping();
```

If you do not get an Exception out of this, that's generally a good sing.

## Exception handling

Any request the client class makes can throw an exception. 

It is a good practice to check for exceptions when performing query.
 
```php
use ClickhouseClient\Exception\Exception;

try {
    $client->ping();
} catch (Exception $ex) {
    # get configurations of the connector
    $ex->getConfig();
    # get repsonse 
    $ex->getResponse();
    # and get the message, ofc
    $ex->getMessage();
}
```

## Tests

Well, we have got some. They do not fail, and fairly work most of the time.

## Support

How much do we care?
Enough to leave an email just here: <a href="b.ozerkins@gmail.com">b.ozerkins@gmail.com</a>







