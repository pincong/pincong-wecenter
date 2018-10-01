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

		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('我的收藏'), '/favorite/');
	}

	public function index_action()
	{

		//边栏可能感兴趣的人或话题
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'favorite/index'))
		{
			$recommend_users_topics = $this->model('module')->recommend_users_topics($this->user_id);

			TPL::assign('sidebar_recommend_users_topics', $recommend_users_topics);
		}

		if ($action_list = $this->model('favorite')->get_item_list(null, $this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))))
		{
			foreach ($action_list AS $key => $val)
			{
				$item_ids[] = $val['item_id'];
			}

			TPL::assign('list', $action_list);
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/favorite/'),
			'total_rows' => $this->model('favorite')->count_favorite_items($this->user_id),
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::output('favorite/index');
	}
}