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
 * WeCenter APP 函数类
 *
 * @package		WeCenter
 * @subpackage	App
 * @category	Libraries
 * @author		WeCenter Dev Team
 */

// 防止重复提交
function check_repeat_submission($uid, &$text)
{
	$key = 'repeat_submission_digest_' . intval($uid);
	if ($digest = AWS_APP::cache()->get($key))
	{
		if (md5($text) == $digest)
		{
			return false;
		}
	}
	return true;
}

function set_repeat_submission_digest($uid, &$text)
{
	$key = 'repeat_submission_digest_' . intval($uid);
	AWS_APP::cache()->set($key, md5($text), 86400);
}


// 检查用户操作频率
// 返回 true    正常
// 返回 false   过于频繁
function check_user_operation_interval($op_name, $uid, $interval, $check_default_value = true)
{
	$interval = intval($interval);
	if (!$interval AND $check_default_value)
	{
		$interval = intval(get_setting('user_operation_interval'));
	}
	if ($interval <= 0)
	{
		return true;
	}
	$key = 'user_operation_last_time_' . intval($uid) . '_' . $op_name;
	$last_time = intval(AWS_APP::cache()->get($key));
	if ($last_time + $interval > time())
	{
		return false;
	}
	return true;
}

function set_user_operation_last_time($op_name, $uid)
{
	$key = 'user_operation_last_time_' . intval($uid) . '_' . $op_name;
	AWS_APP::cache()->set($key, time(), 86400);
}

function can_edit_post($post_uid, &$user_info)
{
	if (!$user_info OR !$user_info['uid'])
	{
		return false;
	}
	if ($user_info['permission']['edit_any_post'])
	{
		return true;
	}
	if ($post_uid == $user_info['uid'] AND $user_info['permission']['edit_own_post'])
	{
		return true;
	}
	if (!$user_info['permission']['edit_specific_post'])
	{
		return false;
	}
	static $specific_post_uids;
	if (!isset($specific_post_uids))
	{
		$specific_post_uids = get_setting_array('specific_post_uids');
	}
	if (in_array($post_uid, $specific_post_uids))
	{
		return true;
	}
	return false;
}

// 获取主题图片指定尺寸的完整url地址
function get_topic_pic_url(&$topic_info, $size = 'min')
{
	$all_size = array('min', 'mid', 'max');
	$size = in_array($size, $all_size) ? $size : $all_size[0];

	$default = G_STATIC_URL . '/common/topic-' . $size . '-img.png';

	if (!$topic_info OR is_null($topic_info['topic_pic']))
	{
		return $default;
	}

	$filename = '/topic/' . AWS_APP::model('topic')->get_image_path($topic_info['topic_id'], $size);
	return get_setting('upload_url') . $filename . '?' . $topic_info['topic_pic']; // $topic_info['topic_pic'] 随机字符串用于避免 CDN 缓存
}

function import_editor_static_files()
{
	TPL::import_css('editor/sceditor/themes/square.css');
	TPL::import_js('editor/sceditor/sceditor.js');
	TPL::import_js('editor/sceditor/icons/material.js');
	TPL::import_js('editor/sceditor/formats/bbcode.js');
}


/**
 * 获取站点根目录 URL
 *
 * @return string
 */
function base_url()
{
	static $base_url;
	if (!isset($base_url))
	{
		$base_url = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	}
	return $base_url;
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
function get_setting($varname)
{
	if (!class_exists('AWS_APP', false))
	{
		return false;
	}

	return AWS_APP::$settings[$varname];
}

function get_settings()
{
	if (!class_exists('AWS_APP', false))
	{
		return false;
	}

	return AWS_APP::$settings;
}


function get_setting_array($varname, $separator = ',')
{
	if (!class_exists('AWS_APP', false))
	{
		return false;
	}

	return array_map('trim', explode($separator, AWS_APP::$settings[$varname]));
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
function get_key_value_pairs($varname, $separator = ',', $allow_empty_separator = false)
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

		if (!isset($separator) AND $allow_empty_separator)
		{
			$result[$row] = null;
		}

		$pos = strpos($row, $separator);
		if (!$pos)
		{
			if ($allow_empty_separator AND is_bool($pos))
			{
				$result[$row] = null;
			}
			continue;
		}
		else
		{
			$key = substr($row, 0, $pos);
			$value = substr($row, $pos + strlen($separator));
			$result[trim($key)] = trim($value);
		}
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


/**
 * 伪静态地址转换器
 *
 * @param  string
 * @return string
 */
function url_rewrite($path = null)
{
	static $base_url;
	if (!isset($base_url))
	{
		$base_url = base_url();
		if (get_setting('url_rewrite_enable') != 'Y')
		{
			$base_url = $base_url . '/?';
		}
	}

	if (!$path)
	{
		return $base_url;
	}
	return $base_url . $path;
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
	$page = intval($page);
	if ($page < 1)
	{
		$page = 1;
	}

	$per_page = intval($per_page);
	if ($per_page < 1)
	{
		$per_page = 1;
	}

	if ($per_page > 1000)
	{
		$per_page = 1000;
	}

	return (($page - 1) * $per_page) . ', ' . ($per_page);
}

/**
 * 检查队列中是否存在指定的 hash 值, 并移除之, 用于表单提交验证
 *
 * @param  string
 * @return boolean
 */
function valid_post_hash($token)
{
	return AWS_APP::form()->check_csrf_token($token);
}

/**
 * 创建一个新的 hash 字符串，并写入 hash 队列, 用于表单提交验证
 *
 * @return string
 */
function new_post_hash()
{
	return AWS_APP::form()->create_csrf_token(3600);
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


function is_inside_url($url)
{
	if (!$url)
	{
		return false;
	}

	// url like '//www.google.com'
	if (strpos($url, '//') === 0)
	{
		$url = 'https:' . $url;
	}

	// relative url
	if (stripos($url, 'https://') !== 0 && stripos($url, 'http://') !== 0)
	{
		return true;
	}

	static $website_domains;
	if (!isset($website_domains))
	{
		$website_domains = get_setting_array('website_domains', "\n");
	}

	foreach($website_domains AS $host)
	{
		if (!$host)
		{
			continue;
		}

		// url like 'https://www.google.com'
		if (strcasecmp($url, 'https://' . $host) === 0 || strcasecmp($url, 'http://' . $host) === 0)
		{
			return true;
		}

		// url like 'https://www.google.com/xxx'
		if (stripos($url, 'https://' . $host . '/') === 0 || stripos($url, 'http://' . $host . '/') === 0)
		{
			return true;
		}
	}

	return false;
}

