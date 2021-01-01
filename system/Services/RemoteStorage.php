<?php

class Services_RemoteStorage
{
	// 查看
	public static function get($filename)
	{
		return self::request('GET', $filename);
	}

	// 存放/替换
	public static function put($filename, $content)
	{
		return self::request('PUT', $filename, $content);
	}

	// 删除
	public static function delete($filename)
	{
		return self::request('DELETE', $filename);
	}


	public static function is_enabled()
	{
		return G_REMOTE_STORAGE;
	}


	private static function request($method, $filename, $content = null)
	{
		$url = str_replace('{$filename}', urlencode($filename), G_REMOTE_STORAGE_REQUEST_URL);

		$options = array();
		if (defined('G_REMOTE_STORAGE_HTTP_OPTIONS'))
		{
			$options['http'] = G_REMOTE_STORAGE_HTTP_OPTIONS;
		}
		else
		{
			$options['http'] = array();
		}

		if (defined('G_REMOTE_STORAGE_SSL_OPTIONS'))
		{
			$options['ssl'] = G_REMOTE_STORAGE_SSL_OPTIONS;
		}

		$options['http']['method'] = $method;

		if (defined('G_REMOTE_STORAGE_REQUEST_HEADERS'))
		{
			$options['http']['header'] = implode("\r\n", G_REMOTE_STORAGE_REQUEST_HEADERS);
		}

		if (!is_null($content))
		{
			$options['http']['content'] = $content;
		}

		// 发起请求
		$body = @file_get_contents($url, false, stream_context_create($options));

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

}