<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package		WeCenter Framework
 * @author		WeCenter Dev Team
 * @copyright	Copyright (c) 2011 - 2014, WeCenter, Inc.
 * @license		http://www.wecenter.com/license/
 * @link		http://www.wecenter.com/
 * @since		Version 1.0
 * @filesource
 */


/**
 * WeCenter 系统初始化文件
 *
 * 处理基本类库与请求
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Front-controller
 * @author		WeCenter Dev Team
 */
class AWS_APP
{
	private static $config;
	private static $db;
	private static $form;
	private static $upload;
	private static $image;
	private static $pagination;
	private static $cache;
	private static $lang;
	private static $captcha;
	private static $crypt;
	private static $token;
	private static $auth;
	private static $uri;
	private static $theme;

	private static $models = array();

	private static $debug_mode;
	public static $_debug = array();

	/**
	 * 系统运行
	 */
	public static function run()
	{
		self::init();

		self::$uri = load_class('core_uri');
		self::$uri->parse();
		$action = self::$uri->action;

		$controller_instance = self::create_controller();
		$action_method = $action . '_action';

		// 判断
		if (!is_object($controller_instance) OR !method_exists($controller_instance, $action_method))
		{
			H::error_404();
		}

		if (method_exists($controller_instance, 'get_access_rule'))
		{
			$access_rule = $controller_instance->get_access_rule();
		}

		$uid = $controller_instance->user_id;

		// 判断访问规则使用白名单还是黑名单, 默认使用白名单
		// 白名单: $access_rule['actions'] 以外的都不允许
		// 黑名单: $access_rule['actions'] 以外的都允许
		if ($access_rule)
		{
			if (isset($access_rule['rule_type']) AND $access_rule['rule_type'] == 'black')
			{
				if (isset($access_rule['actions']) AND in_array($action, $access_rule['actions']))
				{
					// action 在黑名单中, 不允许
					self::check_login($uid, $access_rule['redirect'] ?? null);
				}
			}
			else // 默认使用白名单
			{
				if (!isset($access_rule['actions']) OR !in_array($action, $access_rule['actions']))
				{
					// action 不在白名单中, 不允许
					self::check_login($uid, $access_rule['redirect'] ?? null);
				}
			}
		}
		else
		{
			self::check_login($uid);
		}

		// 执行
		$controller_instance->$action_method();
	}

	/**
	 * 检查用户登录状态
	 *
	 * 检查用户登录状态并带领用户进入相关操作
	 */
	private static function check_login($uid, $redirect = true)
	{
		if (!$uid)
		{
			if ($redirect === false) // null 也跳转
			{
				H::error_403();
			}
			elseif (defined('IN_AJAX') OR $_POST['_post_type'] == 'ajax')
			{
				H::ajax_error(_t('会话超时, 请重新登录'));
			}
			else
			{
				H::redirect('/login/');
			}
		}
	}

	/**
	 * 系统初始化
	 */
	private static function init()
	{
		self::$debug_mode = !!self::config()->get('system')->debug;

		self::set_handlers();

		self::$config = load_class('core_config');
		self::$db = load_class('core_db');

		S::init();

		if ($default_timezone = S::get('default_timezone'))
		{
			date_default_timezone_set($default_timezone);
		}

		if ($img_url = S::get('img_url'))
		{
			define('G_STATIC_URL', $img_url);
		}
		else
		{
			define('G_STATIC_URL', base_url() . '/static');
		}

		if (self::$debug_mode)
		{
			if ($cornd_timer = self::cache()->getGroup('crond'))
			{
				foreach ($cornd_timer AS $cornd_tag)
				{
					if ($cornd_runtime = self::cache()->get($cornd_tag))
					{
						AWS_APP::debug_log('crond', 0, 'Tag: ' . str_replace('crond_timer_', '', $cornd_tag) . ', Last run time: ' . date('Y-m-d H:i:s', $cornd_runtime));
					}
				}
			}
		}
	}

	// 创建 Controller
	private static function create_controller()
	{
		$app_dir = ROOT_PATH . 'app/' . self::$uri->app . '/';
		$class_file = $app_dir . self::$uri->controller . '.php';
		if (!file_exists($class_file))
		{
			$app_dir = ROOT_PATH . 'plugins/' . self::$uri->app . '/controller/';
			$class_file = $app_dir . self::$uri->controller . '.php';
			if (!file_exists($class_file))
			{
				return false;
			}
		}

		require_once $class_file;

		$hook_dir = ROOT_PATH . 'hooks/' . self::$uri->app . '/';
		$hook_file = $hook_dir . self::$uri->controller . '.php';
		if (file_exists($hook_file))
		{
			require_once $hook_file;
			$controller_class = self::$uri->controller . '_hook';
		}
		else
		{
			$controller_class = self::$uri->controller;
		}

		if (!class_exists($controller_class, false))
		{
			return false;
		}

		// 解析路由查询参数
		self::$uri->parse_args();

		return new $controller_class();
	}

