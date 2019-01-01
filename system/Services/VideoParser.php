<?php

class Services_VideoParser
{
    /**
     * 缩略图
     *
     * @param string        $source_type
     * @param string        $source
     * @param string        $size s|m|l
     * @return string|false
     */
	public static function get_thumb_url($source_type, $source, $size = 's')
	{
		static $kvps;
		if (!$kvps)
		{
			$kvps = get_key_value_pairs('video_config_thumb_url_rules');
		}

		if (!$source = trim($source))
		{
			return false;
		}
		if (!$source_type = trim($source_type))
		{
			return false;
		}

		switch ($size)
		{
			case 's':
				$key = $source_type . '@s';
				break;
			case 'm':
				$key = $source_type . '@m';
				break;
			case 'l':
				$key = $source_type . '@l';
				break;
			default:
				$key = $source_type;
		}

		$val = $kvps[$key];
		if (!$val)
		{
			return false;
		}

		return str_replace('{$source}', $source, $val);
	}

	public static function get_iframe_url($source_type, $source)
	{
		static $kvps;
		if (!$kvps)
		{
			$kvps = get_key_value_pairs('video_config_iframe_url_rules');
		}

		if (!$source = trim($source))
		{
			return false;
		}
		if (!$source_type = trim($source_type))
		{
			return false;
		}

		$val = $kvps[$source_type];
		if (!$val)
		{
			return false;
		}

		return str_replace('{$source}', $source, $val);
	}


	public static function parse_video_url($url)
	{
		static $kvps;
		if (!$kvps)
		{
			$kvps = get_key_value_pairs('video_config_url_parsing_rules');
		}

		foreach ($kvps as $type => $regex)
		{
			if (preg_match($regex, $url, $matches))
			{
				return array(
					'source_type' => $type,
					'source' => $matches[1]
				);
			}
		}

		return false;
	}

}
