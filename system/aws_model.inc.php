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
	private $_slave_db_available;
	private $_current_db = 'master';

	private $_fetch_page_table;
	private $_fetch_page_where;

	public function __construct()
	{
		$this->_prefix = AWS_APP::config()->get('database')->prefix;
		$this->_debug = !!AWS_APP::config()->get('system')->debug;
		$this->_slave_db_available = !!AWS_APP::config()->get('database')->slave;

		$this->setup();
	}

	public function setup()
	{}

	public function model($model)
	{
		return AWS_APP::model($model);
	}

	/**
	 * 获取表前缀
	 */
	public function get_prefix()
	{
		return $this->_prefix;
	}

	/**
	 * 获取表名
	 *
	 * 直接写 SQL 的时候要用这个函数, 外部程序使用 get_table() 方法
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function get_table($name)
	{
		return $this->_prefix . $name;
	}

	/**
	 * 获取系统 DB 类
	 *
	 * 此功能基于 Zend_DB 类库
	 *
	 * @return	object
	 */
	public function db()
	{
		return AWS_APP::db($this->_current_db);
	}

	/**
	 * 切换到主数据库
	 *
	 * 此功能用于数据库读写分离
	 *
	 * @return	object
	 */
	public function master()
	{
		if ($this->_current_db == 'master')
		{
			return $this;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		AWS_APP::db('master');

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Master DB Seleted');
		}

		return $this;
	}

	/**
	 * 切换到从数据库
	 *
	 * 此功能用于数据库读写分离
	 *
	 * @return	object
	 */
	public function slave()
	{
		if (!$this->_slave_db_available OR $this->_current_db == 'slave')
		{
			return $this;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		AWS_APP::db('slave');

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Slave DB Seleted');
		}

		return $this;
	}

	/**
	 * 插入数据
	 *
	 * 面向对象数据库操作, 表名无需加表前缀, 数据也无需使用 $this->escape 进行过滤
	 *
	 * @param	string
	 * @param	array
	 * @return	int
	 */
	public function insert($table, $data)
	{
		$this->master();

		foreach ($data AS $key => $val)
		{
			$debug_data['`' . $key . '`'] = "'" . $this->escape($val) . "'";
		}

		$sql = 'INSERT INTO `' . $this->get_table($table) . '` (' . implode(', ', array_keys($debug_data)) . ') VALUES (' . implode(', ', $debug_data) . ')';

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$rows_affected = $this->db()->insert($this->get_table($table), $data);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		$last_insert_id = $this->db()->lastInsertId();

		return $last_insert_id;
	}

	/**
	 * 更新数据
	 *
	 * 面向对象数据库操作, 表名无需加表前缀, 数据也无需使用 $this->escape 进行过滤 ($where 条件除外)
	 *
	 * @param	string
	 * @param	array
	 * @param	string
	 * @return	int
	 */
	public function update($table, $data, $where)
	{
		$this->master();

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if (!$where)
		{
			throw new Zend_Exception('DB Update no where string.');
		}

		if ($data)
		{
			foreach ($data AS $key => $val)
			{
				$update_string[] = '`' . $key . "` = '" . $this->escape($val) . "'";
			}
		}

		$sql = 'UPDATE `' . $this->get_table($table) . '` SET ' . implode(', ', $update_string) . ' WHERE ' . $where;

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$rows_affected = $this->db()->update($this->get_table($table), $data, $where);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $rows_affected;
	}

	/**
	 * 删除数据
	 *
	 * 面向对象数据库操作
	 *
	 * @param	string
	 * @param	string
	 * @return	int
	 */
	public function delete($table, $where)
	{
		$this->master();

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if (!$where)
		{
			throw new Exception('DB Delete no where string.');
		}

		$sql = 'DELETE FROM `' . $this->get_table($table) . '` WHERE ' . $where;

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$rows_affected = $this->db()->delete($this->get_table($table), $where);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $rows_affected;
	}

	/**
	 * Zend DB Select 对象别名
	 *
	 * @return	object
	 */
	public function select()
	{
		$this->slave();

		return $this->db()->select();
	}

	/**
	 * 获取查询全部数组数据
	 *
	 * 面向对象数据库操作, 查询结果返回数组
	 *
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	integer
	 * @param	integer
	 * @return	array
	 */
	public function fetch_all($table, $where = null, $order = null, $limit = null, $offset = 0)
	{
		$this->slave();

		$select = $this->select();

		$select->from($this->get_table($table), '*');

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if ($where)
		{
			$select->where($where);
		}

		if ($order)
		{
			if (strstr($order, ','))
			{
				$all_order = explode(',', $order);

				foreach ($all_order AS $current_order)
				{
					$select->order($current_order);
				}
			}
			else
			{
				$select->order($order);
			}
		}

		if ($limit)
		{
			if (strstr($limit, ','))
			{
				$limit = explode(',', $limit);

				$select->limit(intval($limit[1]), intval($limit[0]));
			}
			else if ($offset)
			{
				$select->limit($limit, $offset);
			}
			else
			{
				$select->limit($limit);
			}
		}

		$sql = $select->__toString();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchAll($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}

	/**
	 * 执行 SQL 语句
	 *
	 * 执行 SQL 语句, 表名要使用 get_table 函数获取, 外来数据要使用 $this->escape() 过滤
	 *
	 * @param	string
	 * @param	integer
	 * @param	integer
	 * @param	string
	 * @return	boolean
	 */
	public function query($sql)
	{
		$this->slave();

		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->query($sql);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}

	/**
	 * 查询全部数据, 返回数组
	 *
	 * 执行 SQL 语句, 表名要使用 get_table 函数获取, 外来数据要使用 $this->escape() 过滤
	 *
	 * @param	string
	 * @param	integer
	 * @param	integer
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	public function query_all($sql, $limit = null, $offset = null)
	{
		$this->slave();

		if (!$sql)
		{
			throw new Exception('Query was empty.');
		}

		if ($limit)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		if ($offset)
		{
			$sql .= ' OFFSET ' . $offset;
		}

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchAll($sql);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}

	/**
	 * 获取上一次查询中的全部 ROWS
	 *
	 * 此函数需配合 $this->fetch_page() 使用
	 *
	 * @return	int
	 */
	public function total_rows($rows_cache = true)
	{
		if (!$this->_fetch_page_table)
		{
			return 0;
		}

		if ($rows_cache)
		{
			$cache_key = 'db_rows_cache_' . md5($this->_fetch_page_table . '_' . $this->_fetch_page_where);

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

	/**
	 * 获取查询全部数组数据, 并记录匹配记录总数
	 *
	 * 面向对象数据库操作, 查询结果返回数组, 此函数适用于需要分页的场景使用, 配合 $this->total_rows() 获取匹配记录总数
	 *
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	integer
	 * @param	integer
	 * @param	boolean
	 * @return	array
	 */
	public function fetch_page($table, $where = null, $order = null, $page = null, $limit = 10)
	{
		$this->slave();

		$select = $this->select();

		$select->from($this->get_table($table), '*');
		//$select->from($this->get_table($table), array(new Zend_Db_Expr('SQL_CALC_FOUND_ROWS *')));

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if ($where)
		{
			$select->where($where);
		}

		if ($order)
		{
			if (strstr($order, ','))
			{
				if ($all_order = explode(',', $order))
				{
					foreach ($all_order AS $current_order)
					{
						$select->order($current_order);
					}
				}
			}
			else
			{
				$select->order($order);
			}
		}

		if (!$page)
		{
			$page = 1;
		}

		if ($limit)
		{
			$select->limitPage($page, $limit);
		}

		$sql = $select->__toString();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchAll($select);
		} catch (Exception $e) {

			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		$this->_fetch_page_table = $table;
		$this->_fetch_page_where = $where;

		return $result;
	}

	/**
	 * 查询一行数据, 返回数组, key 为 字段名
	 *
	 * query_row 的面向对象方法, 表名无需加表前缀, 数据也无需使用 $this->escape 进行过滤 ($where 条件除外)
	 *
	 * @param	string
	 * @param	string
	 * @return	array
	 */
	public function fetch_row($table, $where = null, $order = null)
	{
		$this->slave();

		$select = $this->select();

		$select->from($this->get_table($table), '*');

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if ($where)
		{
			$select->where($where);
		}

		if ($order)
		{
			if (strstr($order, ','))
			{
				if ($all_order = explode(',', $order))
				{
					foreach ($all_order AS $current_order)
					{
						$select->order($current_order);
					}
				}
			}
			else
			{
				$select->order($order);
			}
		}

		$select->limit(1, 0);

		$sql = $select->__toString();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}

	/**
	 * 查询单字段, 直接返回数据
	 *
	 * 面向对象数据库操作, 表名无需加表前缀, 数据也无需使用 $this->escape 进行过滤 ($where 条件除外)
	 *
	 * @param	string
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	mixed
	 */
	public function fetch_one($table, $column, $where = null, $order = null)
	{
		$this->slave();

		$select = $this->select();

		$select->from($this->get_table($table), $column);

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if ($where)
		{
			$select->where($where);
		}

		if ($order)
		{
			if (strstr($order, ','))
			{
				if ($all_order = explode(',', $order))
				{
					foreach ($all_order AS $current_order)
					{
						$select->order($current_order);
					}
				}
			}
			else
			{
				$select->order($order);
			}
		}

		$select->limit(1, 0);

		$sql = $select->__toString();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchOne($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result;
	}

	/**
	 * 获取记录总数, SELECT COUNT() 方法
	 *
	 * 面向对象数据库操作, 表名无需加表前缀, 数据也无需使用 $this->escape 进行过滤 ($where 条件除外)
	 *
	 * @param	string
	 * @param	string
	 * @return	int
	 */
	public function count($table, $where = null)
	{
		$this->slave();

		$select = $this->select();
		$select->from($this->get_table($table), 'COUNT(*) AS n');

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if ($where)
		{
			$select->where($where);
		}

		$sql = $select->__toString();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return $result['n'];
	}

	/**
	 * 计算字段总和, SELECT SUM() 方法
	 *
	 * 面向对象数据库操作, 表名无需加表前缀, 数据也无需使用 $this->escape 进行过滤 ($where 条件除外)
	 *
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	int
	 */
	public function sum($table, $column, $where = null)
	{
		$this->slave();

		$select = $this->select();
		$select->from($this->get_table($table), 'SUM(' . $column . ') AS n');

		if (is_array($where))
		{
			$where = $this->where($where);
		}

		if ($where)
		{
			$select->where($where);
		}

		$sql = $select->__toString();

		if ($this->_debug)
		{
			$start_time = microtime(TRUE);
		}

		try {
			$result = $this->db()->fetchRow($select);
		} catch (Exception $e) {
			show_error("Database error\n------\n\nSQL: {$sql}\n\nError Message: " . $e->getMessage(), $e->getMessage());
		}

		if ($this->_debug)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), $sql);
		}

		return intval($result['n']);
	}

	/**
	 * 添加引号防止数据库攻击
	 *
	 * 外部提交的数据需要使用此方法进行清理
	 *
	 * @param	string
	 * @return	string
	 */
	public function escape($string)
	{
		$_quote = $this->db()->quote($string);

		if (substr($_quote, 0, 1) == "'")
		{
			$_quote = substr(substr($_quote, 1), 0, -1);
		}

		return $_quote;
	}

	public function where($array)
	{
		$where = load_class('Services_WhereBuilder')->build($array);
		if ($where === false)
		{
			throw new Zend_Exception('Error while building WHERE clause.');
		}
		return $where;
	}

}
