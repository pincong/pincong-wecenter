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
		if (!$item_info = $this->model('post')->get_thread_or_reply_info_by_id(H::GET('item_type'), H::GET('item_id')))
		{
			H::error_404();
		}

		$this->crumb(_t('投票记录'));

		TPL::output('vote/log');
	}

	public function list_logs_action()
	{
		if (!$item_info = $this->model('post')->get_thread_or_reply_info_by_id(H::GET('item_type'), H::GET('item_id')))
		{
			H::error_404();
		}

		$log_list = $this->model('vote')->list_logs(H::GET('item_type'), H::GET('item_id'), H::GET('page'), S::get_int('contents_per_page'));

		TPL::assign('list', $log_list);

		TPL::output('vote/list_logs_template');
	}

}
