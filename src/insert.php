<?php

namespace database;

class insert
{
	private $model;
	private $table;
	private $originalTable;
	private $replace = false;
	private $ignore = false;
	private $data = [];

	public function __construct(model $model)
	{
		$this->model = $model;
		$this->originalTable = preg_replace('<^.*\\\>', '', get_class($model));
		$this->into($this->originalTable);
	}

	public function __toString()
	{
		$sql = '';

		if (empty($this->data)) {
			return $sql;
		}

		if ($this->replace) {
			$sql = 'REPLACE ';
		} else {
			$sql = 'INSERT ';
			if ($this->ignore) {
				$sql .= 'IGNORE ';
			}
		}

		$sql .= 'INTO ';
		$sql .= model::$crass ? "`{$this->table}`" : $this->table;
		$sql .= ' (';
		$sql .= implode(', ', array_keys($this->data));
		$sql .= ') VALUES (';
		$sql .= implode(', ', $this->data);
		$sql .= ')';
		return $sql;
	}

	public function reset()
	{
		$this->data = [];
		$this->into($this->originalTable);
		$this->replace = false;
		$this->ignore = false;
		return $this;
	}

	public function into($name)
	{
		if (!empty($name)) {
			$this->table = (string) $name;
		}
		return $this;
	}

	public function replace()
	{
		$this->replace = true;
		return $this;
	}

	public function ignore()
	{
		$this->ignore = true;
		return $this;
	}

	public function field($name, $value, $quote = true)
	{
		$this->data[(model::$crass ? "`{$name}`" : $name)] = $quote ? $this->model->quote($value) : $value;
		return $this;
	}

	public function data(array $data)
	{
		foreach ($data as $field => $value) {
			$this->field($field, $value);
		}
		return $this;
	}

	public function execute()
	{
		return $this->model->exec($this);
	}
}
