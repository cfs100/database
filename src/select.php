<?php

namespace database;

class select
{
	private $model;
	private $table;
	private $originalTable;
	private $flags = [];
	private $expressions = [];
	private $where = [];
	private $join = [];
	private $group = [];
	private $having = [];
	private $order = [];
	private $limit = null;

	public function __construct(model $model, $expressions = ['*'])
	{
		foreach ($expressions as $expression) {
			$this->field($expression);
		}
		$this->model = $model;
		$this->originalTable = preg_replace('<^.*\\\>', '', get_class($model));
		$this->from($this->originalTable);
	}

	public function __toString()
	{
		$sql = '';

		if (empty($this->expressions) || empty($this->table)) {
			return $sql;
		}

		$sql = 'SELECT ';

		if ($this->flags) {
			$sql .= implode(', ', $this->flags) . ' ';
		}

		foreach ($this->expressions as $index => $expression) {
			$sql .= $index !== 0 ? ', ' : null;

			$aux = $this->identifier($expression);
			$sql .= $aux !== $expression ? "{$this->identifier($this->table)}.{$aux}" : $aux;
		}

		$sql .= " FROM {$this->identifier($this->table)}";

		if (!empty($this->join)) {
			foreach ($this->join as $join) {
				if ($join['type']) {
					$sql .= " {$join['type']}";
				}
				$sql .= " JOIN {$this->identifier($join['table'])}";
				if ($join['condition']) {
					$sql .= " ON {$join['condition']}";
				}
			}
		}

		if (!empty($this->where)) {
			foreach ($this->where as $index => $where) {
				$sql .= ($index === 0 ? ' WHERE ' : " {$where['type']} ");
				$sql .= $where['condition'];
			}
		}

		if (!empty($this->group)) {
			foreach ($this->group as $index => $group) {
				$sql .= ($index === 0 ? ' GROUP BY ' : ', ');
				$sql .= $this->identifier($group);
			}

			foreach ($this->having as $index => $having) {
				$sql .= ($index === 0 ? ' HAVING ' : " {$having['type']} ");
				$sql .= $having['condition'];
			}
		}

		if (!empty($this->order)) {
			foreach ($this->order as $index => $order) {
				$sql .= $index === 0 ? ' ORDER BY ' : ', ';
				$sql .= "{$this->identifier($order['by'])} {$order['type']}";
			}
		}

		if (!is_null($this->limit)) {
			$sql .= " LIMIT {$this->limit}";
		}

		return $sql;
	}

	public function reset()
	{
		$this->data = [];
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

	public function field($expression)
	{
		$this->expressions[] = $expression;
		return $this;
	}

	public function flag($name)
	{
		$this->flags[] = $name;
		return $this;
	}

	public function join($table, $condition = null, array $fields = ['*'], $type = null)
	{
		$this->join[] = [
			'table' => $this->identifier($table),
			'condition' => $condition,
			'type' => $type,
		];

		foreach ($fields as $field) {
			$this->field("{$this->identifier($table)}.{$this->identifier($field)}");
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

	public function order($by, $type = 'ASC')
	{
		$this->order[] = [
			'by' => $by,
			'type' => $type,
		];
		return $this;
	}

	public function group($by)
	{
		$this->group[] = $by;
		return $this;
	}

	public function having($condition, $type = 'AND')
	{
		$this->having[] = [
			'type' => $type,
			'condition' => $condition,
		];
		return $this;
	}

	public function andHaving($condition)
	{
		return $this->having($condition, 'AND');
	}

	public function orHaving($condition)
	{
		return $this->having($condition, 'OR');
	}

	public function limit($quantity, $offset = null)
	{
		$this->limit = (integer) $quantity;

		if (!is_null($offset)) {
			$this->limit = ((integer) $offset) . ", {$this->limit}";
		}

		return $this;
	}

	protected function identifier($name)
	{
		return preg_match('<^[a-z0-9$_]+$>i', $name) ? "`{$name}`" : $name;
	}

	public function execute()
	{
		return $this->model->query($this);
	}
}