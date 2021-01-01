<?php

class Services_BBCode
{
	protected $bbcode_table = array();

	private function _code_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return "<pre>" . unnest_bbcode($matches[1]) . "</pre>";
	}

	private function _url_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return FORMAT::parse_link(unnest_bbcode($matches[1]));
	}

	private function _url_2_callback($matches)
	{
		if (!trim($matches[2]))
		{
			return unnest_bbcode($matches[0]);
		}

		return FORMAT::parse_link(unnest_bbcode($matches[1]), $matches[2], true);
	}

	private function _img_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return FORMAT::parse_image(unnest_bbcode($matches[1]));
	}

	private function _img_2_callback($matches)
	{
		if (!trim($matches[2]))
		{
			return unnest_bbcode($matches[0]);
		}

		return FORMAT::parse_image(unnest_bbcode($matches[2]));
	}

	private function _video_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return FORMAT::parse_video(unnest_bbcode($matches[1]));
	}


	private function _plain_text_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return $matches[1];
	}

	private function _plain_text_2_callback($matches)
	{
		if (!trim($matches[2]))
		{
			return unnest_bbcode($matches[0]);
		}

		return $matches[2];
	}

	private function _hr_callback($matches)
	{
		return "<hr>";
	}

	private function _b_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return "<b>$matches[1]</b>";
	}

	private function _i_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return "<i>$matches[1]</i>";
	}

	private function _quote_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return "<blockquote>$matches[1]</blockquote>";
	}

	private function _center_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return "<center>$matches[1]</center>";
	}

	private function _s_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return "<s>$matches[1]</s>";
	}

	private function _u_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		return '<u>' . $matches[1] . '</u>';
	}

	private function _ul_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		$matches[1] = preg_replace_callback("/\[li\](.*?)\[\/li\]/is", array(&$this, '_list_element_callback'), $matches[1]);
		return "<ul>" . preg_replace("/[\n\r?]/", "", $matches[1]) . "</ul>";
	}

	private function _ol_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		$matches[1] = preg_replace_callback("/\[li\](.*?)\[\/li\]/is", array(&$this, '_list_element_callback'), $matches[1]);
		return "<ol>" . preg_replace("/[\n\r?]/", "", $matches[1]) . "</ol>";
	}

	private function _list_callback($matches)
	{
		if (!trim($matches[1]))
		{
			return unnest_bbcode($matches[0]);
		}

		$matches[1] = preg_replace_callback("/\[\*\](.*?)\[\/\*\]/is", array(&$this, '_list_element_callback'), $matches[1]);
		return "<ul>" . preg_replace("/[\n\r?]/", "", $matches[1]) . "</ul>";
	}

	private function _advanced_list_callback($matches)
	{
		if (!trim($matches[2]))
		{
			return unnest_bbcode($matches[0]);
		}

		if ($matches[1] == '1')
		{
			$list_type = 'ol';
		}
		else
		{
			$list_type = 'ul';
		}

		$matches[2] = preg_replace_callback("/\[\*\](.*?)\[\/\*\]/is", array(&$this, '_list_element_callback'), $matches[2]);
		return '<' . $list_type . '>' . preg_replace("/[\n\r?]/", "", $matches[2]) . '</' . $list_type . '>';
	}

	private function _list_element_callback($matches)
	{
		return "<li>" . preg_replace("/[\n\r?]$/", "", $matches[1]) . "</li>";
	}


	public function __construct()
	{
		// [code] 里面不允许嵌套其他tags, 最先解析

		// Replace [code]...[/code] with <pre><code>...</code></pre>
		$this->bbcode_table["/\[code\](.*?)\[\/code\]/is"] = '_code_callback';


		// 优先解析有引号的html elements (img, video, a)
		// [img] [video] [url] 里面不允许嵌套

		// Replace [img]...[/img] with <img src="..."/>
		$this->bbcode_table["/\[img\](.*?)\[\/img\]/is"] = '_img_callback';

		// Replace [img=...]...[/img] with <img src="..."/>
		$this->bbcode_table["/\[img=(.*?)\](.*?)\[\/img\]/is"] = '_img_2_callback';

		// Replace [video]...[/video] with swf video player
		$this->bbcode_table["/\[video\](.*?)\[\/video\]/is"] = '_video_callback';

		// Replace [url]...[/url] with <a href="...">...</a>
		$this->bbcode_table["/\[url\](.*?)\[\/url\]/is"] = '_url_callback';

		// Replace [url=http://www.google.com/]A link to google[/url] with <a href="http://www.google.com/">A link to google</a>
		$this->bbcode_table["/\[url=(.*?)\](.*?)\[\/url\]/is"] = '_url_2_callback';


		// 纯文本tags
		$this->bbcode_table["/\[cp\](.*?)\[\/cp\]/is"] = '_plain_text_callback';

		$this->bbcode_table["/\[size=(.*?)\](.*?)\[\/size\]/is"] = '_plain_text_2_callback';
		$this->bbcode_table["/\[font=(.*?)\](.*?)\[\/font\]/is"] = '_plain_text_2_callback';
		$this->bbcode_table["/\[color=(.*?)\](.*?)\[\/color\]/is"] = '_plain_text_2_callback';


		// Replace [hr] with <hr>
		$this->bbcode_table["/\[hr\]/is"] = '_hr_callback';

		// Replace [b]...[/b] with <strong>...</strong>
		$this->bbcode_table["/\[b\](.*?)\[\/b\]/is"] = '_b_callback';

		// Replace [i]...[/i] with <em>...</em>
		$this->bbcode_table["/\[i\](.*?)\[\/i\]/is"] = '_i_callback';

		// Replace [quote]...[/quote] with <blockquote><p>...</p></blockquote>
		$this->bbcode_table["/\[quote\](.*?)\[\/quote\]/is"] = '_quote_callback';

		// Replace [s] with <del>
		$this->bbcode_table["/\[s\](.*?)\[\/s\]/is"] = '_s_callback';

		// Replace [u]...[/u] with <span style="text-decoration:underline;">...</span>
		$this->bbcode_table["/\[u\](.*?)\[\/u\]/is"] = '_u_callback';

		// Replace [center] with <center>
		$this->bbcode_table["/\[center\](.*?)\[\/center\]/is"] = '_center_callback';

		// Replace [list]...[/list] with <ul><li>...</li></ul>
		$this->bbcode_table["/\[list\](.*?)\[\/list\]/is"] = '_list_callback';

		// Replace [list=1|a]...[/list] with <ul|ol><li>...</li></ul|ol>
		$this->bbcode_table["/\[list=(1|a)\](.*?)\[\/list\]/is"] = '_advanced_list_callback';

		// Replace [ul]...[/ul] with <ul><li>...</li></ul>
		$this->bbcode_table["/\[ul\](.*?)\[\/ul\]/is"] = '_ul_callback';

		// Replace [ol]...[/ol] with <ol><li>...</li></ol>
		$this->bbcode_table["/\[ol\](.*?)\[\/ol\]/is"] = '_ol_callback';

		return $this;
	}

	public function parse($text)
	{
		if (!$text)
		{
			return '';
		}

		foreach ($this->bbcode_table AS $key => $val)
		{
			$text = preg_replace_callback($key, array(&$this, $val), $text);
		}

		return $text;
	}
}
