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
				'rule'
			);
		}

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function rule_action()
	{
		$this->crumb(AWS_APP::lang()->_t(S::get('currency_rule_name')));

		TPL::output('currency/rule');
	}

	public function list_logs_action()
	{
		if ($log = $this->model('currency')->fetch_all('currency_log', 'uid = ' . $this->user_id, 'id DESC', (intval($_GET['page']) * 10) . ', 10'))
		{
			foreach ($log AS $key => $val)
			{
				$parse_items[$val['id']] = array(
					'item_id' => $val['item_id'],
					'item_type' => $val['item_type']
				);
			}

			TPL::assign('log', $log);
			TPL::assign('log_detail', $this->model('currency')->parse_log_items($parse_items));
		}

		TPL::output('currency/list_logs_template');
	}

}