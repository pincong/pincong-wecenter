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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function setup()
	{
		H::no_cache_header();
	}

	public function add_favorite_action()
	{
		if (!$this->model('favorite')->check_item_type(H::POST('item_type')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if (!check_user_operation_interval('favorite', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$item_info = $this->model('post')->get_thread_or_reply_info_by_id(H::POST('item_type'), H::POST('item_id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id)
		{
			if (!$this->user_info['permission']['follow_thread'])
			{
				H::ajax_error((_t('你的声望还不够')));
			}
		}

		set_user_operation_last_time('favorite', $this->user_id);

		$this->model('favorite')->add_favorite(H::POST('item_id'), H::POST('item_type'), $this->user_id);

		H::ajax_success();
	}

	public function remove_favorite_item_action()
	{
		if (!$this->model('favorite')->check_item_type(H::POST('item_type')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		$this->model('favorite')->remove_favorite_item(H::POST('item_id'), H::POST('item_type'), $this->user_id);

		H::ajax_success();
	}

}