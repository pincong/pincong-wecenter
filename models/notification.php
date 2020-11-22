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

class notification_class extends AWS_MODEL
{
	public $notify_action_details = array(
		'FOLLOW_USER' => '有人关注我',
		'INVITE_USER' => '有人邀请我',
		'MENTION_USER' => '有人提到我',
		'REPLY_THREAD' => '有人回复主题',
		'REPLY_USER' => '有人回复我',
	);

	private function check_notification_setting($recipient_uid, $action)
	{
		$notification_setting = $this->model('account')->get_notification_setting_by_uid($recipient_uid);

		// 默认不认置则全部都发送
		if (!$notification_setting['data'])
		{
			return true;
		}

		// 设置则不发送
		if (in_array($action, $notification_setting['data']))
		{
			return false;
		}

		return true;
	}

	public function send($sender_uid, $recipient_uid, $action, $thread_type = null, $thread_id = 0, $item_type = null, $item_id = 0)
	{
		$sender_uid = intval($sender_uid);
		$recipient_uid = intval($recipient_uid);
		if ($recipient_uid <= 0 OR $sender_uid == $recipient_uid)
		{
			return false;
		}

		if (!$this->check_notification_setting($recipient_uid, $action))
		{
			return false;
		}

		$add_time = fake_time();
		if ($notification_id = $this->insert('notification', array(
			'sender_uid' => ($sender_uid),
			'recipient_uid' => ($recipient_uid),
			'action' => $action,
			'thread_type' => $thread_type,
			'thread_id' => intval($thread_id),
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'add_time' => $add_time,
			'read_flag' => 0
		)))
		{
			$this->model('account')->update_notification_unread($recipient_uid);
			return $notification_id;
		}
	}

	public function mark_as_read($notification_id, $uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), [['read_flag', 'notEq', 1], ['id', 'eq', $notification_id, 'i'], ['recipient_uid', 'eq', $uid, 'i']]);

		$this->model('account')->update_notification_unread($uid);
	}

	public function mark_all_as_read($uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), [['read_flag', 'notEq', 1], ['recipient_uid', 'eq', $uid, 'i']]);

		$this->model('account')->update_notification_unread($uid);
	}

	/**
	 * 获得通知列表
	 * 
	 * @param $read_flag 0 - 未读, 1 - 已读, null - 所有
	 */
	public function list_notifications($recipient_uid, $read_flag, $page, $per_page)
	{
		if (!$recipient_uid)
		{
			return false;
		}

		$where[] = ['recipient_uid', 'eq', $recipient_uid, 'i'];

		if (isset($read_flag))
		{
			$where[] = ['read_flag', 'eq', $read_flag, 'i'];
		}

		$list = $this->fetch_page('notification', $where, 'id DESC', $page, $per_page);
		if (!$list)
		{
			return false;
		}

		$this->process_notifications($list);

		return $list;
	}

	private function process_notifications(&$list)
	{
		foreach ($list as $key => $val)
		{
			$user_ids[] = $val['sender_uid'];
			switch ($val['thread_type'])
			{
				case 'question':
					$question_ids[] = $val['thread_id'];
					break;

				case 'article':
					$article_ids[] = $val['thread_id'];
					break;

				case 'video':
					$video_ids[] = $val['thread_id'];
					break;
                    
                case 'voting':
					$voting_ids[] = $val['thread_id'];
					break;
			}
		}

		if ($question_ids)
		{
			$questions = $this->model('content')->get_posts_by_ids('question', $question_ids);
		}
		if ($article_ids)
		{
			$articles = $this->model('content')->get_posts_by_ids('article', $article_ids);
		}
		if ($video_ids)
		{
			$videos = $this->model('content')->get_posts_by_ids('video', $video_ids);
		}
        
        if ($voting_ids)
		{
			$votings = $this->model('content')->get_posts_by_ids('voting', $voting_ids);
		}

		if ($user_ids)
		{
			$users = $this->model('account')->get_user_info_by_uids($user_ids);
		}

		foreach ($list as $key => $val)
		{
			$list[$key]['user_info'] = $users[$val['sender_uid']];

			switch ($val['thread_type'])
			{
				case 'question':
					$list[$key]['thread_info'] = $questions[$val['thread_id']];
					break;

				case 'article':
					$list[$key]['thread_info'] = $articles[$val['thread_id']];
					break;

				case 'video':
					$list[$key]['thread_info'] = $videos[$val['thread_id']];
					break;
                    
                case 'voting':
					$list[$key]['thread_info'] = $votings[$val['thread_id']];
					break;
			}
		}

	}

	public function delete_expired_data()
	{
		$days = S::get_int('expiration_notifications');
		if (!$days)
		{
			return;
		}
		$seconds = $days * 24 * 3600;
		$time_before = real_time() - $seconds;
		if ($time_before < 0)
		{
			$time_before = 0;
		}
		$this->delete('notification', ['add_time', 'lt', $time_before]);
	}
}
