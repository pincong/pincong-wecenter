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
		return true;
	}


	private static function request($method, &$filename, &$content)
	{
		if ($method == 'PUT')
			file_put_contents($filename, $content);
		return '{"status_code": 200}';
	}

}