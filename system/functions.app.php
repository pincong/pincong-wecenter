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
function check_repeat_submission($text)
{
    if (isset(AWS_APP::session()->repeat_submission_digest))
    {
        if (md5($text) === AWS_APP::session()->repeat_submission_digest)
            return FALSE;
    }
    return TRUE;
}

function set_repeat_submission_digest($text)
{
    AWS_APP::session()->repeat_submission_digest = md5($text);
}


/**
 * 获取主题图片指定尺寸的完整url地址
 * @param  string $size
 * @param  string $pic_file 某一尺寸的图片文件名
 * @return string           取出主题图片或主题默认图片的完整url地址
 */
function get_topic_pic_url($size = null, $pic_file = null)
{
	if ($sized_file = AWS_APP::model('topic')->get_sized_file($size, $pic_file))
	{
		return get_setting('upload_url') . '/topic/' . $sized_file;
	}

	if (! $size)
	{
		return G_STATIC_URL . '/common/topic-max-img.png';
	}

	return G_STATIC_URL . '/common/topic-' . $size . '-img.png';
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

	$host = $_SERVER['HTTP_HOST'];

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

	return false;
}

function import_editor_static_files()
{
	TPL::import_css('editor/sceditor/themes/square.css');
	TPL::import_js('editor/sceditor/sceditor.js');
	TPL::import_js('editor/sceditor/icons/material.js');
	TPL::import_js('editor/sceditor/formats/bbcode.js');
}

function get_chapter_icon_url($id, $size = 'max', $default = true)
{
	if (file_exists(get_setting('upload_dir') . '/chapter/' . $id . '-' . $size . '.jpg'))
	{
		return get_setting('upload_url') . '/chapter/' . $id . '-' . $size . '.jpg';
	}
	else if ($default)
	{
		return G_STATIC_URL . '/common/help-chapter-' . $size . '-img.png';
	}

	return false;
}

function base64_url_encode($param)
{
	if (!is_array($param))
	{
		return false;
	}

	return strtr(base64_encode(json_encode($param)), '+/=', '-_,');
}

function base64_url_decode($param)
{
	return json_decode(base64_decode(strtr($param, '-_,', '+/=')), true);
}

function remove_assoc($from, $type, $id)
{
	if (!$from OR !$type OR !is_digits($id))
	{
		return false;
	}

	return $this->query('UPDATE ' . $this->get_table($from) . ' SET `' . $type . '_id` = NULL WHERE `' . $type . '_id` = ' . $id);
}
