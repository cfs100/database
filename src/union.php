<?php

namespace database;

class union
{
	protected $model;
	protected $prev;
	protected $next;

	public function __construct(model $model, select $select)
	{
		$this->model = $model;
		$this->prev = $select;
	}

	public function __toString()
	{
		return $this->next->asString(true);
	}

	public function select(array $expressions = ['*'])
	{
		return $this->next =
			$this->model
				->select($expressions)
				->setPrevUnion($this->prev);
	}
}
