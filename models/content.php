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

class content_class extends AWS_MODEL
{
	public function check_thread_type($type)
	{
		switch ($type)
		{
			case 'question':
			case 'article':
			case 'video':
				return true;
		}
		return false;
	}

	public function check_item_type($type)
	{
		switch ($type)
		{
			case 'question':
			case 'answer':
			case 'article':
			case 'article_comment':
			case 'video':
			case 'video_comment':
				return true;
		}
		return false;
	}

	public function get_item_info_by_id($type, $item_id)
	{
		$item_id = intval($item_id);
		if (!$item_id OR !$this->check_item_type($type))
		{
			return false;
		}

		$where = 'id = ' . ($item_id);
		// TODO: question_id, answer_id 字段改为 id 以避免特殊处理
		if ($type == 'question')
		{
			$where = 'question_id = ' . ($item_id);
		}
		elseif ($type == 'answer')
		{
			$where = 'answer_id = ' . ($item_id);
		}

		$item_info = $this->fetch_row($type, $where);
		// TODO: published_uid 字段改为 uid 以避免特殊处理
		if ($item_info)
		{
			if ($type == 'question')
			{
				$item_info['id'] = $item_info['question_id'];
				$item_info['uid'] = $item_info['published_uid'];
			}
			elseif ($type == 'answer')
			{
				$item_info['id'] = $item_info['answer_id'];
			}
		}

		return $item_info;
	}

		/**
	 * 记录日志
	 * @param string $item_type question|article|video
	 * @param int $item_id
	 * @param string $note
	 * @param int $uid
	 * @param string $child_type question|question_discussion|answer|answer_discussion|article|article_comment|video|video_danmaku|video_comment
	 * @param int $child_id
	 */
	public function log($item_type, $item_id, $note, $uid = 0, $child_type = null, $child_id = 0)
	{
		$this->insert('content_log', array(
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'note' => $note,
			'uid' => intval($uid),
			'child_type' => $child_type,
			'child_id' => intval($child_id),
			'time' => fake_time()
		));
	}

	/**
	 *
	 * 根据 item_id, 得到日志列表
	 *
	 * @param string  $item_type question|article|video
	 * @param int     $item_id
	 * @param int     $page
	 * @param int     $per_page
	 *
	 * @return array
	 */
	public function list_logs($item_type, $item_id, $page, $per_page)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = "`item_type` = '" . ($item_type) . "' AND item_id = " . intval($item_id);

		$log_list = $this->fetch_page('content_log', $where, 'id DESC', $page, $per_page);
		if (!$log_list)
		{
			return false;
		}

		foreach ($log_list AS $key => $log)
		{
			$user_ids[] = $log['uid'];
		}

		if ($user_ids)
		{
			$users = $this->model('account')->get_user_info_by_uids($user_ids);
		}
		else
		{
			$users = array();
		}

		foreach ($log_list as $key => $log)
		{
			$log_list[$key]['user_info'] = $users[$log['uid']];
		}

		return $log_list;
	}

	public function delete_expired_logs()
	{
		$days = intval(get_setting('expiration_content_logs'));
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
		$this->delete('content_log', 'time < ' . $time_before);
	}
}