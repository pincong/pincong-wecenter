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
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}

	public function img_action()
	{
		if (!$_GET['id'])
		{
			die;
		}
		TPL::assign('url', htmlspecialchars($_GET['id']));
		TPL::output('url/img');
	}

	public function link_action()
	{
		if (!$_GET['id'] OR is_inside_url($_GET['id']))
		{
			die;
		}
		TPL::assign('url', htmlspecialchars($_GET['id']));
		TPL::output('url/link');
	}
}