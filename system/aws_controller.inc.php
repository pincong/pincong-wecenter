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
 * WeCenter 前台控制器
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */
class AWS_CONTROLLER
{
	public $user_id;
	public $user_info;

	public function __construct($process_setup = true)
	{
		if (AWS_APP::auth()->authenticate($this->user_info))
		{
			$this->user_id = $this->user_info['uid'];

			if ($this->user_info['forbidden'])
			{
				$this->model('login')->logout();
				H::redirect_msg(_t('抱歉, 你的账号已经被禁止登录'), '/');
			}

			$user_settings = unserialize_array($this->user_info['settings']);
			$this->user_info['default_timezone'] = $user_settings['timezone'] ?? null;

			if ($this->user_info['default_timezone'])
			{
				date_default_timezone_set($this->user_info['default_timezone']);
			}

			// 如果上次登录时间早于24小时, 则更新登录时间
			// TODO: 在管理后台添加选项
			/*$time_before = real_time() - (24 * 3600);
			if ($this->user_info['last_login'] < $time_before)
			{
				$this->model('account')->update_user_last_login($this->user_info['uid']);
			}*/
		}
		else
		{
			// 游客权限
			$user_group = $this->model('usergroup')->get_user_group_by_id(-1);
			$this->user_info['permission'] = $user_group['permission'];
		}

		UF::set_permissions($this->user_info['permission']);

		TPL::assign('user_id', $this->user_id);
		TPL::assign('user_info', $this->user_info);

		// 引入系统 CSS 文件
		TPL::import_css(array(
			'css/common.css',
			'css/link.css',
		));

		if ($lang = AWS_APP::lang()->get_language())
		{
			TPL::import_js('language/' . $lang . '.js');
		}

		// 引入系统 JS 文件
		TPL::import_js(array(
			'js/jquery.2.js',
			'js/jquery.form.js',
			'js/framework.js',
			'js/aws.js',
			'js/aw_template.js',
			'js/app.js',
		));

		// 产生面包屑导航数据
		$this->crumb(S::get('site_name'));

		// 执行控制器 Setup 动作
		if ($process_setup)
		{
			$this->setup();
		}
	}

	/**
	 * 控制器 Setup 动作
	 *
	 * 每个继承于此类库的控制器均会调用此函数
	 *
	 * @access	public
	 */
	public function setup() {}


	/**
	 * 调用系统 Model
	 *
	 * 于控制器中使用 $this->model('class')->function() 进行调用
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	public function model($model = null)
	{
		return AWS_APP::model($model);
	}

	/**
	 * 产生面包屑导航数据
	 *
	 * 产生面包屑导航数据并生成浏览器标题供前端使用
	 *
	 * @access	public
	 * @param	string
	 */
	public function crumb($name)
	{
		$this->crumbs[] = htmlspecialchars_decode($name);

		$title = '';
		foreach ($this->crumbs as $key => $crumb)
		{
			$title = $crumb . ' - ' . $title;
		}

		TPL::assign('page_title', htmlspecialchars(rtrim($title, ' - ')));

		return $this;
	}

}

/**
 * WeCenter 后台控制器
 *
 * @package		WeCenter
 * @subpackage	System
 * @category	Libraries
 * @author		WeCenter Dev Team
 */
class AWS_ADMIN_CONTROLLER extends AWS_CONTROLLER
{
	public $per_page;

	public function __construct()
	{
		parent::__construct(false);

		if ($_GET['app'] != 'admin')
		{
			return false;
		}

		$this->per_page = S::get_int('contents_per_page');

		TPL::import_clean();

		if ($lang = AWS_APP::lang()->get_language())
		{
			TPL::import_js('language/' . $lang . '.js');
		}

		TPL::import_js(array(
			'js/jquery.2.js',
			'js/jquery.form.js',
			'admin/js/aws_admin.js',
			'admin/js/aws_admin_template.js',
			'admin/js/framework.js',
			'admin/js/global.js',
		));

		TPL::import_css(array(
			'admin/css/common.css'
		));

		if (!$this->user_info['permission']['is_administrator'])
		{
			if ($_POST['_post_type'] == 'ajax')
			{
				H::ajax_error((_t('你没有访问权限, 请重新登录')));
			}
			else
			{
				H::redirect('/');
			}
		}

		if (in_array($_GET['act'], array(
			'login',
			'login_process',
		)))
		{
			return true;
		}

		if (!AWS_APP::auth()->is_admin())
		{
			if ($_POST['_post_type'] == 'ajax')
			{
				H::ajax_error((_t('会话超时, 请重新登录')));
			}
			else
			{
				H::redirect('/admin/login/');
			}
		}

		$this->setup();
	}
}
