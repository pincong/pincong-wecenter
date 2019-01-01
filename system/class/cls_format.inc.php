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
	public static function parse_image($url)
	{
		if (stripos($url, 'https://') !== 0 && stripos($url, 'http://') !== 0)
		{
			return $url;
		}
		if (!H::content_url_whitelist_check($url))
		{
			return self::parse_link($url);
		}
		return '<a href="url/img/' . safe_urlencode($url) . '" target="_blank">' . 
			'<img src="' . $url .'" alt="" style="max-width:100%">' . 
			'</a>';
	}

	public static function parse_video($url)
	{
		if (stripos($url, 'https://') !== 0 && stripos($url, 'http://') !== 0)
		{
			return $url;
		}
		if (!H::content_url_whitelist_check($url))
		{
			return self::parse_link($url);
		}
		return "<video controls preload=\"none\" src=\"$url\" style=\"max-width:100%\"></video>";
	}


	public static function parse_link($url, $title = null)
	{
		if ($title === null)
		{
			$title = $url;
		}

		if (stripos($url, 'https://') !== 0 && stripos($url, 'http://') !== 0)
		{
			return $title;
		}

		if (H::hyperlink_whitelist_check($url))
		{
			if (is_inside_url($url))
			{
				return '<a href="' . $url . '">' . $title . '</a>';
			}
			return '<a href="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . $title . '</a>';
		}

		if (H::hyperlink_blacklist_check($url))
		{
			return $title;
		}

		if (is_inside_url($url))
		{
			return '<a href="' . $url . '">' . $title . '</a>';
		}
		return '<a href="url/link/' . safe_urlencode($url) . '" target="_blank">' . $title . '</a>';
	}

	private static function _link_callback($matches)
	{
		return self::parse_link($matches[1]);
	}

	// 注意是引用
	public static function &parse_links(&$str)
	{
		$str = @preg_replace_callback(
			'/(?<!!!\[\]\(|"|\'|\)|>)(https?:\/\/[-a-zA-Z0-9@:;%_\+.~#?\&\/\/=!]+)(?!"|\'|\)|>)/i',
			array('FORMAT', '_link_callback'),
			$str
		);

		//$str = @preg_replace('/([a-z0-9\+_\-]+[\.]?[a-z0-9\+_\-]+@[a-z0-9\-]+\.+[a-z]{2,6}+(\.+[a-z]{2,6})?)/is', '<a href="mailto:\1">\1</a>', $str);

		return $str;
	}

	// 注意是引用
	public static function &parse_bbcode(&$text)
	{
		if (!$text)
		{
			return '';
		}

		// 不再主动解析链接
		// Bug: [url]https://web.archive.org/web/20170602230234/http://www.sohu.com/a/145581401_670685[/url]
		// return self::parse_links(load_class('Services_BBCode')->parse($text));
		return load_class('Services_BBCode')->parse($text);
	}

}