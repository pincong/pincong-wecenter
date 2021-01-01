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

class FORMAT
{

	// 为了防止[video]、[img]、[url]嵌套而产生XSS
	// $url还需要再htmlspecialchars一次但排除&amp, 见self::text()
	// XSS例子: [img]http://localhost/favicon.ico?[url]http://localhost/favicon.ico? onload='alert(1)' onerror='alert(2)'[/url][/img]
	// 首先被解析成：<img src="http://localhost/favicon.ico?[url]http://localhost/favicon.ico? onload='alert(1)' onerror='alert(2)'[/url]">
	// 然后被解析成：<img src="http://localhost/favicon.ico?<a href="http://localhost/favicon.ico? onload='alert(1)' onerror='alert(2)'">此处省略</a>">
	// onload或onerror会被执行

	public static function parse_image($orig_url)
	{
		$url = self::text($orig_url);

		if (!is_website($orig_url) AND !is_uri_path($orig_url))
		{
			return $url;
		}
		if (!content_url_whitelist_check($orig_url))
		{
			return self::parse_link($orig_url);
		}

		return '<a href="url/img/' . safe_base64_encode(htmlspecialchars_decode($orig_url)) . '" title="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . 
			'<img src="' . $url . '" alt="' . $url . '" style="max-width:100%">' . 
			'</a>';
	}

	public static function parse_video($orig_url)
	{
		$url = self::text($orig_url);

		if (!is_website($orig_url) AND !is_uri_path($orig_url))
		{
			return $url;
		}
		if (!content_url_whitelist_check($orig_url))
		{
			return self::parse_link($orig_url);
		}

		return "<video controls preload=\"none\" src=\"$url\" style=\"max-width:100%\"></video>";
	}


	public static function parse_link($orig_url, $title = null, $allow_nested = false)
	{
		$url = self::text($orig_url);

		if ($title === null)
		{
			$title = $url;
		}
		else if (!$allow_nested)
		{
			$title = self::text($title);
		}

		if (!is_website($orig_url) AND !is_uri_path($orig_url))
		{
			return $title;
		}

		if (hyperlink_whitelist_check($orig_url))
		{
			if (is_inside_url($orig_url))
			{
				return '<a href="' . $url . '" title="' . $url . '">' . $title . '</a>';
			}
			return '<a href="' . $url . '" title="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . $title . '</a>';
		}

		if (hyperlink_blacklist_check($orig_url))
		{
			return $title;
		}

		if (is_inside_url($orig_url))
		{
			return '<a href="' . $url . '" title="' . $url . '">' . $title . '</a>';
		}
		return '<a href="url/link/' . safe_base64_encode(htmlspecialchars_decode($orig_url)) . '" title="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . $title . '</a>';
	}


	public static function text($text, $censor = false)
	{
		if (!$text)
		{
			return '';
		}

		$text = str_replace(
			array('<', '>', '"', "'"),
			array('&lt;', '&gt;', '&quot;', '&#39;'),
			$text
		);

		if (!$censor)
		{
			return $text;
		}

		static $replace;
		static $replacing_list;
		if (!isset($replace))
		{
			$user_content_replace = S::get('user_content_replace');
			if (isset($user_content_replace))
			{
				$replace = ($user_content_replace == 'Y');
				$replacing_list = S::get_key_value_pairs('content_replacing_list', '<>', true);
			}
		}

		// 由于::text()被频繁调用, list设置太复杂会影响性能
		if ($replace)
		{
			content_replace($text, $replacing_list);
		}

		return $text;
	}


	public static function message($text, $censor = false)
	{
		$text = self::text($text, $censor);

		return nl2br($text);
	}


	public static function hyperlink($text, $censor = false)
	{
		$text = self::text($text, $censor);

		$text = @preg_replace_callback(
			'/(?<!!!\[\]\(|"|\'|\)|>)(https?:\/\/[-a-zA-Z0-9@:;%_\+.~#?\&\/\/=!]+)(?!"|\'|\)|>)/i',
			array('FORMAT', '_hyperlink_callback'),
			$text
		);

		return nl2br($text);
	}

	private static function _hyperlink_callback($matches)
	{
		return self::parse_link($matches[1]);
	}


	public static function bbcode($text, $censor = false)
	{
		$text = self::text($text, $censor);

		// 不再主动解析链接
		// Bug: [url]https://web.archive.org/web/20170602230234/http://www.sohu.com/a/145581401_670685[/url]
		// return self::hyperlink(load_class('Services_BBCode')->parse($text), false);
		$text = load_class('Services_BBCode')->parse($text);

		return nl2br($text);
	}

}