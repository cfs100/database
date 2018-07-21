<?php

namespace database;

class delete
{
	protected $model;
	protected $table;
	protected $originalTable;
	protected $ignore = false;
	protected $where = [];

	public function __construct(model $model)
	{
		$this->model = $model;
		$this->originalTable = preg_replace('<^.*\\\>', '', get_class($model));
		$this->from($this->originalTable);
	}

	public function __toString()
	{
		$sql = '';

		$sql = 'DELETE ';
		if ($this->ignore) {
			$sql .= 'IGNORE ';
		}

		$sql .= 'FROM ';
		$sql .= model::$crass ? "`{$this->table}`" : $this->table;

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
		$this->from($this->originalTable);
		$this->ignore = false;
		return $this;
	}

	public function from($name)
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

	public function execute()
	{
		return $this->model->exec($this);
	}
}
