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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403
		return $rule_action;
	}

	public function index_action()
	{
		$this->crumb(_t('通知'));

		TPL::output('notification/index');
	}

	public function notify_action()
	{
		H::no_cache_header();

		$list = $this->model('notification')->list_notifications($this->user_id, 0, 1, 5);

		TPL::assign('list', $list);
		if (H::GET('template') == 'list')
		{
			TPL::output("notification/list_template");
		}
		else
		{
			TPL::output("notification/notify_template");
		}
	}

	public function list_action()
	{
		H::no_cache_header();

		$list = $this->model('notification')->list_notifications($this->user_id, H::GET('flag'), H::GET('page'), S::get_int('notifications_per_page'));

		TPL::assign('list', $list);
		TPL::output("notification/list_template");
	}

}