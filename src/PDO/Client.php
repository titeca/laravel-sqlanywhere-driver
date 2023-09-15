<?php

namespace Titeca\SqlAnywhere\PDO;

use PDO;
use Titeca\SqlAnywhere\Exceptions;

class Client
{
    /**
     * Connection
     *
     * @var mixed
     */
    protected $connection = false;

    /**
     * Options
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_AUTOCOMMIT => true,
        PDO::ATTR_PERSISTENT => false,
    ];

    /**
     * Database information
     *
     * @var array
     */
    protected $info = [];

    /**
	 * Create connection
     *
	 * @param  string  $dsn
     * @param  array $options = []
     * @return void
	 */
	public function __construct(protected string $dsn, array $options = [])
	{
        $this->options = array_diff_key($this->options, $options) + $options;
        $this->info = ['dsn' => $dsn, 'options' => $this->options];

        if (!extension_loaded('sqlanywhere'))
			throw new Exceptions\ClientException("SqlAnywhere extension is not enabled on this server!", 100);

        if (!$this->connection = $this->options[PDO::ATTR_PERSISTENT] ? @sasql_pconnect($this->dsn) : @sasql_connect($this->dsn))
			throw new Exceptions\ClientException("Connection problem: " . sasql_error(), 101);

		sasql_set_option($this->connection, 'auto_commit', $this->options[PDO::ATTR_AUTOCOMMIT] ? 'on' : 0);
	}

    /**
     * Destroy connection
     *
     * @return void
     */
    public function __destruct() {
	    @sasql_commit($this->connection);
	    @sasql_close($this->connection);
  	}

    /**
	 * Return error code for connection
     *
	 * @return int
	 */
	public function errorCode(): int
	{
		return $this->connection ? sasql_errorcode($this->connection) : 0;
	}

	/**
	 * Returns all error info for connection
     *
	 * @return string
	 */
	public function errorInfo(): string
	{
		return $this->connection ? sasql_error($this->connection) : "Unknown error";
	}

    /**
	 * Execute a SQL-query
     *
	 * @param  string  $query
	 * @return \Titeca\SqlAnywhere\Query
	 */
	public function query(string $query): Query
	{
        if (!$query = @sasql_query($this->connection, $query))
            throw new Exceptions\QueryException("SQL-query problem: " . sasql_error($this->connection), 110);

        return new Query($this->connection, $query);
	}

    /**
	 * Create a prepared statement
     *
	 * @param  string $query
	 * @return \Titeca\SqlAnywhere\Statement
	 */
	public function prepare(string $query): Statement
	{
		return new Statement($this->connection, $query);
	}

  	/**
     * Quote
     *
     * @param  mixed $data = null
     * @return bool
     */
  	public function quote($data = null): bool
	{
		return true;
	}

	/**
	 * Returns the last value inserted into an identity column or
	 * a default autoincrement column, or zero if the most recent
	 * insert was into a table that did not contain an identity or
	 * default autoincrement column.
	 *
	 * @return int
	 */
	public function lastInsertId(): int
	{
		return sasql_insert_id( $this->connection );
	}

    /**
     * Start database transaction
     *
     * @return bool
     */
	public function beginTransaction(): bool
    {
		return true;
	}

	/**
	 * Commit the transaction
     *
	 * @return bool
	 */
	public function commit(): bool
	{
		return sasql_commit($this->connection);
	}

	/**
	 * Rollback last committed action
     *
	 * @return bool
	 */
	public function rollback(): bool
	{
		return sasql_rollback($this->connection);
	}
}
