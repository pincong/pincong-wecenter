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
	// $url还需要再htmlspecialchars一次但排除&amp, 见safe_text()
	// XSS例子: [img]http://localhost/favicon.ico?[url]http://localhost/favicon.ico? onload='alert(1)' onerror='alert(2)'[/url][/img]
	// 首先被解析成：<img src="http://localhost/favicon.ico?[url]http://localhost/favicon.ico? onload='alert(1)' onerror='alert(2)'[/url]">
	// 然后被解析成：<img src="http://localhost/favicon.ico?<a href="http://localhost/favicon.ico? onload='alert(1)' onerror='alert(2)'">此处省略</a>">
	// onload或onerror会被执行

	public static function parse_image($orig_url)
	{
		$url = safe_text($orig_url);

		if (!is_website($orig_url))
		{
			return $url;
		}
		if (!H::content_url_whitelist_check($orig_url))
		{
			return self::parse_link($orig_url);
		}

		return '<a href="url/img/' . safe_base64_encode(htmlspecialchars_decode($orig_url)) . '" title="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . 
			'<img src="' . $url . '" alt="' . $url . '" style="max-width:100%">' . 
			'</a>';
	}

	public static function parse_video($orig_url)
	{
		$url = safe_text($orig_url);

		if (!is_website($orig_url))
		{
			return $url;
		}
		if (!H::content_url_whitelist_check($orig_url))
		{
			return self::parse_link($orig_url);
		}

		return "<video controls preload=\"none\" src=\"$url\" style=\"max-width:100%\"></video>";
	}


	public static function parse_link($orig_url, $title = null)
	{
		$url = safe_text($orig_url);

		if ($title === null)
		{
			$title = $url;
		}
		else
		{
			$title = safe_text($title);
		}

		if (!is_website($orig_url))
		{
			return $title;
		}

		if (H::hyperlink_whitelist_check($orig_url))
		{
			if (is_inside_url($orig_url))
			{
				return '<a href="' . $url . '" title="' . $url . '">' . $title . '</a>';
			}
			return '<a href="' . $url . '" title="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . $title . '</a>';
		}

		if (H::hyperlink_blacklist_check($orig_url))
		{
			return $title;
		}

		if (is_inside_url($orig_url))
		{
			return '<a href="' . $url . '" title="' . $url . '">' . $title . '</a>';
		}
		return '<a href="url/link/' . safe_base64_encode(htmlspecialchars_decode($orig_url)) . '" title="' . $url . '" rel="nofollow noreferrer noopener" target="_blank">' . $title . '</a>';
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