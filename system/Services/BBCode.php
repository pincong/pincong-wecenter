<?php

class Services_BBCode
{
	protected $bbcode_table = array();

	private function _plain_text_callback($match)
	{
		return $match[1];
	}

	private function _plain_text_2_callback($match)
	{
		return $match[2];
	}

	private function _code_callback($match)
	{
		return "<pre><code>" . str_replace('[', '<span>[</span>', $match[1]) . "</code></pre>";
	}

	private function _code_2_callback($match)
	{
		return "<pre><code class=\"language-$match[1]\">" . str_replace('[', '<span>[</span>', $match[2]) . "</code></pre>";
	}

	private function _b_callback($match)
	{
		return "<strong>$match[1]</strong>";
	}

	private function _i_callback($match)
	{
		return "<em>$match[1]</em>";
	}

	private function _quote_callback($match)
	{
		return "<blockquote><p>$match[1]</p></blockquote>";
	}

	private function _center_callback($match)
	{
		return "<center>$match[1]</center>";
	}

	private function _s_callback($match)
	{
		return "<del>$match[1]</del>";
	}

	private function _u_callback($match)
	{
		return '<span style="text-decoration:underline;">' . $match[1] . '</span>';
	}

	private function _url_callback($match)
	{
		if (stripos($match[1], 'https://') !== 0 && stripos($match[1], 'http://') !== 0)
		{
			return $match[1];
		}
		return parse_link_callback($match);
	}

	private function _link_callback($match)
	{
		if (stripos($match[1], 'https://') !== 0 && stripos($match[1], 'http://') !== 0)
		{
			return $match[2];
		}

		if (is_inside_url($match[1]))
		{
			return '<a href="' . $match[1] . '">' . $match[2] . '</a>';
		}
		else
		{
			return '<a href="' . $match[1] . '" rel="nofollow noreferrer noopener" target="_blank">' . $match[2] . '</a>';
		}
	}

	private function _img_callback($match)
	{
		return load_class('Services_ImageUrlParser')->parse($match[1]);
	}

	private function _img_2_callback($match)
	{
		return load_class('Services_ImageUrlParser')->parse($match[2]);
	}

	private function _list_callback($match)
	{
		$match[1] = preg_replace_callback("/\[\*\](.*?)\[\/\*\]/is", array(&$this, '_list_element_callback'), $match[1]);

        return "<ul>" . preg_replace("/[\n\r?]/", "", $match[1]) . "</ul>";
	}

	private function _list_ul_callback($match)
	{
		$match[1] = preg_replace_callback("/\[li\](.*?)\[\/li\]/is", array(&$this, '_list_element_callback'), $match[1]);

        return "<ul>" . preg_replace("/[\n\r?]/", "", $match[1]) . "</ul>";
	}

	private function _list_ol_callback($match)
	{
		$match[1] = preg_replace_callback("/\[li\](.*?)\[\/li\]/is", array(&$this, '_list_element_callback'), $match[1]);

        return "<ol>" . preg_replace("/[\n\r?]/", "", $match[1]) . "</ol>";
	}

	private function _list_element_callback($match)
	{
		return "<li>" . preg_replace("/[\n\r?]$/", "", $match[1]) . "</li>";
	}

	private function _video_callback($match)
	{
		return load_class('Services_VideoUrlParser')->parse($match[1]);
	}

	private function _list_advance_callback($match)
	{
		if ($match[1] == '1')
        {
            $list_type = 'ol';
        }
        else
        {
        	$list_type = 'ul';
        }

        $match[2] = preg_replace_callback("/\[\*\](.*?)\[\/\*\]/is", array(&$this, '_list_element_callback'), $match[2]);

        return '<' . $list_type . '>' . preg_replace("/[\n\r?]/", "", $match[2]) . '</' . $list_type . '>';
	}

    public function __construct()
    {
        $this->bbcode_table["/\[left\](.*?)\[\/left\]/is"] = '_plain_text_callback';
        $this->bbcode_table["/\[center\](.*?)\[\/center\]/is"] = '_plain_text_callback';
        $this->bbcode_table["/\[right\](.*?)\[\/right\]/is"] = '_plain_text_callback';
        $this->bbcode_table["/\[justify\](.*?)\[\/justify\]/is"] = '_plain_text_callback';
        $this->bbcode_table["/\[sub\](.*?)\[\/sub\]/is"] = '_plain_text_callback';
        $this->bbcode_table["/\[sup\](.*?)\[\/sup\]/is"] = '_plain_text_callback';

        $this->bbcode_table["/\[size=(.*?)\](.*?)\[\/size\]/is"] = '_plain_text_2_callback';
        $this->bbcode_table["/\[font=(.*?)\](.*?)\[\/font\]/is"] = '_plain_text_2_callback';
        $this->bbcode_table["/\[color=(.*?)\](.*?)\[\/color\]/is"] = '_plain_text_2_callback';

	    // Replace [code]...[/code] with <pre><code>...</code></pre>
		$this->bbcode_table["/\[code\](.*?)\[\/code\]/is"] = '_code_callback';
		
		// Replace [code=css]...[/code] with <pre><code class="language-css">...</code></pre>
        $this->bbcode_table["/\[code=(.*?)\](.*?)\[\/code\]/is"] = '_code_2_callback';

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

        // Replace [url]...[/url] with <a href="...">...</a>
        $this->bbcode_table["/\[url\](.*?)\[\/url\]/is"] = '_url_callback';

        // Replace [url=http://www.google.com/]A link to google[/url] with <a href="http://www.google.com/">A link to google</a>
        $this->bbcode_table["/\[url=(.*?)\](.*?)\[\/url\]/is"] = '_link_callback';

        // Replace [img]...[/img] with <img src="..."/>
        $this->bbcode_table["/\[img\](.*?)\[\/img\]/is"] = '_img_callback';

        // Replace [img=...]...[/img] with <img src="..."/>
        $this->bbcode_table["/\[img=(.*?)\](.*?)\[\/img\]/is"] = '_img_2_callback';

        // Replace [video]...[/video] with swf video player
        $this->bbcode_table["/\[video\](.*?)\[\/video\]/is"] = '_video_callback';

        // Replace [list]...[/list] with <ul><li>...</li></ul>
        $this->bbcode_table["/\[list\](.*?)\[\/list\]/is"] = '_list_callback';

        // Replace [list=1|a]...[/list] with <ul|ol><li>...</li></ul|ol>
        $this->bbcode_table["/\[list=(1|a)\](.*?)\[\/list\]/is"] = '_list_advance_callback';

        // Replace [ul]...[/ul] with <ul><li>...</li></ul>
        $this->bbcode_table["/\[ul\](.*?)\[\/ul\]/is"] = '_list_ul_callback';

        // Replace [ol]...[/ol] with <ol><li>...</li></ol>
        $this->bbcode_table["/\[ol\](.*?)\[\/ol\]/is"] = '_list_ol_callback';

        return $this;
    }

    public function parse($text, $escapeHTML = false, $nr2br = false)
    {
        if (! $text)
        {
            return false;
        }

        if ($escapeHTML)
        {
            $text = htmlspecialchars($text);
        }

        foreach ($this->bbcode_table AS $key => $val)
        {
            $text = preg_replace_callback($key, array(&$this, $val), $text);
        }

        if ($nr2br)
        {
            $text = nl2br($text);
        }

        return $text;
    }
}
