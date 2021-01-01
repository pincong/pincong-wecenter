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

class vote_class extends AWS_MODEL
{
	public function get_sent_votes_by_uid($uid, $page, $per_page)
	{
		$list = $this->fetch_page('vote', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$recipient_uids[] = $val['recipient_uid'];
		}

		if ($recipient_uids)
		{
			$recipient_user_infos = $this->model('account')->get_user_info_by_uids($recipient_uids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['user_info'] = $recipient_user_infos[$val['recipient_uid']];
			}
		}

		return $list;
	}

	public function get_received_votes_by_uid($uid, $page, $per_page)
	{
		$list = $this->fetch_page('vote', ['recipient_uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$uids[] = $val['uid'];
		}

		if ($uids)
		{
			$user_infos = $this->model('account')->get_user_info_by_uids($uids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['user_info'] = $user_infos[$val['uid']];
			}
		}

		return $list;
	}

	private function process_currency_agree($type, $item_id, $uid, $item_uid, $permission)
	{
		switch ($type)
		{
			case 'question':
				$note_agree = '赞同问题';
				$note_item_agreed = '问题被赞同';
				break;

			case 'question_reply':
				$note_agree = '赞同回答';
				$note_item_agreed = '回答被赞同';
				break;

			case 'article':
				$note_agree = '赞同文章';
				$note_item_agreed = '文章被赞同';
				break;

			case 'article_reply':
				$note_agree = '赞同文章评论';
				$note_item_agreed = '文章评论被赞同';
				break;

			case 'video':
				$note_agree = '赞同影片';
				$note_item_agreed = '影片被赞同';
				break;

			case 'video_reply':
				$note_agree = '赞同影片评论';
				$note_item_agreed = '影片评论被赞同';
				break;
		}

		$this->model('currency')->process($uid, 'AGREE', S::get('currency_system_config_agree'), $note_agree, $item_id, $type);
		if ($permission['affect_currency'])
		{
			$this->model('currency')->process($item_uid, 'AGREED', S::get('currency_system_config_agreed'), $note_item_agreed, $item_id, $type);
		}
	}

	private function process_currency_disagree($type, $item_id, $uid, $item_uid, $permission)
	{
		switch ($type)
		{
			case 'question':
				$note_disagree = '反对问题';
				$note_item_disagreed = '问题被反对';
				break;

			case 'question_reply':
				$note_disagree = '反对回答';
				$note_item_disagreed = '回答被反对';
				break;

			case 'article':
				$note_disagree = '反对文章';
				$note_item_disagreed = '文章被反对';
				break;

			case 'article_reply':
				$note_disagree = '反对文章评论';
				$note_item_disagreed = '文章评论被反对';
				break;

			case 'video':
				$note_disagree = '反对影片';
				$note_item_disagreed = '影片被反对';
				break;

			case 'video_reply':
				$note_disagree = '反对影片评论';
				$note_item_disagreed = '影片评论被反对';
				break;
		}

		$this->model('currency')->process($uid, 'DISAGREE', S::get('currency_system_config_disagree'), $note_disagree, $item_id, $type);
		if ($permission['affect_currency'])
		{
			$this->model('currency')->process($item_uid, 'DISAGREED', S::get('currency_system_config_disagreed'), $note_item_disagreed, $item_id, $type);
		}
	}


	/**
	 *
	 * 投票
	 * @param string $type     //被投票内容类型
	 * @param int $item_id     //被投票内容ID
	 * @param int $uid         //投票用户ID
	 * @param int $item_uid    //被投票用户ID
	 * @param int $action      //1赞同 -1反对
	 *
	 * @return boolean true|false
	 */
	public function vote($type, $item_id, $uid, $item_uid, $action)
	{
		if (!$this->model('post')->check_thread_or_reply_type($type))
		{
			return false;
		}

		$item_id = intval($item_id);
		$uid = intval($uid);
		$item_uid = intval($item_uid);

		$where = [
			['type', 'eq', $type],
			['item_id', 'eq', $item_id],
			['uid', 'eq', $uid]
		];
		$vote_info = $this->fetch_row('vote', $where, 'id DESC');

		if (!$vote_info OR !$vote_info['value'])
		{
			// 未投票或已取消之前的投票
			$value = ($action == 1 ? 1 : -1);
			$count = $value;
		}
		else if ($vote_info['value'] > 0)
		{
			// 已赞同
			// 取消赞同
			$value = 0;
			// 计数減1
			$count = -1;
		}
		else // if ($vote_info['value'] < 0)
		{
			// 已反对
			// 取消反对
			$value = 0;
			// 计数加1
			$count = 1;
		}

		$this->insert('vote', array(
			'type' => $type,
			'item_id' => $item_id,
			'uid' => $uid,
			'recipient_uid' => $item_uid,
			'value' => $value,
			'add_time' => fake_time()
		));

		// 游戏币跟声望只算一次
		if (!$vote_info)
		{
			// 已缓存过
			$vote_user = $this->model('account')->get_user_and_group_info_by_uid($uid);
			if ($action == 1)
			{
				$this->process_currency_agree($type, $item_id, $uid, $item_uid, $vote_user['permission']);
			}
			else
			{
				$this->process_currency_disagree($type, $item_id, $uid, $item_uid, $vote_user['permission']);
			}
		}
		$this->model('reputation')->apply($type, $item_id, $uid, $item_uid, $count, !!$vote_info);

		return true;
	}


	public function get_user_vote_count($uid, $days = null, $value = null, $type = null, $item_id = null)
	{
		$where[] = ['uid', 'eq', $uid, 'i'];
		if (isset($value))
		{
			$where[] = ['value', 'eq', $value, 'i'];
		}
		if (isset($days))
		{
			$time_after = real_time() - 24 * 3600 * $days;
			$where[] = ['add_time', 'gt', $time_after];
		}
		if (isset($type) AND isset($item_id))
		{
			if ($this->model('post')->check_thread_or_reply_type($type))
			{
				$where[] = ['type', 'eq', $type];
				$where[] = ['item_id', 'eq', $item_id, 'i'];
			}
		}

		return intval($this->count('vote', $where));
	}


	public function check_user_vote_rate_limit($uid, $user_permission)
	{
		$limit = intval($user_permission['user_vote_limit_per_day']);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = [
			['add_time', 'gt', $time_after],
			['uid', 'eq', $uid]
		];
		$count = $this->count('vote', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}


	public function check_same_user_limit($uid, $recipient_uid, $value)
	{
		if ($value == 1)
		{
			$limit = S::get_int('same_user_upvotes_per_day');
		}
		elseif ($value == -1)
		{
			$limit = S::get_int('same_user_downvotes_per_day');
		}
		else
		{
			return true;
		}

		if (!$limit)
		{
			return true;
		}

		$recipient_uid = intval($recipient_uid);
		if ($recipient_uid <= 0)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = [
			['add_time', 'gt', $time_after],
			['uid', 'eq', $uid],
			['recipient_uid', 'eq', $recipient_uid],
			['value', 'eq', $value, 'i']
		];
		$count = $this->count('vote', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}


	public function get_user_vote_value_by_id($type, $item_id, $uid)
	{
		if (!$this->model('post')->check_thread_or_reply_type($type))
		{
			return false;
		}

		$item_id = intval($item_id);
		$uid = intval($uid);

		$where = [
			['type', 'eq', $type],
			['item_id', 'eq', $item_id],
			['uid', 'eq', $uid]
		];
		return $this->fetch_one('vote', 'value', $where, 'id DESC');
	}

	public function get_user_vote_values_by_ids($type, $item_ids, $uid)
	{
		if (!$this->model('post')->check_thread_or_reply_type($type))
		{
			return false;
		}

		if (!is_array($item_ids))
		{
			return false;
		}

		$vote_values = array();
		if (count($item_ids) < 1)
		{
			return $vote_values;
		}

		$where = [
			['type', 'eq', $type],
			['uid', 'eq', $uid, 'i'],
			['item_id', 'in', $item_ids, 'i']
		];
		$rows = $this->fetch_all('vote', $where, 'id DESC');
		if (!$rows)
		{
			return $vote_values;
		}

		foreach ($rows AS $key => $val)
		{
			if (!isset($vote_values[$val['item_id']]))
			{
				$vote_values[$val['item_id']] = $val['value'];
			}
		}

		return $vote_values;
	}


	/**
	 *
	 * 根据 item_id, 得到投票记录
	 *
	 * @param string  $type
	 * @param int     $item_id
	 * @param int     $page
	 * @param int     $per_page
	 *
	 * @return array
	 */
	public function list_logs($type, $item_id, $page, $per_page)
	{
		if (!$this->model('post')->check_thread_or_reply_type($type))
		{
			return false;
		}

		$where = [
			['type', 'eq', $type],
			['item_id', 'eq', $item_id, 'i']
		];

		$log_list = $this->fetch_page('vote', $where, 'id DESC', $page, $per_page);
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

}