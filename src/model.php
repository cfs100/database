<?php

namespace database;

use \PDO;

class model
{
	protected static $connection;

	protected $statement;
	protected $resultset;
	protected $data;
	protected $rows;
	private static $config;

	public function __construct(array $data = null)
	{
		if (!empty($data)) {
			$this->data($data);
		}
	}

	public static function config(array $data)
	{
		static::$config = $data;
	}

	private function connect()
	{
		if (!static::$connection) {
			if (!is_array($config = static::$config)) {
				throw new \BadMethodCallException('Database connection data not set');
			}

			static::$connection = new PDO(
				"{$config['db-type']}:" .
				"dbname={$config['db-name']};" .
				"host={$config['db-host']};" .
				"port={$config['db-port']}"
			, $config['db-user'], $config['db-pass']);
			static::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		}

		return static::$connection;
	}

	public function quote($value)
	{
		switch (gettype($value)) {
			case 'boolean':
				return $value ? 'true' : 'false';
			case 'integer':
			case 'double':
				return $value;
			default:
				return $this->connect()->quote($value);
		}
	}

	public function exec($sql)
	{
		$aux = $this->connect()->exec($sql);
		if ($aux === false) {
			throw new \Exception($this->connect()->errorInfo()[2], 500);
		}
		return $aux;
	}

	public function query($sql)
	{
		$this->statement = $this->reset()->connect()->query($sql);
		if ($this->statement) {
			$this->rows = $this->statement->rowCount();
		} else {
			throw new \Exception($this->connect()->errorInfo()[2], 500);
		}
		return $this;
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
		}

		if (is_null($this->data)) {
			$data = current($this->resultset);
		} else {
			$data = next($this->resultset);
			if (!$data) {
				$this->data = null;
				reset($this->resultset);
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
			$this->resultset = $this->statement->fetchAll();
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

	public function get($field)
	{
		return isset($this->data[$field]) ? $this->data[$field] : null;
	}

	public function getJSON($field, $asArray = false)
	{
		$data = $this->get($field);
		return $data ? json_decode($data, $asArray) : false;
	}
}

