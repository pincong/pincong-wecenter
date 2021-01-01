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
	public static function get_cookie($name, $default = false)
	{
		if (isset($_COOKIE[G_COOKIE_PREFIX . '_' . $name]))
		{
			return $_COOKIE[G_COOKIE_PREFIX . '_' . $name];
		}

		return $default;
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
		//H::no_cache_header('text/json');

		echo str_replace(array("\r", "\n", "\t"), '', json_encode($array));
		exit;
	}

	public static function ajax_error($err)
	{
		H::ajax_json_output(array(
			'err' => $err,
			'errno' => -1,
		));
	}

	public static function ajax_success()
	{
		H::ajax_json_output(array(
			'errno' => 1,
		));
	}

	public static function ajax_response($data)
	{
		H::ajax_json_output(array(
			'rsm' => $data,
		));
	}

	public static function ajax_location($url)
	{
		H::ajax_json_output(array(
			'url' => $url,
			'rsm' => array(
				'url' => $url,
			),
		));
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
			H::ajax_error('HTTP/1.1 403 Forbidden');
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
			H::ajax_error('HTTP/1.1 404 Not Found');
		}
		else
		{
			header('HTTP/1.1 404 Not Found');

			echo TPL::render('global/error_404');
			exit;
		}
	}


	public static function is_post()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}


	public static function GET($key)
	{
		if (!isset($_GET[$key]))
		{
			return null;
		}
		return $_GET[$key];
	}

	public static function POST($key)
	{
		if (!isset($_POST[$key]))
		{
			return null;
		}
		return $_POST[$key];
	}

	public static function GET_I($key)
	{
		if (!isset($_GET[$key]))
		{
			return 0;
		}
		return self::_convert_request($_GET[$key], 'i');
	}

	public static function GET_D($key)
	{
		if (!isset($_GET[$key]))
		{
			return 0;
		}
		return self::_convert_request($_GET[$key], 'd');
	}

	public static function GET_S($key)
	{
		if (!isset($_GET[$key]))
		{
			return '';
		}
		return self::_convert_request($_GET[$key], 's');
	}

	public static function POST_I($key)
	{
		if (!isset($_POST[$key]))
		{
			return 0;
		}
		return self::_convert_request($_POST[$key], 'i');
	}

	public static function POST_D($key)
	{
		if (!isset($_POST[$key]))
		{
			return 0;
		}
		return self::_convert_request($_POST[$key], 'd');
	}

	public static function POST_S($key)
	{
		if (!isset($_POST[$key]))
		{
			return '';
		}
		return self::_convert_request($_POST[$key], 's');
	}

	public static function POSTS_I($key)
	{
		if (!isset($_POST[$key]))
		{
			return array();
		}
		return self::_convert_request_array($_POST[$key], 'i');
	}

	public static function POSTS_D($key)
	{
		if (!isset($_POST[$key]))
		{
			return array();
		}
		return self::_convert_request_array($_POST[$key], 'd');
	}

	public static function POSTS_S($key)
	{
		if (!isset($_POST[$key]))
		{
			return array();
		}
		return self::_convert_request_array($_POST[$key], 's');
	}

	private static function _convert_request($val, $type)
	{
		if ($type == 'i') // int
		{
			return intval($val);
		}
		else if ($type == 'd') // double
		{
			$val = floatval($val);
			if (is_infinite($val) OR is_nan($val))
			{
				return 0;
			}
			return $val;
		}

		if (!is_string($val))
		{
			return '';
		}
		return remove_invisible_characters(multibyte_trim($val));
	}

	private static function _convert_request_array($data, $type)
	{
		if (is_array($data))
		{
			foreach ($data as &$val)
			{
				$val = self::_convert_request($val, $type);
			}
			unset($val);
			return $data;
		}
		return array();
	}

}
