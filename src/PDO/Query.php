<?php

namespace Titeca\SqlAnywhere\PDO;

use Illuminate\Support\LazyCollection;

class Query
{
    const FETCH_ARRAY = 'array';
	const FETCH_OBJECT = 'object';
	const FETCH_ROW = 'row';
	const FETCH_FIELD = 'field';
	const FETCH_ASSOC = 'assoc';

    /**
     * Create new query instance
     *
     * @param  resource $connection
     * @param  resource $result
     * @return void
     */
    public function __construct(protected $connection, protected $result)
	{
		//
	}

    /**
	 * Returns number of rows of the query.
     *
     * @param  string  $type
	 * @return int
	 */
	public function count(string $type = 'row'): int
	{
        return $type == 'row' ? sasql_num_rows($this->result) : sasql_num_fields($this->connection);
	}

    /**
	 * Returns the number of rows of the query.
     *
	 * @return int
	 */
	public function rowCount(): int
	{
		return sasql_num_rows($this->result);
	}

	/**
	 * Returns the number of fields of the query.
     *
	 * @return int
	 */
	public function fieldCount(): int
	{
		return sasql_num_fields($this->connection);
	}

	/**
	 * Returns the number of fields of the query.
     *
	 * @return int
	 */
	public function columnCount(): int
	{
		return sasql_num_fields($this->connection);
	}

	/**
	 * Returns the number of rows affected.
     *
	 * @return int
	 */
	public function affectedRows(): int
	{
		return sasql_affected_rows($this->connection);
	}

    /**
	 * Return single row of the result
     *
	 * @param  string $type = 'assoc'
	 * @return array|object
	 */
	public function fetch(string $type = self::FETCH_ASSOC): array|object
	{
        if (!$this->result)
            return [];

        return match ($type) {
            self::FETCH_ASSOC => sasql_fetch_assoc($this->result),
            self::FETCH_ROW => sasql_fetch_row($this->result),
            self::FETCH_FIELD => sasql_fetch_field($this->result),
            self::FETCH_OBJECT => sasql_fetch_object($this->result),
            default => sasql_fetch_array($this->result),
        };
	}

    /**
	 * Return all rows of the result
     *
	 * @param  string $type = 'assoc'
	 * @return array
	 */
	public function fetchAll(string $type = self::FETCH_ASSOC): array
	{
        if (!$this->result)
            return [];

        return match ($type) {

            self::FETCH_ASSOC => LazyCollection::make(function() {
                while ($row = sasql_fetch_assoc($this->result)) yield $row;
            })->toArray(),

            self::FETCH_ROW => LazyCollection::make(function() {
                while ($row = sasql_fetch_row($this->result)) yield $row;
            })->toArray(),

            self::FETCH_FIELD => LazyCollection::make(function() {
                while ($row = sasql_fetch_field($this->result)) yield $row;
            })->toArray(),

            self::FETCH_OBJECT => LazyCollection::make(function() {
                while ($row = sasql_fetch_object($this->result)) yield $row;
            })->toArray(),

            default => LazyCollection::make(function() {
                while ($row = sasql_fetch_array($this->result)) yield $row;
            })->toArray()
        };
	}

    /**
	 * Return the value of the result parsed as an object
     *
	 * @return object
	 */
	public function fetchObject(): object
	{
		return sasql_fetch_object($this->result);
	}
}
