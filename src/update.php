<?php

namespace database;

class update
{
	private $model;
	private $table;
	private $originalTable;
	private $ignore = false;
	private $data = [];
	private $where = [];

	public function __construct(model $model)
	{
		$this->model = $model;
		$this->originalTable = preg_replace('<^.*\\\>', '', get_class($model));
		$this->table($this->originalTable);
	}

	public function __toString()
	{
		$sql = '';

		if (empty($this->data)) {
			return $sql;
		}

		$sql = 'UPDATE ';
		if ($this->ignore) {
			$sql .= 'IGNORE ';
		}

		$sql .= "`{$this->table}` SET ";
		$sql .= implode(', ', $this->data);

		if (!empty($this->where)) {
			foreach ($this->where as $index => $where) {
				$sql .= ($index === 0 ? ' WHERE ' : " {$where['type']} ");
				$sql .= $where['condition'];
			}
		}

		return $sql;
	}

	public function reset()
	{
		$this->data = [];
		$this->table($this->originalTable);
		$this->ignore = false;
		return $this;
	}

	public function table($name)
	{
		if (!empty($name)) {
			$this->table = (string) $name;
		}
		return $this;
	}

	public function ignore()
	{
		$this->ignore = true;
		return $this;
	}

	public function field($name, $value, $quote = true)
	{
		$this->data[] = "`{$name}` = " . ($quote ? $this->model->quote($value) : $value);
		return $this;
	}

	public function data(array $data)
	{
		foreach ($data as $field => $value) {
			$this->field($field, $value);
		}
		return $this;
	}

	public function where($condition, $type = 'AND')
	{
		$this->where[] = [
			'type' => $type,
			'condition' => $condition,
		];
		return $this;
	}

	public function andWhere($condition)
	{
		return $this->where($condition, 'AND');
	}

	public function orWhere($condition)
	{
		return $this->where($condition, 'OR');
	}
}
