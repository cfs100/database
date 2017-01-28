<?php

namespace database;

class paginator
{
	public static $options = [
		'page' => 1,
		'items' => 50,
		'offset' => 0,
	];

	private $model;
	private $config = [];

	private $total;
	private $pages;

	public function __construct(model $model, array $options = [])
	{
		$this->model = $model;
		foreach (static::$options as $key => $default) {
			$this->config[$key] = isset($options[$key]) ? $options[$key] : $default;
		}
	}

	public function model()
	{
		return $this->model;
	}

	public function formatUrl($format, $page)
	{
		if ($page == 1 || !$page) {
			return preg_replace('<[-/]%d>', '', $format);
		}

		return sprintf($format, $page);
	}

	public function page()
	{
		return (integer) $this->config['page'];
	}

	public function pages()
	{
		return (integer) $this->pages;
	}

	public function total()
	{
		return (integer) $this->total + ($this->total ? $this->config['offset'] : 0);
	}

	public function query($sql)
	{
		$this->model->query("SELECT COUNT(*) AS total FROM ($sql) T");
		$this->total = $this->fetch() ? (integer) $this->get('total') : 0;
		$this->pages = (integer) ceil($this->total() / $this->config['items']);

		$page = (integer) $this->config['page'];

		if ($page < 1) {
			$page = 1;
		} elseif ($page > $this->pages()) {
			$page = $this->pages();
		}

		$this->config['page'] = $page;

		$offset = !$this->config['offset'] ? ($page - 1) * $this->config['items'] : 0;

		if ($this->total == 0) {
			return $this;
		}

		return $this->model->query("SELECT * FROM ($sql) T OFFSET {$offset} LIMIT {$this->config['items']}");
	}

	public function __call($name, $arguments)
    {
    	return call_user_func_array([$this->model, $name], $arguments);
    }
}
