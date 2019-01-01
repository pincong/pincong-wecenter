<?php

class Services_RemoteStorage
{
	// 查看
	public static function get($filename)
	{
		$content = null;
		return self::request('GET', $filename, $content);
	}

	// 存放/替换
	public static function put($filename, &$content)
	{
		return self::request('PUT', $filename, $content);
	}

	// 删除
	public static function delete($filename)
	{
		$content = null;
		return self::request('DELETE', $filename, $content);
	}


	public static function is_enabled()
	{
		return G_REMOTE_STORAGE;
	}


	private static function request($method, &$filename, &$content)
	{
		$url = self::get_request_url($filename);

		// array
		$headers = self::get_request_headers();
		if (!is_null($content))
		{
			$headers[] = 'Content-Length: ' . strlen($content);
		}

		// 发起请求
		$body = @file_get_contents($url, false, stream_context_create(array(
			'http' => array(
				'method' => $method,
				'follow_location' => 0,
				'protocol_version' => 1.1,
				'header' => implode("\r\n", $headers),
				'content' => $content,
			)
		)));

		if (!$body)
		{
			return false;
		}

		$body = json_decode($body, true);
		if (!is_array($body))
		{
			return false;
		}

		return $body;
	}


	private static function get_request_url(&$filename)
	{
		return str_replace('{$filename}', G_REMOTE_STORAGE_REQUEST_URL, urlencode($filename));
	}

	private static function get_request_headers()
	{
		return G_REMOTE_STORAGE_REQUEST_HEADERS;
	}

}