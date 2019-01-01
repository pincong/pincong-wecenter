<?php

class Services_VideoParser
{

    /**
     * 根据 url 特征得到视频信息
     * 成功返回 array, 失败返回 false
     * 目前只支持 YouTube
     *
     * @param string $url   例: https://www.youtube.com/watch?v=abcdefghijk
     * @return mixed        例: array('source_type' => 'youtube', 'source' => 'abcdefghijk')
     */
	public static function parse_video_url($url)
	{
		$url = trim($url);
		if (!$url)
		{
			return false;
		}

		preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
		if (!isset($match[1]))
		{
			return false;
		}

		return array(
			'source_type' => 'youtube',
			'source' => $match[1]
		);
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

    /**
     * 发送 Http 请求, 取 metadata
     * 成功返回 array, 失败返回 false
     * 目前只支持 YouTube
     *
     * @param string        $source_type
     * @param string        $source
     * @return mixed        例: array('duration' => 12345, ...)
     */
	private static function real_fetch_video_metadata($source_type, $source)
	{
		$url = 'https://www.youtube.com/get_video_info?video_id=' . $source . '&asv=3&el=detailpage&hl=en_US';

		$header = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12';
		//$header = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
		$header .= "\r\n"; // 双引号内的字符串才会转义

		$opts = array(
			'http' => array(
				'method' => 'GET',
				'follow_location' => 0,
				'protocol_version' => 1.1,
				'header' => $header
			)
		);
		// 发起请求
		$ctx = stream_context_create($opts);
		$body = @file_get_contents($url, false, $ctx);
		if (!$body)
		{
			return false;
		}

		parse_str($body, $output);
		if (!isset($output['url_encoded_fmt_stream_map']))
		{
			return false;
		}

		if (!$output['video_id'])
		{
			return false;
		}

		$formats = explode(',', $output['url_encoded_fmt_stream_map']);
		if (count($formats) < 1)
		{
			return false;
		}

		foreach ($formats AS $key => $val)
		{
			parse_str($val, $formats[$key]);
			if ($formats[$key]['sp'] == 'signature')
			{
				// TODO: decrypt signature
				return false;
			}
		}

		// TODO: 整理 $formats
		return array(
			'source_type' => 'youtube',
			'source' => $output['video_id'],
			'title' => $output['title'],
			'duration' => intval($output['length_seconds']),
			'formats' => $formats
		);
	}


	public static function fetch_video_metadata($source_type, $source, $cache = true)
	{
		if ($source_type !== 'youtube')
		{
			return false;
		}

		$source = trim($source);
		if (!$source)
		{
			return false;
		}

		if (!$cache)
		{
			return self::real_fetch_video_metadata($source_type, $source);
		}

		$cache_key = 'fetch_video_metadata_' . $source_type . '_' . md5($source);

		$result = AWS_APP::cache()->get($cache_key);
		if (!$result)
		{
			$result = self::real_fetch_video_metadata($source_type, $source);
			if (!$result)
			{
				return false;
			}
			AWS_APP::cache()->set($cache_key, $result, 60);
		}
		return $result;

	}

}
