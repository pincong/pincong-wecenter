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
     * 目前只支持 YouTube
     *
     * @param string        $source_type
     * @param string        $source
     * @param string        $size s|m|l
     * @return string|false
     */
	public static function get_thumb_url($source_type, $source, $size = 's')
	{
		$source = trim($source);
		if (!$source)
		{
			return false;
		}

		if ($source_type !== 'youtube')
		{
			return false;
		}

		switch ($size)
		{
			case 's':
				$filename = 'default.jpg';
				break;
			case 'm':
				$filename = 'hqdefault.jpg';
				break;
			case 'l':
				$filename = 'maxresdefault.jpg';
				break;
		}

		if (!$filename)
		{
			return false;
		}

		return 'https://i.ytimg.com/vi/' . $source . '/' . $filename;
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
		$url = 'https://www.youtube.com/get_video_info?video_id=' . $source;

		$header = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12';
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
		if (!isset($output['player_response']))
		{
			return false;
		}

		$body = json_decode($output['player_response'], true);
		if (!is_array($body))
		{
			return false;
		}

		if (!isset($body['videoDetails']))
		{
			return false;
		}

		if (!isset($body['streamingData']) OR !is_array($body['streamingData']['formats']))
		{
			return false;
		}

		// TODO: 整理 $body['streamingData']['formats']
		return array(
			'source_type' => 'youtube',
			'source' => $body['videoDetails']['videoId'],
			'title' => $body['videoDetails']['title'],
			'duration' => intval($body['videoDetails']['lengthSeconds']),
			'formats' => $body['streamingData']['formats']
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
