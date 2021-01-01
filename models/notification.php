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
	public function get_all_options()
	{
		return array(
			'FOLLOW_USER' => _t('有人关注我'),
			'INVITE_USER' => _t('有人邀请我'),
			'MENTION_USER' => _t('有人提到我'),
			'REPLY_THREAD' => _t('有人回复主题'),
			'REPLY_USER' => _t('有人回复我'),
		);
	}

	public function get_user_ignore_list($uid)
	{
		$item = $this->fetch_row('user_notification_settings', ['uid', 'eq', $uid, 'i']);
		if (!$item)
		{
			return [];
		}
		return unserialize_array($item['data']);
	}

	public function get_users_ignore_lists($uids)
	{
		if (!is_array($uids) OR !count($uids))
		{
			return [];
		}
		$items = $this->fetch_all('user_notification_settings', ['uid', 'in', $uids, 'i'], 'id DESC');
		$lists = array_fill_keys($uids, []);
		foreach ($items as $item)
		{
			$lists[$item['uid']] = unserialize_array($item['data']);
		}
		return $lists;
	}

	public function set_user_ignore_list($uid, $array, $unignore = false)
	{
		if (!is_array($array))
		{
			$array = [];
		}

		$list = [];
		foreach ($this->get_all_options() as $key => $val)
		{
			if ($unignore)
			{
				if (!in_array($key, $array))
				{
					$list[] = $key;
				}
			}
			else
			{
				if (in_array($key, $array))
				{
					$list[] = $key;
				}
			}
		}

		$where = ['uid', 'eq', $uid, 'i'];
		if (!count($list))
		{
			$this->delete('user_notification_settings', $where);
			return;
		}

		if (!$this->count('user_notification_settings', $where))
		{
			$this->insert('user_notification_settings', array(
				'data' => serialize($list),
				'uid' => intval($uid)
			));
			return;
		}

		$this->update('user_notification_settings', array(
			'data' => serialize($list)
		), $where);
	}


	public function send($sender_uid, $recipient_uid, $action, $thread_type = null, $thread_id = 0, $item_type = null, $item_id = 0)
	{
		$sender_uid = intval($sender_uid);
		$recipient_uid = intval($recipient_uid);
		if ($recipient_uid <= 0 OR $sender_uid == $recipient_uid)
		{
			return;
		}

		if (in_array($action, $this->get_user_ignore_list($recipient_uid)))
		{
			return;
		}

		if ($this->model('block')->has_user_been_blocked($sender_uid, $recipient_uid))
		{
			return;
		}

		$now = fake_time();
		$this->insert('notification', array(
			'sender_uid' => $sender_uid,
			'recipient_uid' => $recipient_uid,
			'action' => $action,
			'thread_type' => $thread_type,
			'thread_id' => $thread_id,
			'item_type' => $item_type,
			'item_id' => $item_id,
			'add_time' => $now,
			'read_flag' => 0
		));
	}

	public function multi_send($sender_uid, $recipient_uids, $action, $thread_type = null, $thread_id = 0, $item_type = null, $item_id = 0)
	{
		$lists = $this->get_users_ignore_lists($recipient_uids);
		$recipient_uids = [];
		foreach ($lists as $recipient_uid => $ignored_actions)
		{
			if (in_array($action, $ignored_actions))
			{
				continue;
			}

			$recipient_uid = intval($recipient_uid);
			if ($recipient_uid <= 0 OR $sender_uid == $recipient_uid)
			{
				continue;
			}
			$recipient_uids[] = $recipient_uid;
		}

		$lists = $this->model('block')->get_users_blocked_uids($recipient_uids);
		$recipient_uids = [];
		foreach ($lists as $recipient_uid => $blocked_uids)
		{
			if (in_array($sender_uid, $blocked_uids))
			{
				continue;
			}
			$recipient_uids[] = $recipient_uid;
		}

		$now = fake_time();
		foreach ($recipient_uids as $recipient_uid)
		{
			$this->insert('notification', array(
				'sender_uid' => $sender_uid,
				'recipient_uid' => $recipient_uid,
				'action' => $action,
				'thread_type' => $thread_type,
				'thread_id' => $thread_id,
				'item_type' => $item_type,
				'item_id' => $item_id,
				'add_time' => $now,
				'read_flag' => 0
			));
		}
	}

	public function mark_as_read($notification_id, $uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), [['read_flag', 'notEq', 1], ['id', 'eq', $notification_id, 'i'], ['recipient_uid', 'eq', $uid, 'i']]);
	}

	public function mark_all_as_read($uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), [['read_flag', 'notEq', 1], ['recipient_uid', 'eq', $uid, 'i']]);
	}

	public function count_unread($uid)
	{
		return $this->count('notification', [['read_flag', 'eq', 0], ['recipient_uid', 'eq', $uid, 'i']]);
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
			}
		}

		if ($question_ids)
		{
			$questions = $this->model('post')->get_posts_by_ids('question', $question_ids);
		}
		if ($article_ids)
		{
			$articles = $this->model('post')->get_posts_by_ids('article', $article_ids);
		}
		if ($video_ids)
		{
			$videos = $this->model('post')->get_posts_by_ids('video', $video_ids);
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
			}
		}
	}

}
