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
	function iconv($from_encoding, $target_encoding, $string)
	{
		return mb_convert_encoding($string, str_replace('//IGNORE', '', strtoupper($target_encoding)), $from_encoding);
	}
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
	if (function_exists('iconv_strlen'))
	{
		return iconv_strlen($string, $charset);
	}

	return mb_strlen($string, $charset);
}

/**
 * 双字节语言版 substr
 *
 * 使用方法同 substr()
 *
 * @param  string
 * @param  int
 * @param  int
 * @param  string
 * @return string
 */
function cjk_substr($string, $start, $length, $charset = 'UTF-8')
{
	if (function_exists('iconv_substr'))
	{
		return iconv_substr($string, $start, $length, $charset);
	}

	return mb_substr($string, $start, $length, $charset);
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
 * 检查整型、字符串是否为纯数字（十进制数字，不包括负数和小数）
 *
 * @param integer or string
 * @return boolean
 */
function is_digits($num)
{
	if (!$num AND $num !== 0 AND $num !== '0')
	{
		return false;
	}

	if (is_string($num))
	{
		$num_int = intval($num);
		if ($num_int < 0)
		{
			return false;
		}
		return ($num === strval($num_int));
	}
	else if (is_int($num))
	{
		return ($num >= 0);
	}

	return false;
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


function uniqid_generate($length = 16)
{
	return substr(strtolower(md5(uniqid(rand()))), 0, $length);
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


function unserialize_array(&$string)
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

function serialize_array(&$array)
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

function checksum($string) {
	$i = crc32($string);
	if (0 > $i)
	{
		// Implicitly casts i as float, and corrects this sign.
		$i += 0x100000000;
	}
	return $i;
}

function &safe_base64_encode($string) {
	return strtr(rtrim(base64_encode($string), '='), '+/', '._');
}

function &safe_base64_decode($string) {
	return base64_decode(strtr($string, '._', '+/'));
}

function &safe_text($html) {
	return str_replace(
		array('<', '>', '"', "'"),
		array('&lt;', '&gt;', '&quot;', '&#39;'),
		$html
	);
}

function &unnest_bbcode($text) {
	return str_replace(
		array('[', ']'),
		array('&#91;', '&#93;'),
		$text
	);
}


function &truncate_text($string, $length, $ellipsis = '...')
{
	if (cjk_strlen($string) <= $length)
	{
		return $string;
	}
	return cjk_substr($string, 0, $length) . $ellipsis;
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
