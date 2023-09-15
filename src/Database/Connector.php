<?php

namespace Titeca\SqlAnywhere\Database;

use Illuminate\Database\Connectors\Connector as BaseConnector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PDO;
use Throwable;
use Titeca\SqlAnywhere\Exceptions;
use Titeca\SqlAnywhere\PDO\Client;

class Connector extends BaseConnector implements ConnectorInterface
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_AUTOCOMMIT => true,
        PDO::ATTR_PERSISTENT => false,
    ];

    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \Titeca\SqlAnywhere\PDO\Client
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        return $connection;
    }

    /**
     * Create a new PDO connection instance.
     *
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array  $options
     * @return \Titeca\SqlAnywhere\PDO\Client
     */
    protected function createPdoConnection($dsn, $username, $password, $options) : Client
    {
        return new Client($dsn, $options);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array $config
     * @return string
     */
	protected function getDsn(array $config): string
    {
        if ($missing = implode(", ", array_diff(['host', 'port', 'database', 'username', 'password'], array_keys($config))))
            throw new Exceptions\ConnectorException("Failed to compile data source name due missing configuration parameters: \"{$missing}\"");

        $dsn = Collection::make([
            'commlinks' => "tcpip{host={$config['host']}:{$config['port']}}",
            'uid' => $config['username'],
            'pwd' => $config['password'],
            'dbn' => $config['database'],
        ]);

        if (isset($config['server']))
            $dsn->put('eng', $config['server']);

        if (isset($config['charset']))
            $dsn->put('charset', $config['charset']);

		return $dsn->map(fn($value, $key) => "{$key}={$value}")->implode(";");
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByLostConnection(Throwable $e): bool
    {
        return Str::contains($e->getMessage(), [
        	'Authentication violation',
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'Name or service not known',
            'Not connected to a database'
        ]);
    }
}
