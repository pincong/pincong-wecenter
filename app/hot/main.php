<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   ? 2011 - 2014 WeCenter. All Rights Reserved
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
		$rule_action['rule_type'] = "white";

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index',
				'list'
			);
		}

		return $rule_action;
	}

	public function index_action()
	{
		$this->crumb(_t('精选'));

		// 导航
		TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('hot'));
		
		// 边栏热门话题
		TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($category_info['id']));
		
		// 边栏功能
		TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());
		
		TPL::output('hot/index');
	}

	public function list_action()
	{
		$per_page = S::get_int('index_per_page');

		TPL::assign('list', $this->model('activity')->list_hot_activities(H::GET('category'), H::GET('page'), $per_page));

		TPL::output('hot/template');
	}

}