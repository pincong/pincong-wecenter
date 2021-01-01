<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

class H
{
	/**
	 * 获取 COOKIE
	 *
	 * @param $name
	 */
	public static function get_cookie($name)
	{
		if (isset($_COOKIE[G_COOKIE_PREFIX . '_' . $name]))
		{
			return $_COOKIE[G_COOKIE_PREFIX . '_' . $name];
		}

		return false;
	}

	/**
	 * 设置 COOKIE
	 *
	 * @param $name
	 * @param $value
	 * @param $expire
	 * @param $path
	 * @param $domain
	 * @param $secure
	 * @param $httponly
	 */
	public static function set_cookie($name, $value = '', $expire = null, $path = '/', $domain = null, $secure = false, $httponly = true)
	{
		if (!$domain AND G_COOKIE_DOMAIN)
		{
			$domain = G_COOKIE_DOMAIN;
		}

		return setcookie(G_COOKIE_PREFIX . '_' . $name, $value, $expire, $path, $domain, $secure, $httponly);
	}

	/**
	 * 数组JSON返回
	 *
	 * @param  $array
	 */
	public static function ajax_json_output($array)
	{
		//HTTP::no_cache_header('text/javascript');

		echo str_replace(array("\r", "\n", "\t"), '', json_encode($array));
		exit;
	}

	public static function redirect_msg($message, $url = null, $interval = 5)
	{
		TPL::assign('message', $message);
		if ($url AND !is_website($url))
		{
			$url = url_rewrite($url);
		}
		TPL::assign('url_bit', $url);
		TPL::assign('interval', $interval);

		echo TPL::render('global/show_message');
		exit;
	}

	public static function redirect($url)
	{
		if (!$url)
		{
			$url = '/';
		}
		if (!is_website($url))
		{
			$url = url_rewrite($url);
		}

		header('Location: ' . $url);
		exit;
	}

	/**
	 * NO CACHE 文件头
	 *
	 * @param $type
	 * @param $charset
	 */
	public static function no_cache_header($type = 'text/html', $charset = 'utf-8')
	{
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Pragma: no-cache');
		header('Content-Type: ' . $type . '; charset=' . $charset . '');
	}

	public static function error_403()
	{
		if ($_POST['_post_type'] == 'ajax')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, 'HTTP/1.1 403 Forbidden'));
		}
		else
		{
			header('HTTP/1.1 403 Forbidden');

			echo TPL::render('global/error_403');
			exit;
		}
	}

	public static function error_404()
	{
		if ($_POST['_post_type'] == 'ajax')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, 'HTTP/1.1 404 Not Found'));
		}
		else
		{
			header('HTTP/1.1 404 Not Found');

			echo TPL::render('global/error_404');
			exit;
		}
	}
}
