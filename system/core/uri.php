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

class core_uri
{
	var $app = '';
	var $controller = '';
	var $action = '';

	var $app_dir = '';
	var $class_file = '';

	var $request_main = '';
	var $args_var_str = '';

	public function __construct()
	{
		// path including querystring
		$request_uri = $_SERVER['REQUEST_URI'];

		$script_name = $_SERVER['SCRIPT_NAME'];
		// e.g. /index.php?/login/ , /subdir/index.php?/login/
		$separator = $script_name . '?/';
		if (stripos($request_uri, $separator) !== 0)
		{
			$dirname = rtrim(dirname($script_name), '/\\');
			$dirname .= '/';
			// e.g. /subdir/?/login/ , /?/login/
			$separator = $dirname . '?/';
			if (stripos($request_uri, $separator) !== 0)
			{
				// e.g. /subdir/login/ , /login/
				$separator = $dirname;
				if (stripos($request_uri, $separator) !== 0)
				{
					return;
				}
			}
		}

		$request_main = substr($request_uri, strlen($separator));

		// e.g. /login
		if ($request_main !== '' AND
			strpos($request_main, '/') === false AND
			strpos($request_main, '-') === false AND
			strpos($request_main, '__') === false AND
			strpos($request_main, '?') === false AND
			strpos($request_main, '&') === false AND
			strpos($request_main, '=') === false)
		{
			$request_main .= '/';
		}

		$this->request_main = $request_main;
	}

	// parse controller and action
	public function parse()
	{
		$request = explode('?', $this->request_main, 2);
		if (count($request) == 1)
		{
			$request = explode('&', $this->request_main, 2);
		}

		$first = array_shift($request);
		$querystring = ltrim(implode($request), '?');

		if ($querystring)
		{
			parse_str($querystring, $query_string);
			foreach ($query_string AS $key => $val)
			{
				if (!$_GET[$key])
				{
					if (!strstr($val, '%'))
					{
						$_GET[$key] = $val;
					}
					else
					{
						$_GET[$key] = urldecode($val);
					}
				}
			}
		}

		// '/' 动作分割符
		$parts = explode('/', $first);

		// 删除空值
		foreach ($parts AS $key => $val)
		{
			if (strstr($val, '-') AND !$start_key)
			{
				$start_key = $key;
			}
			else if ($start_key)
			{
				$parts[$start_key] .= '/' . $val;
				unset($parts[$key]);
			}
		}

		$this->args_var_str = array_pop($parts);

		$num_parts = count($parts);
		if (!$num_parts)
		{
			$this->action = 'index';
			$this->controller = 'main';
			$this->app = 'explore';
		}
		else if ($num_parts == 1)
		{
			$this->action = 'index';
			$this->controller = 'main';
			$this->app = $parts[0];
		}
		else if ($num_parts == 2)
		{
			$this->action = $parts[1];
			$this->controller = 'main';
			$this->app = $parts[0];
		}
		else if ($num_parts == 3)
		{
			$this->action = $parts[2];
			$this->controller = $parts[1];
			$this->app = $parts[0];
		}
		else
		{
			$this->action = array_pop($parts);
			$this->controller = array_pop($parts);
			$this->app = implode('/', $parts);
		}

		$app_dir = ROOT_PATH . 'app/' . $this->app . '/';
		$class_file = $app_dir . $this->controller . '.php';
		if (!file_exists($class_file))
		{
			$app_dir = ROOT_PATH . 'plugins/' . $this->app . '/controller/';
			$class_file = $app_dir . $this->controller . '.php';
			if (!file_exists($class_file))
			{
				return false;
			}
		}

		$this->app_dir = $app_dir;
		$this->class_file = $class_file;

		$_GET['c'] = $this->controller;
		$_GET['act'] = $this->action;
		$_GET['app'] = $this->app;

		return true;
	}

	public function parse_args()
	{
		if ($args_var_str = $this->args_var_str)
		{
			if (!strstr($args_var_str, '-'))
			{
				$_GET['id'] = urldecode($args_var_str);
			}
			else
			{
				// '__' 变量分割符
				$parts = explode('__', $args_var_str);

				foreach ($parts as $val)
				{
					if (!$val)
					{
						continue;
					}

					// '-' 赋值分隔符
					@list($k, $v) = explode('-', $val, 2);

					if ($k)
					{
						if (!strstr($v, '%'))
						{
							$_GET[$k] = $v;
						}
						else
						{
							$_GET[$k] = urldecode($v);
						}
					}
				}
			}
		}

		foreach ($_GET AS $key => $val)
		{
			if (strstr($key, '/'))
			{
				unset($_GET[$key]);
			}
		}

		return true;
	}

}
