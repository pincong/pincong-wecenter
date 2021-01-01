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

define('IN_ANWSION', TRUE);
define('ENVIRONMENT_PHP_VERSION', '7.0.0');
//define('SYSTEM_LANG', 'en_US');

if (version_compare(PHP_VERSION, ENVIRONMENT_PHP_VERSION, '<'))
{
	die('Error: WinnieCenter requires PHP version ' . ENVIRONMENT_PHP_VERSION . ' or newer');
}

define('START_TIME', microtime(TRUE));
define('TIMESTAMP', time());

if (function_exists('memory_get_usage'))
{
	define('MEMORY_USAGE_START', memory_get_usage());
}

if (!defined('AWS_PATH'))
{
	define('AWS_PATH', dirname(__FILE__) . '/');
}

define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');

@ini_set('display_errors', '0');

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);

define('TEMP_PATH', dirname(dirname(__FILE__)) . '/tmp/');

require_once(ROOT_PATH . 'version.php');
require_once(AWS_PATH . 'functions.inc.php');

array_walk_recursive($_GET, 'remove_invisible_characters');
array_walk_recursive($_POST, 'remove_invisible_characters');
array_walk_recursive($_COOKIE, 'remove_invisible_characters');
array_walk_recursive($_REQUEST, 'remove_invisible_characters');

if (@ini_get('register_globals'))
{
	if ($_REQUEST)
	{
		foreach ($_REQUEST AS $name => $value)
		{
			unset($$name);
		}
	}

	if ($_COOKIE)
	{
		foreach ($_COOKIE AS $name => $value)
		{
			unset($$name);
		}
	}
}

require_once(AWS_PATH . 'functions.app.php');

if (file_exists(AWS_PATH . 'config.inc.php'))
{
	require_once(AWS_PATH . 'config.inc.php');
}

load_class('core_autoload');

date_default_timezone_set('UTC');
