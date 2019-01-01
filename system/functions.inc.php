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
 * WeCenter 系统函数类
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */

/**
 * 获取站点根目录 URL
 *
 * @return string
 */
function base_url()
{
	return rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
}

function base64_current_path()
{
	return '';
}

/**
 * 根据特定规则对数组进行排序
 *
 * 提取多维数组的某个键名，以便把数组转换成一位数组进行排序（注意：不支持下标，否则排序会出错）
 *
 * @param  array
 * @param  string
 * @param  string
 * @return array
 */
function aasort($source_array, $order_field, $sort_type = 'DESC')
{
	if (! is_array($source_array) or sizeof($source_array) == 0)
	{
		return false;
	}

	foreach ($source_array as $array_key => $array_row)
	{
		$sort_array[$array_key] = $array_row[$order_field];
	}

	$sort_func = ($sort_type == 'ASC' ? 'asort' : 'arsort');

	$sort_func($sort_array);

	// 重组数组
	foreach ($sort_array as $key => $val)
	{
		$sorted_array[$key] = $source_array[$key];
	}

	return $sorted_array;
}

/**
 * 检查整型、字符串或数组内的字符串是否为纯数字（十进制数字，不包括负数和小数）
 *
 * @param integer or string or array
 * @return boolean
 */
function is_digits($num)
{
	if (!$num AND $num !== 0 AND $num !== '0')
	{
		return false;
	}

	if (is_array($num))
	{
		foreach ($num AS $val)
		{
			if (!is_digits($val))
			{
				return false;
			}
		}

		return true;
	}

	return Zend_Validate::is($num, 'Digits');
}

if (! function_exists('iconv'))
{
	/**
	 * 系统不开启 iconv 模块时, 自建 iconv(), 使用 MB String 库处理
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string
	 */
	function iconv($from_encoding = 'GBK', $target_encoding = 'UTF-8', $string)
	{
		return convert_encoding($string, $from_encoding, $target_encoding);
	}
}

if (! function_exists('iconv_substr'))
{
	/**
	 * 系统不开启 iconv_substr 模块时, 自建 iconv_substr(), 使用 MB String 库处理
	 *
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  string
	 * @return string
	 */
	function iconv_substr($string, $start, $length, $charset = 'UTF-8')
	{
		return mb_substr($string, $start, $length, $charset);
	}
}

if (! function_exists('iconv_strpos'))
{
	/**
	 * 系统不开启 iconv_substr 模块时, 自建 iconv_strpos(), 使用 MB String 库处理
	 *
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  string
	 * @return string
	 */
	function iconv_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
	{
		return mb_strpos($haystack, $needle, $offset, $charset);
	}
}

/**
 * 兼容性转码
 *
 * 系统转换编码调用此函数, 会自动根据当前环境采用 iconv 或 MB String 处理
 *
 * @param  string
 * @param  string
 * @param  string
 * @return string
 */
function convert_encoding($string, $from_encoding = 'GBK', $target_encoding = 'UTF-8')
{
	if (function_exists('mb_convert_encoding'))
	{
		return mb_convert_encoding($string, str_replace('//IGNORE', '', strtoupper($target_encoding)), $from_encoding);
	}
	else
	{
		if (strtoupper($from_encoding) == 'UTF-16')
		{
			$from_encoding = 'UTF-16BE';
		}

		if (strtoupper($target_encoding) == 'UTF-16')
		{
			$target_encoding = 'UTF-16BE';
		}

		if (strtoupper($target_encoding) == 'GB2312' or strtoupper($target_encoding) == 'GBK')
		{
			$target_encoding .= '//IGNORE';
		}

		return iconv($from_encoding, $target_encoding, $string);
	}
}

/**
 * 兼容性转码 (数组)
 *
 * 系统转换编码调用此函数, 会自动根据当前环境采用 iconv 或 MB String 处理, 支持多维数组转码
 *
 * @param  array
 * @param  string
 * @param  string
 * @return array
 */
function convert_encoding_array($data, $from_encoding = 'GBK', $target_encoding = 'UTF-8')
{
	return eval('return ' . convert_encoding(var_export($data, true) . ';', $from_encoding, $target_encoding));
}

/**
 * 双字节语言版 strpos
 *
 * 使用方法同 strpos()
 *
 * @param  string
 * @param  string
 * @param  int
 * @param  string
 * @return string
 */
