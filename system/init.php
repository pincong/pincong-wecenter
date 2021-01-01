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

define('AWS_PATH', dirname(__FILE__) . '/');
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
define('TEMP_PATH', dirname(dirname(__FILE__)) . '/tmp/');

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);

require_once(AWS_PATH . 'config.inc.php');
require_once(ROOT_PATH . 'version.php');
require_once(AWS_PATH . 'functions.inc.php');
require_once(AWS_PATH . 'functions.app.php');

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

load_class('core_autoload');

date_default_timezone_set('UTC');

if (defined('G_GZIP_COMPRESS') AND G_GZIP_COMPRESS === TRUE)
{
	if (@ini_get('zlib.output_compression') == FALSE)
	{
		if (extension_loaded('zlib'))
		{
			if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
			{
				ob_start('ob_gzhandler');
			}
		}
	}
}

require_once (AWS_PATH . 'aws_app.inc.php');
require_once (AWS_PATH . 'aws_controller.inc.php');
require_once (AWS_PATH . 'aws_model.inc.php');

AWS_APP::run();
