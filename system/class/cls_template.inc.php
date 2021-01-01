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
				// 其实这两个功能是一样的, 为了避免替换内容过多难以维护而设置两个list
				if (get_setting('html_content_replace') == 'Y')
				{
					$replacing_list = get_key_value_pairs('html_replacing_list', '<>', true);
					H::content_replace($output, $replacing_list);
				}
				if (get_setting('sensitive_words_replace') == 'Y')
				{
					$replacing_list = get_key_value_pairs('sensitive_words', '<>', true);
					H::content_replace($output, $replacing_list);
				}
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
				if (substr($val, 0, 4) == 'css/')
				{
					$val = str_replace('css/', 'css/default/', $val);
				}

				if (!is_website($val) AND !is_uri_path($val))
				{
					$val = G_STATIC_URL . '/' . $val;
				}

				self::$view->_import_css_files[] = $val;
			}
		}
		else
		{
			if (substr($path, 0, 4) == 'css/')
			{
				$path = str_replace('css/', 'css/default/', $path);
			}

			if (!is_website($path) AND !is_uri_path($path))
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
				if (!is_website($val) AND !is_uri_path($val))
				{
					$val = G_STATIC_URL . '/' . $val;
				}

				self::$view->_import_js_files[] = $val;
			}
		}
		else
		{
			if (!is_website($path) AND !is_uri_path($path))
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
