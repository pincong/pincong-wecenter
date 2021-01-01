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
 * 生成随机字符串
 *
 * @param  integer
 * @return string
 */
function random_string($length = 8)
{
	for ($i = 0; $i < $length; $i++)
	{
		$str .= chr(rand(97, 122));
	}

	return $str;
}

/**
 * 获取数组中随机一条数据
 *
 * @param  array
 * @return mixed
 */
function array_random($arr)
{
	return $arr[rand(0, count($arr) - 1)];
}


/**
 * 删除网页上看不见的隐藏字符串, 如 Java\0script
 *
 * @param	string
 * @return	string
 */
function remove_invisible_characters($str)
{
	// every control character except newline (dec 10)
	// carriage return (dec 13), and horizontal tab (dec 09)

	do
	{
		// 00-08, 11, 12, 14-31, 127
		$str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str, -1, $count);
	}
	while ($count);

	return $str;
}


function multibyte_trim($str)
{
	return preg_replace('/^\s+|\s+$/u', '', $str);
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

function intval_minmax($val, $min, $max)
{
	$val = intval($val);
	$min = intval($min);
	$max = intval($max);
	if ($val < $min)
	{
		return $min;
	}
	else if ($val > $max)
	{
		return $max;
	}
	return $val;
}


function unserialize_array($string)
{
	if (isset($string))
	{
		@$result = unserialize($string, array('allowed_classes' => false));
		if (!is_array($result))
		{
			return array();
		}
		return $result;
	}
	return array();
}

function serialize_array($array)
{
	if (is_array($array) AND count($array) > 0)
	{
		return serialize($array);
	}
	return null;
}

function is_valid_timezone($timezone)
{
	if (!$timezone)
	{
		return false;
	}
	@$tz = timezone_open($timezone);
	return $tz !== false;
}

function checksum($string)
{
	$i = crc32($string);
	if (0 > $i)
	{
		// Implicitly casts i as float, and corrects this sign.
		$i += 0x100000000;
	}
	return $i;
}

function safe_base64_encode($string)
{
	return strtr(rtrim(base64_encode($string), '='), '+/', '._');
}

function safe_base64_decode($string)
{
	return base64_decode(strtr($string, '._', '+/'));
}

function safe_url_encode($string)
{
	return strtr(rawurlencode($string), '-', '+');
}

function safe_url_decode($string)
{
	return rawurldecode(strtr($string, '+', '-'));
}

function safe_text($html)
{
	return str_replace(
		array('<', '>', '"', "'"),
		array('&lt;', '&gt;', '&quot;', '&#39;'),
		$html
	);
}

function unnest_bbcode($text)
{
	return str_replace(
		array('[', ']'),
		array('&#91;', '&#93;'),
		$text
	);
}


function truncate_text($string, $length, $ellipsis = '...')
{
	if (iconv_strlen($string) <= $length)
	{
		return $string;
	}
	return iconv_substr($string, 0, $length) . $ellipsis;
}


function escape_like_clause($string)
{
	return str_replace(array('[', '_', '%'), array('[[]', '[_]', '[%]'), $string);
}


function is_javascript($url)
{
	if (!$url)
	{
		return false;
	}

	if (stripos($url, 'javascript:') === 0)
	{
		return true;
	}

	return false;
}

function is_website($url)
{
	if (!$url)
	{
		return false;
	}

	if (stripos($url, 'https://') !== 0 AND stripos($url, 'http://') !== 0)
	{
		return false;
	}

	return true;
}

function is_uri_path($url)
{
	if (!$url)
	{
		return false;
	}

	if (strpos($url, '/') !== 0 OR strpos($url, '//') === 0)
	{
		return false;
	}

	return true;
}
