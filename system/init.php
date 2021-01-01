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

@ini_set('default_charset', 'UTF-8');

define('IN_ANWSION', TRUE);
define('ENVIRONMENT_PHP_VERSION', '7.0.0');

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

require_once(AWS_PATH . 'config.inc.php');
if (!defined('G_ERROR_REPORTING'))
{
	define('G_ERROR_REPORTING', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);
}
error_reporting(G_ERROR_REPORTING);

require_once(ROOT_PATH . 'version.php');
require_once(AWS_PATH . 'functions.inc.php');
require_once(AWS_PATH . 'functions.app.php');

load_class('core_autoload');

date_default_timezone_set('UTC');

require_once (AWS_PATH . 'aws_app.inc.php');
require_once (AWS_PATH . 'aws_controller.inc.php');
require_once (AWS_PATH . 'aws_model.inc.php');

AWS_APP::run();
