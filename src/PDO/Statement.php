<?php

namespace Titeca\SqlAnywhere\PDO;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Titeca\SqlAnywhere\Exceptions;

class Statement
{
    /**
     * Bindings
     *
     * @var array
    */
    protected $bindings = [];

    /**
     * Create new statement
     *
     * @param  resource $connection
     * @param  string $query
     * @return void
     */
    public function __construct(
        protected $connection,
        protected string $query)
	{
		//
	}

    /**
	 * Bind parameter
     *
	 * @param  mixed $key
	 * @param  mixed $&value
	 * @return self
	 */
	public function bindValue($key, $value): self
    {
        is_string($key) ? $this->bindings[$key] = $value : $this->bindings[] = $value;
        return $this;
	}

    /**
     * Execute statement
     *
     * @param  array $bindings = []
     * @return \Titeca\SqlAnywhere\Query
     */
    public function execute(array $bindings = []): Query
    {
        $query = Str::of($this->query);
        $bindings = Collection::make($this->bindings)->merge($bindings)->mapWithKeys(fn($value, $key) =>
            [!is_int($key) && !Str::startsWith($key, ':') ? ":{$key}" : $key => is_int($key) && empty($value) ? 'NULL' : "'" . sasql_escape_string($this->connection, $value) . "'"]);

        $query = $query->replaceArray('?', $bindings->filter(fn($value, $key) => is_int($key)));
        $bindings->each(function($value, $key) use (&$query) {
            $query = is_int($key) ? $query : $query->replace($key, $value);
        });

        if (!$query = @sasql_query($this->connection, (string) $query))
            throw new Exceptions\QueryException("SQL-query problem: " . sasql_error($this->connection), 110);

        return new Query($this->connection, $query);
	}
}
