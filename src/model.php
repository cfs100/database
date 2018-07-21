<?php

namespace database;

use \PDO;

class model
{
	protected static $connection;
	public static $crass = true;

	protected $statement;
	protected $resultset;
	protected $data;
	protected $rows;
	protected static $config;
	protected $debugger;

	public function __construct(array $data = null)
	{
		if (!empty($data)) {
			$this->data($data);
		}

		if (class_exists('\\debugger\\instance')) {
			$this->debugger = new \debugger\instance(static::class);
		}
	}

	public static function config(array $data)
	{
		static::$config = $data;
	}

	protected function connect()
	{
		if (!static::$connection) {
			if (!is_array($config = static::$config)) {
				$error = 'Database connection data not set';

				if ($this->debugger) {
					$this->debugger->error($error);
				}

				throw new \BadMethodCallException($error);
			}

			$options = [];

			if (isset($config['db-name'])) {
				$options[] = "dbname={$config['db-name']}";
			}
			if (isset($config['db-host'])) {
				$options[] = "host={$config['db-host']}";
			}
			if (isset($config['db-port'])) {
				$options[] = "port={$config['db-port']}";
			}
			if (isset($config['charset'])) {
				$options[] = "charset={$config['charset']}";
			}

			static::$connection = new PDO(
				$config['db-type'] . (!empty($options) ? ':' . implode(';', $options) : null)
			, $config['db-user'], $config['db-pass']);

			static::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			static::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if ($config['db-type'] == 'pgsql') {
				static::$crass = false;
			}
		}

		return static::$connection;
	}

	public function quote($value)
	{
		switch (gettype($value)) {
			case 'boolean':
				return $value ? 'true' : 'false';
			case 'NULL':
				return 'null';
			case 'integer':
			case 'double':
				return $value;
			default:
				return $this->connect()->quote($value);
		}
	}

	public function exec($sql)
	{
		if ($this->debugger) {
			$this->debugger->log((string) $sql);
		}

		$aux = $this->connect()->exec($sql);
		if ($aux === false) {
			$info = $this->connect()->errorInfo();

			if ($this->debugger) {
				$this->debugger->error($info);
			}

			throw new \Exception($info[2], 500);
		}

		if ($this->debugger) {
			$this->debugger->info("Rows affected: {$aux}");
		}

		return $aux;
	}

	public function query($sql)
	{
		if ($this->debugger) {
			$this->debugger->log((string) $sql);
		}

		$this->statement = $this->reset()->connect()->query($sql);

		if (!$this->statement) {
			$this->debugger->error($this->connect()->errorInfo());
			throw new \Exception($this->connect()->errorInfo()[2], 500);
		}

		$this->rows = $this->statement->rowCount();

		return $this;
	}

	public function nextRowset()
	{
		$this->data = null;

		if (!is_array($this->resultset)) {
			return null;
		}

		$rowset = next($this->resultset);
		$this->rows = $rowset ? count($rowset) : false;

		if ($this->debugger) {
			$this->debugger->log('Next rowset... ' . ($this->rows() === false ? 'no more rowsets' : "{$this->rows} row" . ($this->rows > 1 ? 's' : null)));
		}

		if (!$rowset) {
			reset($this->resultset);
		}

		return (boolean) $rowset;
	}

	protected function _nextRowset()
	{
		if (!$this->statement) {
			return null;
		}

		try {
			return $this->statement->nextRowset();
		} catch (\Throwable $e) {
			if ($e->getCode() != 'IM001') {
				throw $e;
			}
		}

		return false;
	}

	public function reset()
	{
		$this->rows =
		$this->data =
		$this->resultset =
		$this->statement = null;
		return $this;
	}

	public function fetch()
	{
		$data = false;
		if (!is_array($this->resultset)) {
			$this->fetchAll();

			if ($this->debugger) {
				$this->debugger->info("Rows returned: {$this->rows}");
			}
		}

		if (is_null($this->resultset)) {
			return false;
		}

		$rowset = key($this->resultset);

		if (is_null($this->data)) {
			$data = current($this->resultset[$rowset]);
		} else {
			$data = next($this->resultset[$rowset]);
			if (!$data) {
				$this->data = null;
				reset($this->resultset[$rowset]);
			}
		}

		if ($data) {
			$this->data($data);
		}

		return $data !== false;
	}

	public function rows()
	{
		return $this->rows;
	}

	public function fetchAll()
	{
		if (is_object($this->statement)) {
			$this->resultset = [];
			do {
				$this->resultset[] = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
			} while ($this->_nextRowset());
			reset($this->resultset);
		}

		return $this->resultset;
	}

	public function data(array $data = null)
	{
		if (!is_null($data)) {
			$this->data = $data;
			return $this;
		}

		return $this->data;
	}

	public function set($field, $value)
	{
		if (is_null($this->data)) {
			$this->data = [];
		}

		$this->data[$field] = $value;

		return $this;
	}

	public function get($field)
	{
		return isset($this->data[$field]) ? $this->data[$field] : null;
	}

	public function getJSON($field, $asArray = false)
	{
		$data = $this->get($field);
		return $data ? json_decode($data, $asArray) : false;
	}

	public function lastInsertId()
	{
		return static::$connection ? static::$connection->lastInsertId() : false;
	}

	public static function dbType()
	{
		return is_array(static::$config) ? static::$config['db-type'] : null;
	}

	public function insert()
	{
		return new insert($this);
	}

	public function update()
	{
		return new update($this);
	}

	public function delete()
	{
		return new delete($this);
	}

	public function select($expressions = ['*'])
	{
		return new select($this, $expressions);
	}
}

