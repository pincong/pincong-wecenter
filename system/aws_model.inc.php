<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package		WeCenter Framework
 * @author		WeCenter Dev Team
 * @copyright	Copyright (c) 2011 - 2014, WeCenter, Inc.
 * @license		http://www.wecenter.com/license/
 * @link		http://www.wecenter.com/
 * @since		Version 1.0
 * @filesource
 */

/**
 * WeCenter 数据库操作类
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */
class AWS_MODEL
{
	private $_prefix;
	private $_debug;

	private $_fetch_page_table;
	private $_fetch_page_where;

	public function __construct()
	{
		$this->_prefix = AWS_APP::config()->get('database')->prefix;
		$this->_debug = !!AWS_APP::config()->get('system')->debug;

		$this->setup();
	}

	private function _db_error($sql, $e)
	{
		show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage());
	}

	private function _build_where($array, &$prepared_values)
	{
		if (!isset($array))
		{
			return null;
		}
		if (is_string($array))
		{
			return $array;
		}
		$where = load_class('Services_WhereBuilder')->build($array, $prepared_values);
		if ($where === false)
		{
			throw new Zend_Exception('Error while building WHERE clause.');
		}
		return $where;
	}

	public function setup()
	{}

	public function model($model)
	{
		return AWS_APP::model($model);
	}

	public function get_table($name)
	{
		return $this->_prefix . $name;
	}


	public function insert($table, $data)
	{
		$pdo = AWS_APP::db()->master();

		$sql = 'INSERT INTO `' . $this->get_table($table) . '` ';

		if (is_array($data))
		{
			$sql .= '(`' . implode('`, `', array_keys($data)) . '`) ';
			$sql .= 'VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')';
			$prepared_values = array_values($data);
		}
		else
		{
			$sql .= $data;
			$prepared_values = null;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$success = $stmt->execute($prepared_values);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		if (!$success)
		{
			return false;
		}
		return $pdo->lastInsertId();
	}


	public function update($table, $data, $where)
	{
		$pdo = AWS_APP::db()->master();

		$sql = 'UPDATE `' . $this->get_table($table) . '` SET ';

		if (is_array($data))
		{
			$sql .= '`' . implode('`= ?, `', array_keys($data)) . '` = ?';
			$prepared_values = array_values($data);
		}
		else
		{
			$sql .= $data;
			$prepared_values = null;
		}

		$where = $this->_build_where($where, $prepared_values);
		if (!$where)
		{
			throw new Zend_Exception('Missing WHERE clause.');
		}
		$sql .= ' WHERE ' . $where;

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$success = $stmt->execute($prepared_values);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $success;
	}


	public function delete($table, $where)
	{
		$pdo = AWS_APP::db()->master();

		$sql = 'DELETE FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if (!$where)
		{
			throw new Zend_Exception('Missing WHERE clause.');
		}
		$sql .= ' WHERE ' . $where;

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$success = $stmt->execute($prepared_values);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $success;
	}


	public function execute($sql, $prepared_values = null)
	{
		$pdo = AWS_APP::db()->master();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$success = $stmt->execute($prepared_values);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $success;
	}


	public function query_all($sql, $prepared_values = null)
	{
		$pdo = AWS_APP::db()->slave();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function fetch_all($table, $where = null, $order_by = null, $page = null, $per_page = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT * FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($order_by)
		{
			$sql .= ' ORDER BY ' . $order_by;
		}
		$limit = $this->limit_page($page, $per_page);
		if (isset($limit))
		{
			$sql .= ' LIMIT ' . $limit;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function fetch_rows($table, $columns, $where = null, $order_by = null, $page = null, $per_page = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT ';
		if (is_array($columns))
		{
			$sql .= '`' . implode('`, `', $columns) . '`';
		}
		else
		{
			$sql .= $columns;
		}
		$sql .= ' FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($order_by)
		{
			$sql .= ' ORDER BY ' . $order_by;
		}
		$limit = $this->limit_page($page, $per_page);
		if (isset($limit))
		{
			$sql .= ' LIMIT ' . $limit;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function fetch_column($table, $column, $where = null, $order_by = null, $page = null, $per_page = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT `' . $column . '` FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($order_by)
		{
			$sql .= ' ORDER BY ' . $order_by;
		}
		$limit = $this->limit_page($page, $per_page);
		if (isset($limit))
		{
			$sql .= ' LIMIT ' . $limit;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function fetch_row($table, $where = null, $order_by = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT * FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($order_by)
		{
			$sql .= ' ORDER BY ' . $order_by;
		}

		$sql .= ' LIMIT 1';

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function fetch_one($table, $column, $where = null, $order_by = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT `' . $column . '` FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($order_by)
		{
			$sql .= ' ORDER BY ' . $order_by;
		}

		$sql .= ' LIMIT 1';

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetch(PDO::FETCH_COLUMN);
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function count($table, $where = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT COUNT(*) AS `n` FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetchColumn();
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function sum($table, $column, $where = null)
	{
		$pdo = AWS_APP::db()->slave();

		$sql = 'SELECT SUM(`' . $column . '`) AS `n` FROM `' . $this->get_table($table) . '`';

		$where = $this->_build_where($where, $prepared_values);
		if ($where)
		{
			$sql .= ' WHERE ' . $where;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($prepared_values);
			$result = $stmt->fetchColumn();
		} catch (Exception $e) {
			$this->_db_error($sql, $e);
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}


	public function fetch_page($table, $where = null, $order_by = null, $page = null, $per_page = null)
	{
		$this->_fetch_page_table = $table;
		$this->_fetch_page_where = $where;

		return $this->fetch_all($table, $where, $order_by, $page, $per_page);
	}


	public function total_rows($rows_cache = true)
	{
		if (!$this->_fetch_page_table)
		{
			return 0;
		}

		if ($rows_cache)
		{
			$cache_key = 'db_rows_cache_' . md5($this->_fetch_page_table . '_' . serialize($this->_fetch_page_where));

			$db_found_rows = AWS_APP::cache()->get($cache_key);
		}

		if (!$db_found_rows AND $db_found_rows !== 0)
		{
			$db_found_rows = $this->count($this->_fetch_page_table, $this->_fetch_page_where);
		}

		if ($rows_cache AND $db_found_rows)
		{
			AWS_APP::cache()->set($cache_key, $db_found_rows, S::get('cache_level_high'));
		}

		return $db_found_rows;
	}


	public function quote($string)
	{
		$pdo = AWS_APP::db()->master();
		return $pdo->quote($string);
	}

	function limit_page($page, $per_page)
	{
		if (!isset($per_page))
		{
			if (!isset($page))
			{
				return null;
			}
			$limit = intval($page);
			if ($limit < 0)
			{
				$limit = 0;
			}
			return $limit;
		}

		$page = intval($page);
		if ($page < 1)
		{
			$page = 1;
		}
		$per_page = intval($per_page);
		if ($per_page < 0)
		{
			$per_page = 0;
		}
		return (($page - 1) * $per_page) . ', ' . ($per_page);
	}

}
