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

class edit extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403
		return $rule_action;
	}

	public function remark_action()
	{
		if (!$this->user_info['permission']['kb_explore'])
		{
			H::error_403();
		}

		if (!$item_info = $this->model('kb')->get(H::GET('id')))
		{
			H::error_404();
		}

		TPL::assign('item_info', $item_info);

		TPL::output("kb/remark_template");
	}

	public function modify_action()
	{
		if (!$this->user_info['permission']['kb_explore'])
		{
			H::error_403();
		}

		if (!$item_info = $this->model('kb')->get(H::GET('id')))
		{
			H::error_404();
		}

		TPL::assign('item_info', $item_info);

		TPL::output("kb/modify_template");
	}

}
