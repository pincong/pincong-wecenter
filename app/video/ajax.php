<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
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

	public function remove_video_action()
	{
		if (!$this->user_info['permission']['is_moderator'])
		{
			H::ajax_error((_t('对不起, 你没有删除影片的权限')));
		}

		if ($video_info = $this->model('post')->get_thread_info_by_id('video', H::POST('video_id')))
		{
			$this->model('video')->clear_video($video_info['id'], null);
		}

		H::ajax_location(url_rewrite('/'));
	}

}