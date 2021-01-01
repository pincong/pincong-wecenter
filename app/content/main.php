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

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'log',
				'list_logs'
			);
		}

		return $rule_action;
	}

	public function log_action()
	{
		$this->crumb(_t('修改记录'));

		TPL::output('content/log');
	}

	public function list_logs_action()
	{
		$log_list = $this->model('content')->list_logs(H::GET('thread_type'), H::GET('thread_id'), H::GET('item_type'), H::GET('item_id'), H::GET('uid'), H::GET('page'), S::get_int('contents_per_page'));

		TPL::assign('list', $log_list);

		TPL::output('content/list_logs_template');
	}

}
