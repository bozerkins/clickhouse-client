# Clickhouse Client
A simple version of clickhouse client (using <a href="https://clickhouse.yandex/docs/en/interfaces/http_interface.html">HTTP interface</a>). 
This version provides the closest access to HTTP interface, 
allowing you to use maximum of the <a href="https://clickhouse.yandex/">Clickhouse Database</a> capacities in your PHP applications.

## Installation

Basic installation with <a href="https://getcomposer.org/download/">composer</a>

```shell
composer require justfuse/clickhouse-client
```

## Usage examples

First you would need to create a client and configurations objects.


### Config

Here is an example of creating a config object with default clickhouse database configurations.

```php
use JustFuse\ClickhouseClient\Client\Config;

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
use JustFuse\ClickhouseClient\Client\Config;

$config = new Config(
    [], [ 'database' => 'my_shiny_database', 'readonly' => 1 ], []
);
```

Communication with Clickhouse Database happens using <a href="http://php.net/manual/en/book.curl.php">php-curl</a> library.
If you wish to control query execution more closely, you can pass a 4th parameter to Config constructor, containing php-curl library parameters.

For example if we would like to set a 5 second connection timeout, we would create a following config:

```php
use JustFuse\ClickhouseClient\Client\Config;

$config = new Config(
    [], [ 'database' => 'my_shiny_database' ], [], [ CURLOPT_TIMEOUT => 5 ]
);
```

Full list of supported constants can be found in <a href="http://php.net/manual/en/function.curl-setopt.php">curl_setopt function documentation</a>.

### Client

Creating a client is fairly simple.

```php
use JustFuse\ClickhouseClient\Client\Client;
use JustFuse\ClickhouseClient\Client\Format;

$client= new Client($config);
```

### Client - fetching data

To perform a query, we would need to call a "query" method.

```php
# perform select
$response = $client->query('SELECT * FROM system.numbers LIMIT 10');
# get output (json decoded)
$response->getOutput();
# get raw output string
$response->getOutputRaw();
# get communication details
$response->getDetails();
# and a neat shortcut for getting http response code
$response->getHttpCode();
```

Each client query returns a response with all the information about the connection performed and response.

Response is decoded using one of the format classes. By default Format\JsonFormat is used.

NOTE: all the select performed using "query" method automatically happen in readonly mode

### Client - managing database schema and more

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

### Client - write data

There are 3 ways of writing data to Clickhouse Database: 

1. as a simple sql query
2. using one of the format classes
3. streaming data from a file or other stream


#### Client - write data using sql

The simplest way of inserting your data into the database.

```php
# write data to a table
$client->writePlain('INSERT INTO t VALUES (1), (2), (3)');
```

#### Client - write data using one of the format classes

This approach is a bit trickier, but generally does not require an explicit data escaping policy.

By default the client object uses "Format\JsonFormat" class, but you can pass any other of the formats as an argument.

```php
# write data to a table
$this->client->writeRows('INSERT INTO t',
    [
        ['a' => 5],
        ['a' => 6],
        ['a' => 7]
    ],
    Format\JsonEachRowFormat::class
);
```

#### Client - write data using stream

This approach is even more tricky, as your data in the file should correspond to the formatting class you chose.
Though, it is the fastest (and cheapest) way of getting data into Clickhouse Database via http client. 

A stream can be anything. In this example we are using memory stream, but we can actually get a file handler and pass it into insert method instead.

```php
$stream = fopen('php://memory','r+');
fwrite($stream, '{"a":8}'.PHP_EOL.'{"a":9}'.PHP_EOL );
rewind($stream);

$this->client->writeStream(
    'INSERT INTO t',
    $stream,
    Format\JsonEachRowFormat::class
);
```

### Client - ping

Clickhouse Database supports a ping method, yay. So this client supports it as well.

You can check if the database responds properly using "ping" method.

```php
$client->ping();
```

If you do not get an Exception out of this, that's generally a good sing.

### Connector - Lower level communication

For those peeps who does not like black magic of clients and would enjoy performing requests on a lower level we have Connector class.

This is a simple wrapper on php-curl library and some other stuff.

How to use it:

```php
use JustFuse\ClickhouseClient\Connector\Config;
use JustFuse\ClickhouseClient\Connector\Connector;
use JustFuse\ClickhouseClient\Connector\Request;
use JustFuse\ClickhouseClient\Client\Format;

# create connector config (which is much simplier that the previous one)
$config = new Config();
$config->setHost('127.0.0.1');
$config->setPort('8123');
$config->setProtocol('http');
$config->setUser('default');
$config->setPassword('my-shiny-password');
$config->setDefaultCurlOptions(
    [
        CURLOPT_TIMEOUT => 5
    ]
);

# create a connector
$connector = new Connector($config);

# create a request
$request = new Request();
$request->setGet(['query' => 'SELECT 1']);

# perform query and get a response
$response = $this->connector->perform($request);

# we can use the same formatters from the previous section
# we should understand how they work though
$format = new Format\JsonFormat();

# create a request using format
$request = new Request();
$request->setGet([ 'query' => 'SELECT 1 FORMAT ' . $format->format(false) ]);

# perform query and get a response and decode it with format
$response = $this->connector->perform($request, $format);
```

### Exception handling

Any request the client class makes can throw an exception. 

It is a good practice to check for exceptions when performing query.
 
```php
use JustFuse\ClickhouseClient\Exception\Exception;

try {
    $client->ping();
} catch (Exception $ex) {
    # get configurations of the connector
    $ex->getConfig();
    # get request for the connector
    $ex->getRequest();
    # get repsonse 
    $ex->getResponse();
    # and get the message, ofc
    $ex->getMessage();
}
```

### Tests

Well, we have got some. They do not fail, and fairly work most of the time.

### Support

How much do we care?
Enough to leave an email just here: <a href="b.ozerkins@gmail.com">b.ozerkins@gmail.com</a>







