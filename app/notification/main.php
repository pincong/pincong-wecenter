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
		$this->crumb(AWS_APP::lang()->_t('通知'));

		TPL::output('notification/index');
	}

	public function notify_action()
	{
		HTTP::no_cache_header();

		$list = $this->model('notification')->list_notifications($this->user_id, 0, 1, 5);

		if (!$list AND $this->user_info['notification_unread'] != 0)
		{
			$this->model('account')->update_notification_unread($this->user_id);
		}

		TPL::assign('list', $list);
		TPL::output("notification/notify_template");
	}

	public function list_action()
	{
		HTTP::no_cache_header();

		$list = $this->model('notification')->list_notifications($this->user_id, $_GET['flag'], $_GET['page'], get_setting('notifications_per_page'));

		if (!$list AND $this->user_info['notification_unread'] != 0)
		{
			$this->model('account')->update_notification_unread($this->user_id);
		}

		TPL::assign('list', $list);
		TPL::output("notification/list_template");
	}

}