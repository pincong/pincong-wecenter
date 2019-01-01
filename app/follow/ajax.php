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
		HTTP::no_cache_header();
	}

	public function follow_people_action()
	{
		if (! $_POST['uid'] OR $_POST['uid'] == $this->user_id)
		{
			die;
		}

		// 首先判断是否存在关注
		if ($this->model('follow')->user_follow_check($this->user_id, $_POST['uid']))
		{
			$action = 'remove';

			$this->model('follow')->user_follow_del($this->user_id, $_POST['uid']);
		}
		else
		{
			if (!$this->user_info['permission']['follow_people'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不关注其他人')));
			}

			$action = 'add';

			if ($this->model('follow')->user_follow_add($this->user_id, $_POST['uid']))
			{
				$this->model('notify')->send($this->user_id, $_POST['uid'], notify_class::TYPE_PEOPLE_FOCUS, notify_class::CATEGORY_PEOPLE, $this->user_id, array(
					'from_uid' => $this->user_id
				));
			}

		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $action
		), 1, null));
	}
}