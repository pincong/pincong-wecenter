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


	public static function fetch_metadata($source_type, $source, $from_cache = true)
	{
		if (!$source = trim($source))
		{
			return false;
		}
		if (!$source_type = trim($source_type))
		{
			return false;
		}

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

	public static function fetch_metadata_by_url($url)
	{
		if (!$url = trim($url))
		{
			return false;
		}
		return self::real_fetch_metadata(null, null, $url);
	}

    /**
     * 发送 Http 请求, 取 metadata
     * Http 请求成功返回 array, Http 请求失败返回 false
     *
     * @param string        $source_type
     * @param string        $source
     * @param string        $url	如果指定则忽略前两个参数
     * @return mixed        例: array('source_type' => 'youtube', 'source' => 'abcdefghijk', 'duration' => 12345)
     */
	private static function real_fetch_metadata($source_type, $source, $url = null)
	{
		$api = self::get_parser_api();
		if (!$api)
		{
			return false;
		}

		if ($url)
		{
			$content = 'url=' . urlencode($url);
		}
		else
		{
			$content = 'source_type=' . urlencode($source_type) . '&source=' . urlencode($source);
		}

		// 附加参数如 access_token 和/或 其他自定义信息
		if ($api_params = get_setting('video_config_parser_api_params'))
		{
			$content .= '&' . $api_params;
		}

		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: ' . strlen($content)
		);

		$ctx = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'follow_location' => 0,
				'protocol_version' => 1.1,
				'header' => implode("\r\n", $headers),
				'content' => $content,
			)
		));

		// 发起请求
		$body = @file_get_contents($api, false, $ctx);
		if (!$body)
		{
			return false;
		}

		$body = json_decode($body, true);
		if (!is_array($body))
		{
			return false;
		}

		// 请自行检查 $body['source_type'], $body['source'], ...
		return $body;
	}

	private static function get_parser_api()
	{
		static $rows;
		if (!$rows)
		{
			$rows = get_setting('video_config_parser_api');
			if (!$rows)
			{
				return false;
			}
			$rows = explode("\n", $rows);
		}
		return trim($rows[rand(0, count($rows) - 1)]); // 随机选一个
	}

}
