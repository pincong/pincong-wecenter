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


if (!defined('IN_ANWSION'))
{
	die;
}

class content extends AWS_ADMIN_CONTROLLER
{
	public function custom_css_action()
	{
		$setting_key = 'custom_css';
		$setting_title = _t('自定义 CSS');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function custom_head_top_action()
	{
		$setting_key = 'custom_head_top';
		$setting_title = _t('自定义 head 顶端');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function custom_head_action()
	{
		$setting_key = 'custom_head';
		$setting_title = _t('自定义 head 中间');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function custom_head_bottom_action()
	{
		$setting_key = 'custom_head_bottom';
		$setting_title = _t('自定义 head 底端');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function custom_body_top_action()
	{
		$setting_key = 'custom_body_top';
		$setting_title = _t('自定义 body 顶端');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function custom_body_bottom_action()
	{
		$setting_key = 'custom_body_bottom';
		$setting_title = _t('自定义 body 底端');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function statistic_code_action()
	{
		$setting_key = 'statistic_code';
		$setting_title = _t('网站统计代码');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function content_replacing_list_action()
	{
		$setting_key = 'content_replacing_list';
		$setting_title = _t('用户内容替换列表');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function html_replacing_list_action()
	{
		$setting_key = 'html_replacing_list';
		$setting_title = _t('网页内容替换列表');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function sensitive_words_action()
	{
		$setting_key = 'sensitive_words';
		$setting_title = _t('敏感词列表');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('content_' . $setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}
}