	private static function set_handlers()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline) {
			if (!(error_reporting() & $errno))
			{
				return false;
			}

		// error was suppressed with the @-operator
		if (0 === error_reporting())
		{
			return false;
		}

			if (!self::$debug_mode)
			{
				return;
			}
			$message = friendly_error_type($errno) . ": " . $errstr . " File: " . $errfile . " Line: " . $errline;
			AWS_APP::debug_log('error', 0, $message);
		});

		set_exception_handler(function ($exception) {
			$message = "Application error\n------\n\nMessage: " . $exception->getMessage() . "\nFile: " . $exception->getFile() . "\nLine: " . $exception->getLine() . "\nURI: " . $_SERVER['REQUEST_URI'] . "\n------\n" . $exception->getTraceAsString();
			show_error($message);
		});
	}

	/**
	 * 格式化系统返回消息
	 *
	 * 格式化系统返回的消息 json 数据包给前端进行处理
	 *
	 * @access	public
	 * @param	array
	 * @param	integer
	 * @return	string
	 */
	public static function RSM($rsm, $errno = 0, $err = '')
	{
		return array(
			'rsm' => $rsm,
			'errno' => (int)$errno,
			'err' => $err,
		);
	}

	/**
	 * 获取系统配置
	 *
	 * 调用 core/config.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function config()
	{
		if (!self::$config)
		{
			self::$config = load_class('core_config');
		}

		return self::$config;
	}

	/**
	 * 获取系统上传类
	 *
	 * 调用 core/upload.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function upload()
	{
		if (!self::$upload)
		{
			self::$upload = load_class('core_upload');
		}

		return self::$upload;
	}

	/**
	 * 获取系统图像处理类
	 *
	 * 调用 core/image.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function image()
	{
		if (!self::$image)
		{
			self::$image = load_class('core_image');
		}

		return self::$image;
	}

	/**
	 * 获取系统语言处理类
	 *
	 * 调用 core/lang.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function lang()
	{
		if (!self::$lang)
		{
			self::$lang = load_class('core_lang');
		}

		return self::$lang;
	}

	/**
	 * 获取系统验证码处理类
	 *
	 * 调用 core/captcha.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function captcha()
	{
		if (!self::$captcha)
		{
			self::$captcha = load_class('core_captcha');
		}

		return self::$captcha;
	}

	/**
	 * 获取系统缓存处理类
	 *
	 * 调用 core/cache.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function cache()
	{
		if (!self::$cache)
		{
			self::$cache = load_class('core_cache');
		}

		return self::$cache;
	}

	/**
	 * 获取系统表单提交验证处理类
	 *
	 * 调用 core/form.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function form()
	{
		if (!self::$form)
		{
			self::$form = load_class('core_form');
		}

		return self::$form;
	}

	/**
	 * 获取系统分页处理类
	 *
	 * 调用 core/pagination.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function pagination()
	{
		if (!self::$pagination)
		{
			self::$pagination = load_class('core_pagination');
		}

		return self::$pagination;
	}

	/**
	 * 调用系统数据库
	 *
	 * 此功能基于 PDO
	 *
	 * @access	public
	 * @return	object
	 */
	public static function db()
	{
		if (!self::$db)
		{
			self::$db = load_class('core_db');
		}

		return self::$db;
	}

	/**
	 * 加密处理类
	 *
	 * 调用 core/crypt.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function crypt()
	{
		if (!self::$crypt)
		{
			self::$crypt = load_class('core_crypt');
		}

		return self::$crypt;
	}

	/**
	 * token 处理类
	 *
	 * 调用 core/token.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function token()
	{
		if (!self::$token)
		{
			self::$token = load_class('core_token');
		}

		return self::$token;
	}

	/**
	 * auth 处理类
	 *
	 * 调用 core/auth.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function auth()
	{
		if (!self::$auth)
		{
			self::$auth = load_class('core_auth');
		}

		return self::$auth;
	}

	/**
	 * theme 处理类
	 *
	 * 调用 core/theme.php
	 *
	 * @access	public
	 * @return	object
	 */
	public static function theme()
	{
		if (!self::$theme)
		{
			self::$theme = load_class('core_theme');
		}

		return self::$theme;
	}

	/**
	 * 记录系统 Debug 事件
	 *
	 * 打开 debug 功能后相应事件会在页脚输出
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 */
	public static function debug_log($type, $expend_time, $message)
	{
		$root_dir = rtrim(ROOT_PATH, '/\\');
		$message = str_replace($root_dir, '', $message);

		self::$_debug[$type][] = array(
			'expend_time' => $expend_time,
			'log_time' => microtime(true),
			'message' => htmlspecialchars($message)
		);
	}

	/**
	 * 调用系统 Model
	 *
	 * 根据命名规则调用相应的 Model 并初始化类库保存于 self::$models 数组, 防止重复初始化
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	public static function model($model_class = null)
	{
		if (!$model_class)
		{
			$model_class = 'AWS_MODEL';
		}
		else if (! strstr($model_class, '_class'))
		{
			$model_class .= '_class';
		}

		if (! isset(self::$models[$model_class]))
		{
			self::$models[$model_class] = new $model_class();
		}

		return self::$models[$model_class];
	}
}
