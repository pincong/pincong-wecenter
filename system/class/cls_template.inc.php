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

class TPL
{
	public static $template_ext = '.tpl.htm';

	public static $view;

	public static $output_matchs;

	public static $template_path;

	public static $in_app = false;

	public static function initialize()
	{
		if (!is_object(self::$view))
		{
			self::$template_path = realpath(ROOT_PATH . 'views/');

			self::$view = new Savant3(
				array(
					'template_path' => array(realpath(ROOT_PATH), self::$template_path),
					//'filters' => array('Savant3_Filter_trimwhitespace', 'filter')
				)
			);

			if (file_exists(AWS_PATH . 'config.inc.php') AND class_exists('AWS_APP', false))
			{
				self::$in_app = true;
			}
		}

		return self::$view;
	}

	// 生成基本的 HTML
	public static function &render($template_filename, $plugin_name = null)
	{
		if (!strstr($template_filename, self::$template_ext))
		{
			$template_filename .= self::$template_ext;
		}

		if ($plugin_name)
		{
			$display_template_filename = 'plugins/' .$plugin_name. '/view/' . $template_filename;
		}
		else
		{
			$display_template_filename = 'default/' . $template_filename;
		}

		if (self::$in_app)
		{
			if (!$plugin_name AND get_setting('ui_style') != 'default')
			{
				$custom_template_filename =  get_setting('ui_style') . '/' . $template_filename;

				if (file_exists(self::$template_path . '/' . $custom_template_filename))
				{
					$display_template_filename =  $custom_template_filename;
				}
			}

			self::assign('template_name', get_setting('ui_style'));

			if (!self::$view->_meta_keywords)
			{
				self::set_meta('keywords', get_setting('keywords'));
			}

			if (!self::$view->_meta_description)
			{
				self::set_meta('description', get_setting('description'));
			}
		}
		else
		{
			self::assign('template_name', 'default');
		}

		return self::$view->getOutput($display_template_filename);
	}

	// 进行一些处理 关键词替换等
	public static function &process($template_filename, $plugin_name = null)
	{
		$output = self::render($template_filename, $plugin_name);

		if (self::$in_app AND basename($template_filename) != 'debuger.tpl.htm')
		{
			$template_dirs = explode('/', $template_filename);

			if ($template_dirs[0] != 'admin')
			{
				H::sensitive_words_replace($output);
			}

			if (get_setting('url_rewrite_enable') != 'Y' OR $template_dirs[0] == 'admin')
			{
				//$output = preg_replace('/(href|action)=([\"|\'])(?!http)(?!mailto)(?!file)(?!ftp)(?!javascript)(?![\/|\#])(?!\.\/)([^\"\']+)([\"|\'])/is', '\1=\2' . base_url() . '/' . G_INDEX_SCRIPT . '\3\4', $output);
				$output = preg_replace('/<([^>]*?)(href|action)=([\"|\'])(?!http)(?!mailto)(?!file)(?!ftp)(?!javascript)(?![\/|\#])(?!\.\/)([^\"\']+)([\"|\'])([^>]*?)>/is', '<\1\2=\3' . base_url() . '/' . G_INDEX_SCRIPT . '\4\5\6>', $output);
			}

			if ($request_routes = get_request_route() AND $template_dirs[0] != 'admin' AND get_setting('url_rewrite_enable') == 'Y')
			{
				foreach ($request_routes as $key => $val)
				{
					$output = preg_replace("/href=[\"|']" . $val[0] . "[\#]/", "href=\"" . $val[1] . "#", $output);
					$output = preg_replace("/href=[\"|']" . $val[0] . "[\"|']/", "href=\"" . $val[1] . "\"", $output);
				}
			}

			if (get_setting('url_rewrite_enable') == 'Y' AND $template_dirs[0] != 'admin')
			{
				//$output = preg_replace('/(href|action)=([\"|\'])(?!mailto)(?!file)(?!ftp)(?!http)(?!javascript)(?![\/|\#])(?!\.\/)([^\"\']+)([\"|\'])/is', '\1=\2' . base_url() . '/' . '\3\4', $output);
				$output = preg_replace('/<([^>]*?)(href|action)=([\"|\'])(?!mailto)(?!file)(?!ftp)(?!http)(?!javascript)(?![\/|\#])(?!\.\/)([^\"\']{0,})([\"|\'])([^>]*?)>/is', '<\1\2=\3' . base_url() . '/' . '\4\5\6>', $output);
			}

			//$output = preg_replace("/([a-zA-Z0-9]+_?[a-zA-Z0-9]+)-__|(__[a-zA-Z0-9]+_?[a-zA-Z0-9]+)-$/i", '', $output);

			$output = preg_replace('/[a-zA-Z0-9]+_?[a-zA-Z0-9]*\-__/', '', $output);
			$output = preg_replace('/(__)?[a-zA-Z0-9]+_?[a-zA-Z0-9]*\-([\'|"])/', '\2', $output);

			if (AWS_APP::config()->get('system')->debug)
			{
				$output .= "\r\n<!-- Template End: " . $display_template_filename . " -->\r\n";
			}
		}

		return $output;
	}

