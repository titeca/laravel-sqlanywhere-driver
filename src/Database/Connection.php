<?php

namespace Titeca\SqlAnywhere\Database;

use Illuminate\Database\Connection as BaseConnection;
use Titeca\SqlAnywhere\Grammar;
use Titeca\SqlAnywhere\PDO\Client;

class Connection extends BaseConnection
{
    /**
	 * Create a new database connection instance.
	 *
	 * @param  \Titeca\SqlAnywhere\PDO\Client $pdo
	 * @param  string $database
	 * @param  string $tablePrefix
	 * @param  array $config
	 * @return void
	 */
	public function __construct(Client $pdo, $database = '', $tablePrefix = '', array $config = array())
	{
		$this->pdo = $pdo;

		// First we will setup the default properties. We keep track of the DB
		// name we are connected to since it is needed when some reflective
		// type commands are run such as checking whether a table exists.
		$this->database = $database;
		$this->tablePrefix = $tablePrefix;
		$this->config = $config;

		// We need to initialize a query grammar and the query post processors
		// which are both very important parts of the database abstractions
		// so we initialize these to their default values while starting.
		$this->useDefaultPostProcessor();
        $this->useDefaultQueryGrammar();
        $this->useDefaultSchemaGrammar();
	}

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new Grammar\QueryGrammar($this);
    }

	/**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar|null
     */
    protected function getDefaultSchemaGrammar()
    {
        return new Grammar\SchemaGrammar($this);
    }

    /**
	 * Run a select statement against the database.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  $useReadPdo = true
	 * @return array
	 */
	public function select($query, $bindings = [], $useReadPdo = true)
	{
		return $this->run($query, $bindings, function($query, $bindings)
		{
			if ($this->pretending()) return [];

			// For select statements, we'll simply execute the query and return an array
			// of the database result set. Each element in the array will be a single
			// row from the database table, and will either be an array or objects.
			$statement = $this->getReadPdo()->prepare($query);
			$statement = $statement->execute($this->prepareBindings($bindings));

			return $statement->fetchAll();
		});
	}

	/**
	 * Run an SQL statement and get the number of rows affected.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function affectingStatement($query, $bindings = [])
	{
		return $this->run($query, $bindings, function($query, $bindings)
		{
			if ($this->pretending()) return 0;

			// For update or delete statements, we want to get the number of rows affected
			// by the statement and return that back to the developer. We'll first need
			// to execute the statement and then we'll use PDO to fetch the affected.
			$statement = $this->getPdo()->prepare($query);
			$statement = $statement->execute($this->prepareBindings($bindings));

			return $statement->affectedRows();
		});
	}
}
