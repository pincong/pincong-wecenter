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
	private static function txt_deleted()
	{
		static $text;
		if (!isset($text))
		{
			$text = '<s class="aw-deleted">' . _t('已删除') . '</s>';
		}
		return $text;
	}
	private static function txt_hidden()
	{
		static $text;
		if (!isset($text))
		{
			$text = '<i class="aw-deleted">' . _t('已隐藏') . '</i>';
		}
		return $text;
	}

	private static function kb_tips()
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

	private static function get_kb_item($id)
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
	private static function get_kb_id($user_info, $key)
	{
		if (!$user_info OR $user_info['forbidden'] != 3)
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

	private static function get_kb($user_info, $key)
	{
		if ($id = self::get_kb_id($user_info, $key))
		{
			return self::get_kb_item($id);
		}
	}

	// 标题 (raw text)
	public static function page_title($item_info)
	{
		$user_info = $item_info['user_info'] ?? null;

		if ($user_info AND $user_info['forbidden'] == 2)
		{
			return '';
		}

		if ($kb = self::get_kb($user_info, 'thread_' . $item_info['id']))
		{
			return FORMAT::text($kb['title']);
		}
		return FORMAT::text($item_info['title']);
	}

	// 标题
	public static function title($item_info)
	{
		$user_info = $item_info['user_info'] ?? null;

		if ($user_info AND $user_info['forbidden'] == 2)
		{
			return self::txt_hidden();
		}

		if ($kb = self::get_kb($user_info, 'thread_' . $item_info['id']))
		{
			return FORMAT::text($kb['title']);
		}
		if (!$item_info['title'])
		{
			return self::txt_deleted();
		}
		return FORMAT::text($item_info['title'], true);
	}

	// 正文 (不显示已删除) (解析bbcode)
	public static function body($item_info)
	{
		$user_info = $item_info['user_info'] ?? null;

		if ($user_info AND $user_info['forbidden'] == 2)
		{
			return '';
		}

		if ($kb = self::get_kb($user_info, 'thread_' . $item_info['id']))
		{
			return self::kb_tips() . FORMAT::bbcode($kb['message']);
		}
		return FORMAT::bbcode($item_info['message'], true);
	}

	// 正文 (不显示已删除) (解析链接)
	public static function body_simple($item_info)
	{
		$user_info = $item_info['user_info'] ?? null;

		if ($user_info AND $user_info['forbidden'] == 2)
		{
			return '';
		}

		if ($kb = self::get_kb($user_info, 'thread_' . $item_info['id']))
		{
			return self::kb_tips() . FORMAT::bbcode($kb['message']);
		}
		return FORMAT::hyperlink($item_info['message'], true);
	}

	// 回复 (解析bbcode)
	public static function reply($item_info)
	{
		$user_info = $item_info['user_info'] ?? null;

		if ($user_info AND $user_info['forbidden'] == 2)
		{
			return self::txt_hidden();
		}

		if ($kb = self::get_kb($user_info, 'reply_' . $item_info['id']))
		{
			$text = $kb['title'] . "\r\n\r\n" . $kb['message'];
			return self::kb_tips() . FORMAT::bbcode($text);
		}

		if (!$item_info['message'])
		{
			return self::txt_deleted();
		}
		return FORMAT::bbcode($item_info['message'], true);
	}

	// 回复 (解析链接)
	public static function reply_simple($item_info)
	{
		$user_info = $item_info['user_info'] ?? null;

		if ($user_info AND $user_info['forbidden'] == 2)
		{
			return self::txt_hidden();
		}

		if ($kb = self::get_kb($user_info, 'reply_' . $item_info['id']))
		{
			$text = $kb['title'] . "\r\n\r\n" . $kb['message'];
			return self::kb_tips() . FORMAT::bbcode($text);
		}

		if (!$item_info['message'])
		{
			return self::txt_deleted();
		}
		return FORMAT::hyperlink($item_info['message'], true);
	}

	public static function skip($user_info)
	{
		if ($user_info['forbidden'] != 3)
		{
			return false;
		}

		// 每帖之复读次数超过限制则跳过
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