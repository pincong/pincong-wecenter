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

	public function send($sender_uid, $recipient_uid, $action, $item_type, $item_id)
	{
		if (intval($recipient_uid) <= 0)
		{
			return;
		}

		if (!$this->check_notification_setting($recipient_uid, $action))
		{
			return;
		}

		$add_time = fake_time();
		if ($notification_id = $this->insert('notification', array(
			'sender_uid' => intval($sender_uid),
			'recipient_uid' => intval($recipient_uid),
			'action' => $action,
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
		), 'notification_id = ' . intval($notification_id) . ' AND recipient_uid = ' . intval($uid));

		$this->model('account')->update_notification_unread($uid);
	}

	public function mark_all_as_read($uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), 'recipient_uid = ' . intval($uid));

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

		$where[] = 'recipient_uid = ' . intval($recipient_uid);

		if (isset($read_flag))
		{
			$where[] = 'read_flag = ' . intval($read_flag);
		}

		$list = $this->fetch_page('notification', implode(' AND ', $where), 'notification_id DESC', $page, $per_page);
		if (!$list)
		{
			return false;
		}

		return $list;
	}

	private function process_notifications(&$list)
	{
		foreach ($list as $key => $val)
		{
			$user_ids[] = $val['sender_uid'];
			switch ($val['item_type'])
			{
				case 'user':
					$user_ids[] = $val['item_id'];
					break;

				case 'answer':
					$answer_ids[] = $val['item_id'];
					break;

				case 'article_comment':
					$article_comment_ids[] = $val['item_id'];
					break;

				case 'video_comment':
					$video_comment_ids[] = $val['item_id'];
					break;

				case 'question':
					$question_ids[] = $val['item_id'];
					break;

				case 'article':
					$article_ids[] = $val['item_id'];
					break;

				case 'video':
					$video_ids[] = $val['item_id'];
					break;
			}
		}

		if ($answer_ids)
		{
			$answers = $this->model('content')->get_posts_by_ids('answer', $answer_ids);
			foreach ($answers AS $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
		}
		if ($question_ids)
		{
			$questions = $this->model('content')->get_posts_by_ids('question', $question_ids);
		}

		if ($article_comment_ids)
		{
			$article_comments = $this->model('content')->get_posts_by_ids('article_comment', $article_comment_ids);
			foreach ($article_comments AS $key => $val)
			{
				$article_ids[] = $val['article_id'];
			}
		}
		if ($article_ids)
		{
			$articles = $this->model('content')->get_posts_by_ids('article', $article_ids);
		}

		if ($video_comment_ids)
		{
			$video_comments = $this->model('content')->get_posts_by_ids('video_comment', $video_comment_ids);
			foreach ($video_comments AS $key => $val)
			{
				$video_ids[] = $val['video_id'];
			}
		}
		if ($video_ids)
		{
			$videos = $this->model('content')->get_posts_by_ids('video', $video_ids);
		}

		if ($user_ids)
		{
			$users = $this->model('account')->get_user_info_by_uids($user_ids);
		}

		foreach ($list as $key => $val)
		{
			switch ($val['item_type'])
			{
				case 'user':
					$list[$key]['item_info']; = $users[$val['item_id']];
					break;

				case 'answer':
					$list[$key]['item_info']; = $answers[$val['item_id']];
					break;

				case 'article_comment':
					$list[$key]['item_info']; = $article_comments[$val['item_id']];
					break;

				case 'video_comment':
					$list[$key]['item_info']; = $video_comments[$val['item_id']];
					break;

				case 'question':
					$list[$key]['item_info']; = $questions[$val['item_id']];
					break;

				case 'article':
					$list[$key]['item_info']; = $articles[$val['item_id']];
					break;

				case 'video':
					$list[$key]['item_info']; = $videos[$val['item_id']];
					break;
			}
		}
	}

	public function delete_expired_data()
	{
		$days = intval(get_setting('expiration_notifications'));
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
		$this->delete('notification', 'add_time < ' . $time_before);
	}
}