	// 显示
	public static function output($template_filename, $plugin_name = null)
	{
		echo self::process($template_filename, $plugin_name);
		exit;
	}

	// 包含其它模板文件
	public static function include($template_filename, $plugin_name = null)
	{
		echo self::render($template_filename, $plugin_name);
	}

	public static function set_meta($tag, $value)
	{
		self::assign('_meta_' . $tag, $value);
	}

	public static function assign($name, $value)
	{
		self::$view->$name = $value;
	}

	public static function val($name)
	{
		return self::$view->$name;
	}

	public static function import_css($path)
	{
		if (is_array($path))
		{
			foreach ($path AS $key => $val)
			{
				if (substr($val, 0, 4) == 'css/' AND !strstr($val, '/admin/'))
				{
					$val = str_replace('css/', 'css/default/', $val);
				}

				if (substr($val, 0, 4) != 'http')
				{
					$val = G_STATIC_URL . '/' . $val;
				}

				self::$view->_import_css_files[] = $val;
			}
		}
		else
		{
			if (substr($path, 0, 4) == 'css/' AND !strstr($path, '/admin/'))
			{
				$path = str_replace('css/', 'css/default/', $path);
			}

			if (substr($path, 0, 4) != 'http')
			{
				$path = G_STATIC_URL . '/' . $path;
			}

			self::$view->_import_css_files[] = $path;
		}
	}

	public static function import_js($path)
	{
		if (is_array($path))
		{
			foreach ($path AS $key => $val)
			{
				if (substr($val, 0, 4) != 'http')
				{
					$val = G_STATIC_URL . '/' . $val;
				}

				self::$view->_import_js_files[] = $val;
			}
		}
		else
		{
			if (substr($path, 0, 4) != 'http')
			{
				$path = G_STATIC_URL . '/' . $path;
			}

			self::$view->_import_js_files[] = $path;
		}
	}

	public static function import_clean($type = false)
	{
		if ($type == 'js' OR !$type)
		{
			self::$view->_import_js_files = null;
		}

		if ($type == 'css' OR !$type)
		{
			self::$view->_import_css_files = null;
		}
	}

	public static function fetch($template_filename)
	{
		if (self::$in_app)
		{
			if (get_setting('ui_style') != 'default')
			{
				$custom_template_file = self::$template_path . '/' . get_setting('ui_style') . '/' . $template_filename . self::$template_ext;

				if (file_exists($custom_template_file))
				{
					return file_get_contents($custom_template_file);
				}
			}
		}

		return file_get_contents(self::$template_path . '/default/' . $template_filename . self::$template_ext);
	}

}
