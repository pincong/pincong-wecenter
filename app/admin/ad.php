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

class ad extends AWS_ADMIN_CONTROLLER
{
	public function ad_space_a1_action()
	{
		$setting_key = 'ad_space_a1';
		$setting_title = _t('广告位 A1');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list($setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function ad_space_a2_action()
	{
		$setting_key = 'ad_space_a2';
		$setting_title = _t('广告位 A2');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list($setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function ad_space_a3_action()
	{
		$setting_key = 'ad_space_a3';
		$setting_title = _t('广告位 A3');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list($setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function ad_space_b1_action()
	{
		$setting_key = 'ad_space_b1';
		$setting_title = _t('广告位 B1 (未使用)');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list($setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function ad_space_b2_action()
	{
		$setting_key = 'ad_space_b2';
		$setting_title = _t('广告位 B2 (未使用)');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list($setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}

	public function ad_space_b3_action()
	{
		$setting_key = 'ad_space_b3';
		$setting_title = _t('广告位 B3 (未使用)');
		$this->crumb($setting_title);
		TPL::assign('setting_key', $setting_key);
		TPL::assign('setting_title', $setting_title);
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list($setting_key));
		TPL::assign('settings', S::get_all());
		TPL::output('admin/custom_content');
	}
}