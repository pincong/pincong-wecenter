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
		$this->crumb(_t('我的收藏'));
	}

	public function index_action()
	{
		TPL::assign('list', $this->model('favorite')->get_item_list($this->user_id, H::GET('page'), S::get_int('contents_per_page')));

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/favorite/'),
			'total_rows' => $this->model('favorite')->total_rows(),
			'per_page' => S::get_int('contents_per_page')
		)));

		TPL::output('favorite/index');
	}
}