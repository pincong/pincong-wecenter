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

	public function follow_people_action()
	{
		if (! H::POST('uid') OR H::POST('uid') == $this->user_id)
		{
			die;
		}

		// 首先判断是否存在关注
		if ($this->model('follow')->user_follow_check($this->user_id, H::POST('uid')))
		{
			$action = 'remove';

			$this->model('follow')->user_follow_del($this->user_id, H::POST('uid'));
		}
		else
		{
			if (!$this->user_info['permission']['follow_people'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', _t('你的声望还不关注其他人')));
			}

			$action = 'add';

			if ($this->model('follow')->user_follow_add($this->user_id, H::POST('uid')))
			{
				$this->model('notification')->send($this->user_id, H::POST('uid'), 'FOLLOW_USER');
			}

		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $action
		), 1, null));
	}
}