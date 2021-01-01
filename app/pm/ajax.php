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

	private function validate_messages($messages)
	{
		if (!is_array($messages))
		{
			H::ajax_error((_t('内容错误')));
		}
		$count = count($messages);
		if ($count < 2 OR $count > 5)
		{
			H::ajax_error((_t('内容错误')));
		}

		$length_limit = S::get_int('pm_length_limit');

		$uids = [];
		foreach ($messages as $uid => $message)
		{
			$uid = intval($uid);
			if (!$uid OR !$message OR !is_string($message))
			{
				H::ajax_error((_t('内容错误')));
				break;
			}
			if (!$this->model('password')->check_base64_string($message, $length_limit))
			{
				H::ajax_error((_t('内容最多 %s 字节', $length_limit)));
				break;
			}
			$uids[] = $uid;
		}

		if (!in_array(intval($this->user_id), $uids))
		{
			H::ajax_error((_t('内容错误')));
		}

		if (count(array_unique($uids)) != $count)
		{
			H::ajax_error((_t('内容错误')));
		}
		return $uids;
	}

	public function new_action()
	{
		$messages = H::POST('messages');
		$uids = $this->validate_messages($messages);

		if ($conversation = $this->model('pm')->find_conversation_by_uids($uids))
		{
			$conversation_id = $conversation['id'];
			$this->model('pm')->send_message($conversation_id, $this->user_id, $messages);
		}
		else
		{
			$users = $this->model('account')->get_user_info_by_uids($uids);
			if (!is_array($users) OR count($users) != count($uids))
			{
				H::ajax_error((_t('接收私信的用户不存在')));
			}
			foreach ($users as $user)
			{
				if (!$this->model('pm')->test_permissions($this->user_info, $user, $error))
				{
					H::ajax_error($error);
					break;
				}
			}

			$conversation_id = $this->model('pm')->new_conversation($this->user_id, $messages);
			if (!$conversation_id)
			{
				H::ajax_error((_t('无法创建会话')));
			}
		}

		H::ajax_location(url_rewrite('/pm/read/' . $conversation_id));
	}

	public function send_action()
	{
		$messages = H::POST('messages');
		$uids = $this->validate_messages($messages);

		$conversation_id = H::POST_I('conversation_id');
		if (!$conversation = $this->model('pm')->get_conversation($conversation_id, $this->user_id, true))
		{
			H::ajax_error((_t('会话不存在')));
		}

		if (count($conversation['uids']) < 2)
		{
			H::ajax_error((_t('会话已经结束')));
		}

		$this->model('pm')->send_message($conversation_id, $this->user_id, $messages);

		H::ajax_location(url_rewrite('/pm/read/' . $conversation_id));
	}

	public function delete_action()
	{
		$message_id = H::POST_I('id');
		if ($message_id > 0)
		{
			$this->model('pm')->delete_message($message_id, $this->user_id);
		}

		H::ajax_success();
	}

	public function exit_action()
	{
		$conversation_id = H::POST_I('id');
		if ($conversation_id > 0)
		{
			$this->model('pm')->exit_conversation($conversation_id, $this->user_id);
		}

		H::ajax_success();
	}

	public function notify_action()
	{
		if (!$this->user_info['permission']['is_moderator'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		$message = H::POST('message');
		$uid = H::POST_I('uid');

		if (!$uid OR !$message OR !is_string($message))
		{
			H::ajax_error((_t('内容错误')));
		}

		$length_limit = S::get_int('pm_length_limit');
		if (!$this->model('password')->check_base64_string($message, $length_limit))
		{
			H::ajax_error((_t('内容最多 %s 字节', $length_limit)));
		}

		if (!$this->model('account')->get_user_info_by_uid($uid))
		{
			H::ajax_error((_t('接收私信的用户不存在')));
		}

		$conversation_id = $this->model('pm')->notify($uid, $message);
		if (!$conversation_id)
		{
			H::ajax_error((_t('无法创建会话')));
		}

		H::ajax_response(array(
			'conversation' => $conversation_id
		));
	}

}