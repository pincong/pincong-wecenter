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

class CF
{
	private static function &deleted()
	{
		static $text;
		if (!isset($text))
		{
			$text = '<s class="aw-deleted">' . AWS_APP::lang()->_t('已删除') . '</s>';
		}
		return $text;
	}

	private static function &get_kb_item($id)
	{
		static $cache;

		if (!$cache[$id])
		{
			if ($item = AWS_APP::model('kb')->get($id))
			{
				$cache[$id] = $item;
			}
		}

		return $cache[$id];
	}

	// 获得知识库 id (替换原内容)
	private static function get_kb_id(&$user_info, &$key)
	{
		if ($user_info['flagged'] != 3)
		{
			return 0;
		}

		static $cache;
		if ($cache[$key])
		{
			return $cache[$key];
		}

		$kb_count = AWS_APP::model('kb')->size();
		if (!$kb_count)
		{
			return 0;
		}

		$num = checksum($key);
		$kb_id = $num % $kb_count + 1;
		$cache[$key] = $kb_id;
		return $kb_id;
	}

	private static function &get_kb(&$user_info, &$key)
	{
		if ($id = self::get_kb_id($user_info, $key))
		{
			return self::get_kb_item($id);
		}
	}

	// 标题 (raw text)
	public static function &page_title(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			return $kb['title'];
		}
		return $string;
	}

	// 标题
	public static function &title(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			return $kb['title'];
		}
		if (!isset($string))
		{
			return self::deleted();
		}
		return $string;
	}

	// 正文 (不显示已删除) (解析bbcode)
	public static function &body(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			return nl2br(FORMAT::parse_bbcode($kb['message']));
		}
		return nl2br(FORMAT::parse_bbcode($string));
	}

	// 正文 (不显示已删除) (解析链接)
	public static function &body_simple(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			return nl2br(FORMAT::parse_bbcode($kb['message']));
		}
		return nl2br(FORMAT::parse_links($string));
	}

	// 回复 (解析bbcode)
	public static function &reply(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			$text = $kb['title'] . "\r\n" . $kb['message'];
			return nl2br(FORMAT::parse_bbcode($text));
		}

		if (!isset($string))
		{
			return self::deleted();
		}
		return nl2br(FORMAT::parse_bbcode($string));
	}

	// 回复 (解析链接)
	public static function &reply_simple(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			$text = $kb['title'] . "\r\n" . $kb['message'];
			return nl2br(FORMAT::parse_bbcode($text));
		}

		if (!isset($string))
		{
			return self::deleted();
		}
		return nl2br(FORMAT::parse_links($string));
	}

	// TODO: 每帖之回复只复读一次
	public static function skip(&$user_info, $key = null)
	{
		if ($user_info['flagged'] == 2)
		{
			return true;
		}

		return false;
	}
}