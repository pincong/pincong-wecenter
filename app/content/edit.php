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

	public function change_category_action()
	{
		TPL::assign('category_current_id', H::GET_I('category_id'));
		TPL::assign('category_list', $this->model('category')->get_allowed_categories_change($this->user_info));

		TPL::output("content/change_category_template");
	}

}