function cjk_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
{
	if (function_exists('iconv_strpos'))
	{
		return iconv_strpos($haystack, $needle, $offset, $charset);
	}

	return mb_strpos($haystack, $needle, $offset, $charset);
}

/**
 * 双字节语言版 substr
 *
 * 使用方法同 substr(), $dot 参数为截断后带上的字符串, 一般场景下使用省略号
 *
 * @param  string
 * @param  int
 * @param  int
 * @param  string
 * @param  string
 * @return string
 */
function cjk_substr($string, $start, $length, $charset = 'UTF-8', $dot = '')
{
	if (cjk_strlen($string, $charset) <= $length)
	{
		return $string;
	}

	if (function_exists('mb_substr'))
	{
		return mb_substr($string, $start, $length, $charset) . $dot;
	}
	else
	{
		return iconv_substr($string, $start, $length, $charset) . $dot;
	}
}

/**
 * 双字节语言版 strlen
 *
 * 使用方法同 strlen()
 *
 * @param  string
 * @param  string
 * @return string
 */
function cjk_strlen($string, $charset = 'UTF-8')
{
	if (function_exists('mb_strlen'))
	{
		return mb_strlen($string, $charset);
	}
	else
	{
		return iconv_strlen($string, $charset);
	}
}

/**
 * 递归创建目录
 *
 * 与 mkdir 不同之处在于支持一次性多级创建, 比如 /dir/sub/dir/
 *
 * @param  string
 * @param  int
 * @return boolean
 */
function make_dir($dir, $permission = 0777)
{
	$dir = rtrim($dir, '/') . '/';

	if (is_dir($dir))
	{
		return TRUE;
	}

	if (! make_dir(dirname($dir), $permission))
	{
		return FALSE;
	}

	return @mkdir($dir, $permission);
}

/**
 * jQuery jsonp 调用函数
 *
 * 用法同 json_encode
 *
 * @param  array
 * @param  string
 * @return string
 */
function jsonp_encode($json = array(), $callback = 'jsoncallback')
{
	if ($_GET[$callback])
	{
		return $_GET[$callback] . '(' . json_encode($json) . ')';
	}

	return json_encode($json);
}


function date_friendly($timestamp)
{
	$timestamp = $timestamp + intval(get_setting('time_difference'));

	if (get_setting('time_style') == 'N')
	{
		return date('Y-m-d', $timestamp);
	}

	return date('Y-m-d H:i:s', $timestamp);
}

/**
 * 载入类库, 并实例化、加入队列
 *
 * 路径从 system 开始计算，并遵循 Zend Freamework 路径表示法，即下划线 _ 取代 / , 如 core_config 表示 system/core/config.php
 *
 * @param  string
 * @return object
 */
function &load_class($class)
{
	static $_classes = array();

	// Does the class exist?  If so, we're done...
	if (isset($_classes[$class]))
	{
		return $_classes[$class];
	}

	if (class_exists($class) === FALSE)
	{
		$file = AWS_PATH . preg_replace('#_+#', '/', $class) . '.php';

		if (! file_exists($file))
		{
			throw new Zend_Exception('Unable to locate the specified class: ' . $class . ' ' . preg_replace('#_+#', '/', $class) . '.php');
		}

		require_once $file;
	}

	$_classes[$class] = new $class();

	return $_classes[$class];
}

