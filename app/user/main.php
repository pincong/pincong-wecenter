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

		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'admin_log',
				'list_admin_logs'
			);
		}

		return $rule_action;
	}

	public function admin_log_action()
	{
		$this->crumb(_t('管理记录'));

		TPL::output('user/admin_log');
	}

	public function list_admin_logs_action()
	{
		$log_list = $this->model('user')->list_admin_logs(H::GET('uid'), H::GET('admin_uid'), H::GET('type'), H::GET('status'), H::GET('page'), S::get_int('contents_per_page'));

		TPL::assign('list', $log_list);

		TPL::output('user/list_admin_logs_template');
	}

}
