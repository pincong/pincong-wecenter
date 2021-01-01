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
		if (!$this->model('favorite')->check_item_type($_POST['item_type']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if (!check_user_operation_interval('favorite', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('content')->get_thread_or_reply_info_by_id($_POST['item_type'], $_POST['item_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		set_user_operation_last_time('favorite', $this->user_id);

		$this->model('favorite')->add_favorite($_POST['item_id'], $_POST['item_type'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_favorite_item_action()
	{
		if (!$this->model('favorite')->check_item_type($_POST['item_type']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		$this->model('favorite')->remove_favorite_item($_POST['item_id'], $_POST['item_type'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}