function _show_error($exception_message)
{
	$name = 'HTTP_HOST'; //strtoupper($_SERVER['HTTP_HOST']);

	if ($exception_message)
	{
		$exception_message = htmlspecialchars($exception_message);

		$errorBlock = "<div style='display:none' id='exception_message'><textarea rows='15' onfocus='this.select()'>{$exception_message}</textarea></div>";
	}

	if (defined('IN_AJAX'))
	{
		return $exception_message;
	}

	return <<<EOF
<!DOCTYPE html><html><head><title>Error</title><style type='text/css'>body{background:#f9f9f9;margin:0;padding:30px 20px;font-family:"Helvetica Neue",helvetica,arial,sans-serif}#error{max-width:800px;background:#fff;margin:0 auto}h1{background:#151515;color:#fff;font-size:22px;font-weight:500;padding:10px}h1 span{color:#7a7a7a;font-size:14px;font-weight:400}#content{padding:20px;line-height:1.6}#reload_button{background:#151515;color:#fff;border:0;line-height:34px;padding:0 15px;font-family:"Helvetica Neue",helvetica,arial,sans-serif;font-size:14px;border-radius:3px}textarea{width:95%;height:300px;font-size:11px;font-family:"Helvetica Neue Ultra Light", Monaco,Lucida Console,Consolas,Courier,Courier New;line-height:16px;color:#474747;border:1px #bbb solid;border-radius:3px;padding:5px;}</style></head><body onkeydown="if (event.keyCode == 68) { document.getElementById('exception_message').style.display = 'block' }"><div id='error'><h1>An error occurred <span>(500 Error)</span></h1><div id='content'>We're sorry, but a temporary technical error has occurred which means we cannot display this site right now.<br /><br />You can try again by clicking the button below, or try again later.<br /><br />{$errorBlock}<br /><button onclick="window.location.reload();" id='reload_button'>Try again</button></div></div></body></html>
EOF;
}

function show_error($exception_message, $error_message = '')
{
	@ob_end_clean();

	if (isset($_SERVER['SERVER_PROTOCOL']) AND strstr($_SERVER['SERVER_PROTOCOL'], '/1.0') !== false)
	{
		header("HTTP/1.0 500 Internal Server Error");
	}
	else
	{
		header("HTTP/1.1 500 Internal Server Error");
	}

	echo _show_error($exception_message);
	exit;
}

/**
 * 获取带表前缀的数据库表名
 *
 * @param  string
 * @return string
 */
function get_table($name)
{
	return AWS_APP::config()->get('database')->prefix . $name;
}

/**
 * 获取全局配置项
 *
 * 如果指定 varname 则返回指定的配置项, 如果不指定 varname 则返回全部配置项
 *
 * @param  string
 * @return mixed
 */
function get_setting($varname = null)
{
	if (! class_exists('AWS_APP', false))
	{
		return false;
	}

	$settings = AWS_APP::$settings;

	if ($varname)
	{
		return $settings[$varname];
	}
	else
	{
		return $settings;
	}
}

/**
 * 获取全局配置项 key-value pairs
 *
 * e.g. "Google google.com\nFacebook facebook.com"
 * return array("Google" => "google.com", "Facebook" => "facebook.com")
 *
 * @param  string
 * @return mixed
 */
function get_key_value_pairs($varname, $separator = ',')
{
	$result = array();

	$rows = explode("\n", get_setting($varname));
	foreach($rows as $row)
	{
		$row = trim($row);
		if (!$row)
		{
			continue;
		}

		$array = explode($separator, $row);
		$count = count($array);
		if ($count < 2)
		{
			continue;
		}

		$result[trim($array[0])] = trim($array[$count - 1]);
	}

	return $result;
}

/**
 * 检查 $content 是否包含 get_setting($varname)
 *
 * 命中返回 true, 未命中返回 false
 *
 * @param  string
 * @param  string
 * @param  boolean    true: 可出现在 $content 的任意位置, false: 只能出现在 $content 的开头
 * @param  boolean
 * @return boolean
 */
function content_contains($varname, $content, $any_position = false, $case_sensitive = false)
{
	if (!$content)
	{
		return false;
	}

	if (!$rows = get_setting($varname))
	{
		return false;
	}

	$rows = explode("\n", $rows);

	foreach($rows AS $row)
	{
		$row = trim($row);

		if (!$row)
		{
			continue;
		}

		// 正则表达式
		if (substr($row, 0, 1) == '{' AND substr($row, -1, 1) == '}')
		{
			if (preg_match(substr($row, 1, -1), $content))
			{
				return true;
			}

			continue;
		}

		if ($case_sensitive)
		{
			$pos = strpos($content, $row);
		}
		else
		{
			$pos = stripos($content, $row);
		}

		if ($any_position AND $pos > 0)
		{
			return true;
		}

		if ($pos === 0)
		{
			return true;
		}
	}

	return false;
}


// ------------------------------------------------------------------------


/**
 * 判断文件或目录是否可写
 *
 * @param  string
 * @return boolean
 */
function is_really_writable($file)
{
	// If we're on a Unix server with safe_mode off we call is_writable
	if (DIRECTORY_SEPARATOR == '/' and @ini_get('safe_mode') == FALSE)
	{
		return is_writable($file);
	}

	// For windows servers and safe_mode "on" installations we'll actually
	// write a file then read it.  Bah...
	if (is_dir($file))
	{
		$file = rtrim($file, '/') . '/is_really_writable_' . md5(rand(1, 100));

		if (! @file_put_contents($file, 'is_really_writable() test file'))
		{
			return FALSE;
		}
		else
		{
			@unlink($file);
		}

		return TRUE;
	}
	else if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
	{
		return FALSE;
	}

	return TRUE;
}

/**
 * 生成密码种子
 *
 * @param  integer
 * @return string
 */
function fetch_salt($length = 8)
{
	for ($i = 0; $i < $length; $i++)
	{
		$salt .= chr(rand(97, 122));
	}

	return $salt;
}

/**
 * 根据 salt 混淆密码
 *
 * @param  string
 * @param  string
 * @return string
 */
function compile_password($password, $salt)
{
	$password = md5(md5($password) . $salt);

	return $password;
}

function bcrypt_password_hash($password)
{
	return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * 伪静态地址转换器
 *
 * @param  string
 * @return string
 */
function get_js_url($url)
{
	if (substr($url, 0, 1) == '/')
	{
		$url = substr($url, 1);

		if (get_setting('url_rewrite_enable') == 'Y' AND $request_routes = get_request_route())
		{
			if (strstr($url, '?'))
			{
				$request_uri = explode('?', $url);

				$query_string = $request_uri[1];

				$url = $request_uri[0];
			}
			else
			{
				unset($query_string);
			}

			foreach ($request_routes as $key => $val)
			{
				if (preg_match('/^' . $val[0] . '$/', $url))
				{
					$url = preg_replace('/^' . $val[0] . '$/', $val[1], $url);

					break;
				}
			}

			if ($query_string)
			{
				$url .= '?' . $query_string;
			}
		}

		$url = base_url() . '/' . ((get_setting('url_rewrite_enable') != 'Y') ? G_INDEX_SCRIPT : '') . $url;
	}

	return $url;
}

/**
 * 用于分页查询 SQL 的 limit 参数生成器
 *
 * @param  int
 * @param  int
 * @return string
 */
function calc_page_limit($page, $per_page)
{
	if (intval($per_page) == 0)
	{
		throw new Zend_Exception('Error param: per_page');
	}

	if ($page < 1)
	{
		$page = 1;
	}

	return ((intval($page) - 1) * intval($per_page)) . ', ' . intval($per_page);
}

/**
 * 将用户登录信息编译成 hash 字符串，用于发送 Cookie
 *
 * @param  string
 * @param  string
 * @param  string
 * @param  integer
 * @param  boolean
 * @return string
 */
function get_login_cookie_hash($user_name, $password, $salt, $uid)
{
	$password = compile_password($password, $salt);

	//$auth_hash_key = AWS_APP::crypt()->new_key(md5(G_COOKIE_HASH_KEY . $_SERVER['HTTP_USER_AGENT']));
	$auth_hash_key = null;

	return AWS_APP::crypt()->encode(json_encode(array(
		'uid' => $uid,
		'user_name' => $user_name,
		'password' => $password
	)), $auth_hash_key);
}

/**
 * 检查队列中是否存在指定的 hash 值, 并移除之, 用于表单提交验证
 *
 * @param  string
 * @return boolean
 */
function valid_post_hash($hash)
{
	return AWS_APP::form()->valid_post_hash($hash);
}

/**
 * 创建一个新的 hash 字符串，并写入 hash 队列, 用于表单提交验证
 *
 * @return string
 */
function new_post_hash()
{
	if (! AWS_APP::session()->client_info)
	{
		return false;
	}

	return AWS_APP::form()->new_post_hash();
}

/**
 * 构造或解析路由规则后得到的请求地址数组
 *
 * 返回二维数组, 二位数组, 每个规则占据一条, 被处理的地址通过下标 0 返回, 处理后的地址通过下标 1 返回
 *
 * @param  boolean
 * @return array
 */
function get_request_route($positive = true)
{
	if (!$route_data = get_setting('request_route_custom'))
	{
		return false;
	}

	if ($request_routes = explode("\n", $route_data))
	{
		$routes = array();

		$replace_array = array("(:any)" => "([^\"'&#\?\/]+[&#\?\/]*[^\"'&#\?\/]*)", "(:num)" => "([0-9]+)");

		foreach ($request_routes as $key => $val)
		{
			$val = trim($val);

			if (!$val)
			{
				continue;
			}

			if ($positive)
			{
				list($pattern, $replace) = explode('===', $val);
			}
			else
			{
				list($replace, $pattern) = explode('===', $val);
			}

			if (substr($pattern, 0, 1) == '/' and $pattern != '/')
			{
				$pattern = substr($pattern, 1);
			}

			if (substr($replace, 0, 1) == '/' and $replace != '/')
			{
				$replace = substr($replace, 1);
			}

			$pattern = addcslashes($pattern, "/\.?");

			$pattern = str_replace(array_keys($replace_array), array_values($replace_array), $pattern);

			$replace = str_replace(array_keys($replace_array), "\$1", $replace);

			$routes[] = array($pattern, $replace);
		}

		return $routes;
	}
}

/**
 * 删除 UBB 标识码
 *
 * @param  string
 * @return string
 */
function strip_ubb($str)
{
	$str = preg_replace('/\[[^\]]+\](http[s]?:\/\/[^\[]*)\[\/[^\]]+\]/', ' $1 ', $str);

	$pattern = '/\[[^\]]+\]([^\[]*)\[\/[^\]]+\]/';
	$replacement = ' $1 ';
	return preg_replace($pattern, $replacement, preg_replace($pattern, $replacement, $str));
}

/**
 * 获取数组中随机一条数据
 *
 * @param  array
 * @return mixed
 */
function array_random($arr)
{
	shuffle($arr);

	return end($arr);
}

/**
 * 获得二维数据中第二维指定键对应的值，并组成新数组 (不支持二维数组)
 *
 * @param  array
 * @param  string
 * @return array
 */
function fetch_array_value($array, $key)
{
	if (!$array || ! is_array($array))
	{
		return array();
	}

	$data = array();

	foreach ($array as $_key => $val)
	{
		$data[] = $val[$key];
	}

	return $data;
}

/**
 * 强制转换字符串为整型, 对数字或数字字符串无效
 *
 * @param  mixed
 */
function intval_string(&$value)
{
	if (! is_numeric($value))
	{
		$value = intval($value);
	}
}

/**
 * 获取时差
 *
 * @return string
 */
function get_time_zone()
{
	$time_zone = 0 + (date('O') / 100);

	if ($time_zone == 0)
	{
		return '';
	}

	if ($time_zone > 0)
	{
		return '+' . $time_zone;
	}

	return $time_zone;
}

/**
 * 格式化输出相应的语言
 *
 * 根据语言包中数组键名的下标获取对应的翻译字符串
 *
 * @param  string
 * @param  string
 */
function _e($string, $replace = null)
{
	if (!class_exists('AWS_APP', false))
	{
		echo load_class('core_lang')->translate($string, $replace, TRUE);
	}
	else
	{
		echo AWS_APP::lang()->translate($string, $replace, TRUE);
	}
}

/**
 * 递归读取文件夹的文件列表
 *
 * 读取的目录路径可以是相对路径, 也可以是绝对路径, $file_type 为指定读取的文件后缀, 不设置则读取文件夹内所有的文件
 *
 * @param  string
 * @param  string
 * @return array
 */
function fetch_file_lists($dir, $file_type = null)
{
	if ($file_type)
	{
		if (substr($file_type, 0, 1) == '.')
		{
			$file_type = substr($file_type, 1);
		}
	}

	$base_dir = realpath($dir);

	if (!file_exists($base_dir))
	{
		return false;
	}

	$dir_handle = opendir($base_dir);

	$files_list = array();

	while (($file = readdir($dir_handle)) !== false)
	{
		if (substr($file, 0, 1) != '.' AND !is_dir($base_dir . '/' . $file))
		{
			if (($file_type AND H::get_file_ext($file, false) == $file_type) OR !$file_type)
			{
				$files_list[] = $base_dir . '/' . $file;
			}
		}
		else if (substr($file, 0, 1) != '.' AND is_dir($base_dir . '/' . $file))
		{
			if ($sub_dir_lists = fetch_file_lists($base_dir . '/' . $file, $file_type))
			{
				$files_list = array_merge($files_list, $sub_dir_lists);
			}
		}
	}

	return $files_list;
}


/**
 * 删除网页上看不见的隐藏字符串, 如 Java\0script
 *
 * @param	string
 */
function remove_invisible_characters(&$str, $url_encoded = TRUE)
{
	$non_displayables = array();

	// every control character except newline (dec 10)
	// carriage return (dec 13), and horizontal tab (dec 09)

	if ($url_encoded)
	{
		$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
		$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
	}

	$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

	do
	{
		$str = preg_replace($non_displayables, '', $str, -1, $count);
	}
	while ($count);
}

/**
 * 生成一段时间的月份列表
 *
 * @param string
 * @param string
 * @param string
 * @param string
 * @return array
 */
function get_month_list($timestamp1, $timestamp2, $year_format = 'Y', $month_format = 'm')
{
	$yearsyn = date($year_format, $timestamp1);
	$monthsyn = date($month_format, $timestamp1);
	$daysyn = date('d', $timestamp1);

	$yearnow = date($year_format, $timestamp2);
	$monthnow = date($month_format, $timestamp2);
	$daynow = date('d', $timestamp2);

	if ($yearsyn == $yearnow)
	{
		$monthinterval = $monthnow - $monthsyn;
	}
	else if ($yearsyn < $yearnow)
	{
		$yearinterval = $yearnow - $yearsyn -1;
		$monthinterval = (12 - $monthsyn + $monthnow) + 12 * $yearinterval;
	}

	$timedata = array();
	for ($i = 0; $i <= $monthinterval; $i++)
	{
		$tmptime = mktime(0, 0, 0, $monthsyn + $i, 1, $yearsyn);
		$timedata[$i]['year'] = date($year_format, $tmptime);
		$timedata[$i]['month'] = date($month_format, $tmptime);
		$timedata[$i]['beginday'] = '01';
		$timedata[$i]['endday'] = date('t', $tmptime);
	}

	$timedata[0]['beginday'] = $daysyn;
	$timedata[$monthinterval]['endday'] = $daynow;

	unset($tmptime);

	return $timedata;
}

function array_key_sort_asc_callback($a, $b)
{
	if ($a['sort'] == $b['sort'])
	{
		return 0;
	}

	return ($a['sort'] < $b['sort']) ? -1 : 1;
}

function get_random_filename($dir, $file_ext)
{
	if (!$dir OR !file_exists($dir))
	{
		return false;
	}

	$dir = rtrim($dir, '/') . '/';

	$filename = md5(mt_rand(1, 99999999) . microtime());

	if (file_exists($dir . $filename . '.' . $file_ext))
	{
		return get_random_filename($dir, $file_ext);
	}

	return $filename . '.' . $file_ext;
}

function check_extension_package($package)
{
	if (!file_exists(ROOT_PATH . 'models/' . $package . '.php'))
	{
		return false;
	}

	return true;
}

function get_left_days($timestamp)
{
	$left_days = intval(($timestamp - time()) / (3600 * 24));

	if ($left_days < 0)
	{
		$left_days = 0;
	}

	return $left_days;
}

function get_paid_progress_bar($amount, $paid)
{
	if ($amount == 0)
	{
		return 0;
	}

	return intval(($paid / $amount) * 100);
}


function uniqid_generate($length = 16)
{
	return substr(strtolower(md5(uniqid(rand()))), 0, $length);
}


function real_time()
{
    return time();
}

function fake_time($timestamp = 0)
{
	if (!$timestamp)
	{
		$timestamp = time();
	}

	if (get_setting('time_blurring') == 'N')
	{
		return $timestamp;
	}
	$min = intval(get_setting('random_seconds_min'));
	$max = intval(get_setting('random_seconds_max'));
	return intval($timestamp / 86400) * 86400 + rand($min, $max);
}

function my_trim($str)
{
	// trim 不适用于处理多字节字符
	// trim('【BUG】trim 函数处理全角空格会产生 bug 会清除整个字符串', "　");
	// trim('【BUG】trim 函数处理 NBSP(U+00A0) 会产生 bug 你你你你你', " "); // U+00A0 NO-BREAK SPACE

	return trim($str, "\x00..\x20");
}


function rand_minmax($min, $max, $default = 0, $undefined = 0)
{
	$min = intval($min);
	$max = intval($max);
	if ($min == $undefined AND $max == $undefined)
	{
		return $default;
	}

	return rand($min, $max);
}
