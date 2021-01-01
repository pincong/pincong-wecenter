<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

class core_db
{
	private $dbh_master;
	private $dbh_slave;

	public function __construct()
	{
		$debug_mode = !!load_class('core_config')->get('system')->debug;
		if ($debug_mode)
		{
			$start_time = microtime(TRUE);
		}

		$cfg = load_class('core_config')->get('database');

		if (isset($cfg->charset))
		{
			$cfg->master['charset'] = $cfg->charset;

			if ($cfg->slave)
			{
				$cfg->slave['charset'] = $cfg->charset;
			}
		}

		if (!isset($cfg->master['dsn']))
		{
			$cfg->master['dsn'] = $this->to_dsn($cfg->dbtype ?? null, $cfg->master);
		}

		try
		{
			$this->dbh_master = new PDO($cfg->master['dsn'], $cfg->master['username'], $cfg->master['password'], array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			));
		}
		catch (Exception $e)
		{
			throw new Zend_Exception("Can't connect to master database.");
		}

		if ($debug_mode)
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Connected to Master DB');
		}

		if ($cfg->slave)
		{
			if ($debug_mode)
			{
				$start_time = microtime(TRUE);
			}

			if (!$cfg->slave['dsn'])
			{
				$cfg->slave['dsn'] = $this->to_dsn($cfg->dbtype, $cfg->slave);
			}

			try
			{
				$this->dbh_slave = new PDO($cfg->slave['dsn'], $cfg->slave['username'], $cfg->slave['password'], array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				));
			}
			catch (Exception $e)
			{
				throw new Zend_Exception("Can't connect to slave database.");
			}

			if ($debug_mode)
			{
				AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Connected to Slave DB');
			}
		}
		else
		{
			$this->dbh_slave = $this->dbh_master;
		}
	}

	private function to_dsn($type, $params)
	{
		unset($params['username']);
		unset($params['password']);

		$result = '';
		foreach ($params as $key => $val)
		{
			if (!!$result)
			{
				$result .= ';';
			}
			$result .= $key . '=' .$val;
		}
		if (!$type)
		{
			$type = 'mysql';
		}
		return $type . ':' . $result;
	}

	public function master()
	{
		return $this->dbh_master;
	}

	public function slave()
	{
		return $this->dbh_slave;
	}

	public function getServerVersion()
	{
		try {
			return $this->dbh_master->getAttribute(PDO::ATTR_SERVER_VERSION);
		} catch (PDOException $e) {
			// In case of the driver doesn't support getting attributes
			return null;
		}
	}
}
