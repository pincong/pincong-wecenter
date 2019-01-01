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

	public function flag_user_action()
	{
		if (!$this->user_info['permission']['flag_user'])
		{
			HTTP::error_403();
		}

		if (!$user = $this->model('account')->get_user_info_by_uid($_GET['uid']))
		{
			HTTP::error_404();
		}

		$user['data'] = unserialize_array($user['extra_data']);
		TPL::assign('user', $user);

		TPL::output("user/flag_user_template");
	}

}
