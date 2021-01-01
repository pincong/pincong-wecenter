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
function check_repeat_submission($uid, $text)
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

function set_repeat_submission_digest($uid, $text)
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
		$interval = S::get_int('user_operation_interval');
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

function check_http_referer()
{
	if (S::get('check_http_referer') != 'Y')
	{
		return true;
	}
	static $website_domains;
	if (!isset($website_domains))
	{
		$website_domains = S::get_array('website_domains', "\n");
	}
	if (!$website_domains)
	{
		return true;
	}
	$empty = true;
	foreach($website_domains AS $host)
	{
		if (!$host)
		{
			continue;
		}
		$empty = false;
		if (stripos($_SERVER['HTTP_REFERER'], 'https://' . $host . '/') === 0)
		{
			return true;
		}
		if (!isset($_SERVER['REQUEST_SCHEME']) OR $_SERVER['REQUEST_SCHEME'] != 'https')
		{
			if (stripos($_SERVER['HTTP_REFERER'], 'http://' . $host . '/') === 0)
			{
				return true;
			}
		}
	}
	return $empty;
}

function get_user_groups_flagged()
{
	return AWS_APP::model('usergroup')->get_groups_flagged();
}

function get_user_group_name_flagged($flagged)
{
	$name = AWS_APP::model('usergroup')->get_group_name_by_value_flagged($flagged);
	if (!$name)
	{
		return '?';
	}
	return $name;
}

function can_edit_post($post_uid, $user_info)
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
		$specific_post_uids = array_map('trim', explode(',', $user_info['permission']['specific_post_uids']));
		if (!$specific_post_uids)
		{
			$specific_post_uids = S::get_array('specific_post_uids');
		}
		if (!is_array($specific_post_uids))
		{
			$specific_post_uids = array();
		}
	}
	if (in_array($post_uid, $specific_post_uids))
	{
		return true;
	}
	return false;
}

function get_anonymous_user_info($user_info)
{
	static $anonymous_user;
	if (!isset($anonymous_user))
	{
		$uid = AWS_APP::model('anonymous')->get_anonymous_uid($user_info);
		$anonymous_user = AWS_APP::model('account')->get_user_info_by_uid($uid);
	}
	return $anonymous_user;
}

// 获取主题图片指定尺寸的完整url地址
function get_topic_pic_url($topic_info, $size = 'min')
{
	$all_size = array('min', 'mid', 'max');
	$size = in_array($size, $all_size) ? $size : $all_size[0];

	$default = G_STATIC_URL . '/common/topic-' . $size . '-img.png';

	if (!$topic_info OR is_null($topic_info['topic_pic']))
	{
		return $default;
	}

	$filename = '/topic/' . AWS_APP::model('topic')->get_image_path($topic_info['topic_id'], $size);
	return S::get('upload_url') . $filename . '?' . $topic_info['topic_pic']; // $topic_info['topic_pic'] 随机字符串用于避免 CDN 缓存
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
		$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
	}
	return $base_url;
}

function date_friendly($timestamp)
{
	$timestamp = $timestamp + S::get_int('time_difference');

	if (S::get('time_style') == 'N')
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
	if ($exception_message)
	{
		$root_dir = rtrim(ROOT_PATH, '/\\');
		$exception_message = str_replace($root_dir, '', $exception_message);
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

function show_error($exception_message)
{
	@ob_end_clean();

	header("HTTP/1.1 500 Internal Server Error");

	echo _show_error($exception_message);
	exit;
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
	return S::get($varname);
}

function get_settings()
{
	return S::get_all();
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
		if (S::get('url_rewrite_enable') != 'Y')
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
		echo load_class('core_lang')->translate($string, $replace);
	}
	else
	{
		echo AWS_APP::lang()->translate($string, $replace);
	}
}

function _t($string, $replace = null)
{
	if (!class_exists('AWS_APP', false))
	{
		return load_class('core_lang')->translate($string, $replace);
	}
	else
	{
		return AWS_APP::lang()->translate($string, $replace);
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

	if (S::get('time_blurring') == 'N')
	{
		return $timestamp;
	}
	$min = S::get_int('random_seconds_min');
	$max = S::get_int('random_seconds_max');
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
		$website_domains = S::get_array('website_domains', "\n");
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

function friendly_error_type($type)
{
	switch($type)
	{
		case E_ERROR: // 1 //
			return 'E_ERROR';
		case E_WARNING: // 2 //
			return 'E_WARNING';
		case E_PARSE: // 4 //
			return 'E_PARSE';
		case E_NOTICE: // 8 //
			return 'E_NOTICE';
		case E_CORE_ERROR: // 16 //
			return 'E_CORE_ERROR';
		case E_CORE_WARNING: // 32 //
			return 'E_CORE_WARNING';
		case E_COMPILE_ERROR: // 64 //
			return 'E_COMPILE_ERROR';
		case E_COMPILE_WARNING: // 128 //
			return 'E_COMPILE_WARNING';
		case E_USER_ERROR: // 256 //
			return 'E_USER_ERROR';
		case E_USER_WARNING: // 512 //
			return 'E_USER_WARNING';
		case E_USER_NOTICE: // 1024 //
			return 'E_USER_NOTICE';
		case E_STRICT: // 2048 //
			return 'E_STRICT';
		case E_RECOVERABLE_ERROR: // 4096 //
			return 'E_RECOVERABLE_ERROR';
		case E_DEPRECATED: // 8192 //
			return 'E_DEPRECATED';
		case E_USER_DEPRECATED: // 16384 //
			return 'E_USER_DEPRECATED';
	}
	return strval($type);
}

function content_replace(&$content, $replacing_list)
{
	if (!$content OR !$replacing_list)
	{
		return;
	}

	foreach($replacing_list AS $word => $replacement)
	{
		if (!isset($replacement))
		{
			$replacement = '';
		}

		if (substr($word, 0, 1) == '{' AND substr($word, -1, 1) == '}')
		{
			$content = preg_replace(substr($word, 1, -1), $replacement, $content);
		}
		else
		{
			$content = str_ireplace($word, $replacement, $content);
		}
	}
}



// 命中返回 true, 未命中返回 false
function content_url_whitelist_check($content_url)
{
	return S::content_contains('content_url_whitelist', $content_url);
}

// 命中返回 true, 未命中返回 false
function hyperlink_blacklist_check($hyperlink)
{
	return S::content_contains('hyperlink_blacklist', $hyperlink);
}

// 命中返回 true, 未命中返回 false
function hyperlink_whitelist_check($hyperlink)
{
	return S::content_contains('hyperlink_whitelist', $hyperlink);
}

