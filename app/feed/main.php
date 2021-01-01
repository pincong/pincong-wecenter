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
				'index'
			);
		}

		return $rule_action;
	}

	public function setup()
	{
		header('Content-type: text/xml; charset=UTF-8');

		date_default_timezone_set('UTC');
	}

	public function index_action()
	{
		TPL::assign('list', $this->model('threadindex')->get_posts_list('question', 1, 20, null, H::GET('category')));

		TPL::output('global/feed');
	}
}