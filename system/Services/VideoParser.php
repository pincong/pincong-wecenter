<?php

class Services_VideoParser
{

	// 检查视频网址是否支持
	public static function check_url($url)
	{
		return content_contains('video_config_url_whitelist', $url);
	}

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

		$source = trim($source);
		if (!$source)
		{
			return false;
		}

		$source_type = trim($source_type);
		if (!$source_type)
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

	public static function fetch_metadata_by_url($url)
	{
		return false;
	}

    /**
     * 发送 Http 请求, 取 metadata
     * 成功返回 array, 失败返回 false
     *
     * @param string        $source_type
     * @param string        $source
     * @return mixed        例: array('duration' => 12345, ...)
     */
	private static function real_fetch_metadata($source_type, $source)
	{
		return false;
	}

	public static function fetch_metadata($source_type, $source, $from_cache = true)
	{
		if (!$from_cache)
		{
			return self::real_fetch_metadata($source_type, $source);
		}

		$cache_rules = get_key_value_pairs('video_config_parser_cache_rules');

		$cache_time = intval($cache_rules[$source_type]);
		if (!$cache_time)
		{
			return self::real_fetch_metadata($source_type, $source);
		}

		$cache_key = 'fetch_metadata_' . $source_type . '_' . md5($source);

		$result = AWS_APP::cache()->get($cache_key);
		if (!$result)
		{
			$result = self::real_fetch_metadata($source_type, $source);
			if (!$result)
			{
				return false;
			}
			AWS_APP::cache()->set($cache_key, $result, $cache_time);
		}
		return $result;

	}

}
