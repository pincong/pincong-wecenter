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
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('我的收藏'));
	}

	public function index_action()
	{
		TPL::assign('list', $this->model('favorite')->get_item_list($this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))));

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/favorite/'),
			'total_rows' => $this->model('favorite')->count_favorite_items($this->user_id),
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::output('favorite/index');
	}
}