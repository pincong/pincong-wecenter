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
	private static function &txt_deleted()
	{
		static $text;
		if (!isset($text))
		{
			$text = '<s class="aw-deleted">' . AWS_APP::lang()->_t('已删除') . '</s>';
		}
		return $text;
	}
	private static function &txt_hidden()
	{
		static $text;
		if (!isset($text))
		{
			$text = '<i class="aw-deleted">' . AWS_APP::lang()->_t('已隐藏') . '</i>';
		}
		return $text;
	}

	private static function &kb_tips()
	{
		static $text;
		if (!isset($text))
		{
			$text = S::get('kb_replaced_tips');
			if ($text)
			{
				$text = '<p class="aw-small-text">' . $text . '</p>';
			}
			else
			{
				$text = '';
			}
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
		if ($user_info['flagged'] == 2)
		{
			return '';
		}

		if ($kb = self::get_kb($user_info, $key))
		{
			return $kb['title'];
		}
		return $string;
	}

	// 标题
	public static function &title(&$user_info, $key, &$string)
	{
		if ($user_info['flagged'] == 2)
		{
			return self::txt_hidden();
		}

		if ($kb = self::get_kb($user_info, $key))
		{
			return $kb['title'];
		}
		if (!isset($string))
		{
			return self::txt_deleted();
		}
		return $string;
	}

	// 正文 (不显示已删除) (解析bbcode)
	public static function &body(&$user_info, $key, &$string)
	{
		if ($user_info['flagged'] == 2)
		{
			return '';
		}

		if ($kb = self::get_kb($user_info, $key))
		{
			return self::kb_tips() . FORMAT::bbcode($kb['message']);
		}
		return FORMAT::bbcode($string);
	}

	// 正文 (不显示已删除) (解析链接)
	public static function &body_simple(&$user_info, $key, &$string)
	{
		if ($user_info['flagged'] == 2)
		{
			return '';
		}

		if ($kb = self::get_kb($user_info, $key))
		{
			return self::kb_tips() . FORMAT::bbcode($kb['message']);
		}
		return FORMAT::hyperlink($string);
	}

	// 回复 (解析bbcode)
	public static function &reply(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			$text = $kb['title'] . "\r\n" . $kb['message'];
			return self::kb_tips() . FORMAT::bbcode($text);
		}

		if (!isset($string))
		{
			return self::txt_deleted();
		}
		return FORMAT::bbcode($string);
	}

	// 回复 (解析链接)
	public static function &reply_simple(&$user_info, $key, &$string)
	{
		if ($kb = self::get_kb($user_info, $key))
		{
			$text = $kb['title'] . "\r\n" . $kb['message'];
			return self::kb_tips() . FORMAT::bbcode($text);
		}

		if (!isset($string))
		{
			return self::txt_deleted();
		}
		return FORMAT::hyperlink($string);
	}

	public static function skip(&$user_info, $limited = true)
	{
		if ($user_info['flagged'] == 2)
		{
			return true;
		}

		if ($user_info['flagged'] != 3 OR !$limited)
		{
			return false;
		}

		// 每帖之复读次数超过限制则跳过(隐藏)
		static $max_replies;
		if (!isset($max_replies))
		{
			$max_replies = S::get('kb_replies_per_post');
		}
		if (!$max_replies)
		{
			return false;
		}

		static $ref_count;
		if (!isset($ref_count))
		{
			$ref_count = 1;
		}

		if ($ref_count > $max_replies)
		{
			return true; // 超出限制
		}

		$ref_count = $ref_count + 1;

		return false;
	}
}