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
	private $cached_contents = array();

	private function get_cached_content_info($type, $item_id)
	{
		return $this->cached_contents[$type . '_' . $item_id];
	}

	private function cache_content_info($type, $item_id, $content_info)
	{
		$this->cached_contents[$type . '_' . $item_id] = $content_info;
	}

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


	public function check_reply_type($type)
	{
		switch ($type)
		{
			case 'answer':
			case 'article_reply':
			case 'video_reply':
				return true;
		}
		return false;
	}

	public function check_thread_or_reply_type($type)
	{
		switch ($type)
		{
			case 'question':
			case 'answer':
			case 'article':
			case 'article_reply':
			case 'video':
			case 'video_reply':
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
			case 'question_comment':
			case 'question_discussion':
			case 'article':
			case 'article_reply':
			case 'video':
			case 'video_reply':
				return true;
		}
		return false;
	}


	public function get_thread_info_by_id($type, $item_id)
	{
		$item_id = intval($item_id);
		if (!$item_id OR !$this->check_thread_type($type))
		{
			return false;
		}

		$item_info = $this->get_cached_content_info($type, $item_id);
		if (isset($item_info))
		{
			return $item_info;
		}

		$where = ['id', 'eq', $item_id];

		$item_info = $this->fetch_row($type, $where);

		$this->cache_content_info($type, $item_id, $item_info);
		return $item_info;
	}


	public function get_reply_info_by_id($type, $item_id)
	{
		$item_id = intval($item_id);
		if (!$item_id OR !$this->check_reply_type($type))
		{
			return false;
		}

		$item_info = $this->get_cached_content_info($type, $item_id);
		if (isset($item_info))
		{
			return $item_info;
		}

		$where = ['id', 'eq', $item_id];

		$item_info = $this->fetch_row($type, $where);

		$this->cache_content_info($type, $item_id, $item_info);
		return $item_info;
	}


	public function get_thread_or_reply_info_by_id($type, $item_id)
	{
		$item_id = intval($item_id);
		if (!$item_id OR !$this->check_thread_or_reply_type($type))
		{
			return false;
		}

		$item_info = $this->get_cached_content_info($type, $item_id);
		if (isset($item_info))
		{
			return $item_info;
		}

		$where = ['id', 'eq', $item_id];

		$item_info = $this->fetch_row($type, $where);

		$this->cache_content_info($type, $item_id, $item_info);
		return $item_info;
	}

	public function get_item_thread_info_by_id($type, $item_id)
	{
		$item_info = $this->get_thread_or_reply_info_by_id($type, $item_id);
		if (!$item_info)
		{
			return false;
		}

		switch ($type)
		{
			case 'question':
				$item_info['thread_type'] = 'question';
				$item_info['thread_id'] = $item_info['id'];
				return $item_info;

			case 'article':
				$item_info['thread_type'] = 'article';
				$item_info['thread_id'] = $item_info['id'];
				return $item_info;

			case 'video':
				$item_info['thread_type'] = 'video';
				$item_info['thread_id'] = $item_info['id'];
				return $item_info;

			case 'answer':
				$thread_info = $this->get_thread_info_by_id('question', $item_info['question_id']);
				if ($thread_info)
				{
					$thread_info['thread_type'] = 'question';
					$thread_info['thread_id'] = $thread_info['id'];
					return $thread_info;
				}
				return false;

			case 'article_reply':
				$thread_info = $this->get_thread_info_by_id('article', $item_info['article_id']);
				if ($thread_info)
				{
					$thread_info['thread_type'] = 'article';
					$thread_info['thread_id'] = $thread_info['id'];
					return $thread_info;
				}
				return false;

			case 'video_reply':
				$thread_info = $this->get_thread_info_by_id('video', $item_info['video_id']);
				if ($thread_info)
				{
					$thread_info['thread_type'] = 'video';
					$thread_info['thread_id'] = $thread_info['id'];
					return $thread_info;
				}
				return false;
		}

		return false;
	}

	public function get_redirect_posts($type, $redirect_id)
	{
		$redirect_id = intval($redirect_id);
		if (!$redirect_id OR !$this->check_thread_type($type))
		{
			return false;
		}
		return $this->fetch_all($type, ['redirect_id', 'eq', $redirect_id]);
	}


	// 不缓存版
	public function get_post_by_id($type, $item_id)
	{
		$item_id = intval($item_id);
		if (!$item_id OR !$this->check_thread_or_reply_type($type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id];
		return $this->fetch_row($type, $where);
	}

	// 不缓存版
	public function get_posts_by_ids($type, $item_ids)
	{
		if (!$item_ids OR !$this->check_thread_or_reply_type($type))
		{
			return false;
		}

		if ($item_list = $this->fetch_all($type, ['id', 'in', $item_ids, 'i']))
		{
			foreach ($item_list AS $key => $val)
			{
				$result[$val['id']] = $val;
			}
		}

		return $result;
	}

	// 不缓存版
	public function get_threads_by_ids($type, $item_ids)
	{
		if (!$item_ids OR !$this->check_thread_type($type))
		{
			return false;
		}

		if ($item_list = $this->fetch_all($type, ['id', 'in', $item_ids, 'i']))
		{
			foreach ($item_list AS $key => $val)
			{
				$result[$val['id']] = $val;
			}
		}

		return $result;
	}


	public function has_user_relpied_to_thread($thread_type, $thread_id, $uid, $check_scheduled_posts = false)
	{
		$thread_id = intval($thread_id);
		if (!$thread_id)
		{
			return false;
		}

		$uid = intval($uid);

		switch ($thread_type)
		{
			case 'question':
				$reply_type = 'answer';
				$where = [['question_id', 'eq', $thread_id], ['uid', 'eq', $uid]];
				break;

			case 'article':
				$reply_type = 'article_reply';
				$where = [['article_id', 'eq', $thread_id], ['uid', 'eq', $uid]];
				break;

			case 'video':
				$reply_type = 'video_reply';
				$where = [['video_id', 'eq', $thread_id], ['uid', 'eq', $uid]];
				break;

			default:
				return false;
		}

		if ($this->fetch_one($reply_type, 'id', $where))
		{
			return 1;
		}

		if ($check_scheduled_posts)
		{
			if ($this->fetch_one('scheduled_posts', 'id', [
				['type', 'eq', $reply_type],
				['parent_id', 'eq', $thread_id],
				['uid', 'eq', $uid]
			]))
			{
				return 2;
			}
		}

		return 0;
	}


	/**
	 * 记录日志
	 * @param string $thread_type question|article|video
	 * @param int $thread_id
	 * @param string $item_type question|question_discussion|answer|answer_discussion|article|article_comment|video|video_comment
	 * @param int $item_id
	 * @param string $note
	 * @param int $uid
	 * @param string $child_type 附加内容type
	 * @param int $child_id 附加内容id
	 */
	public function log($thread_type, $thread_id, $item_type, $item_id, $note, $uid = 0, $child_type = null, $child_id = 0)
	{
		if (!$uid = intval($uid))
		{
			return;
		}
		$this->insert('content_log', array(
			'thread_type' => $thread_type,
			'thread_id' => intval($thread_id),
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'note' => $note,
			'uid' => ($uid),
			'child_type' => $child_type,
			'child_id' => intval($child_id),
			'time' => fake_time()
		));
	}

	/**
	 *
	 * 得到日志列表
	 *
	 * @param string  $thread_type question|article|video
	 * @param int     $thread_id
	 * @param string  $item_type
	 * @param int     $item_id
	 * @param int     $uid
	 * @param int     $page
	 * @param int     $per_page
	 *
	 * @return array
	 */
	public function list_logs($thread_type, $thread_id, $item_type, $item_id, $uid, $page, $per_page)
	{
		if ($thread_type AND !$this->check_thread_type($thread_type))
		{
			return false;
		}
		if ($item_type AND !$this->check_item_type($item_type))
		{
			return false;
		}

		$where = array();
		if ($thread_type)
		{
			$where[] = ['thread_type', 'eq', $thread_type];
		}
		if ($thread_id = intval($thread_id))
		{
			$where[] = ['thread_id', 'eq', $thread_id];
		}
		if ($item_type)
		{
			$where[] = ['item_type', 'eq', $item_type];
		}
		if ($item_id = intval($item_id))
		{
			$where[] = ['item_id', 'eq', $item_id];
		}
		if ($uid = intval($uid))
		{
			$where[] = ['uid', 'eq', $uid];
		}

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

	public function update_view_count($item_type, $item_id)
	{
		if (!$this->check_thread_type($item_type))
		{
			return;
		}

		$item_id = intval($item_id);
		$key = 'update_view_count_' . $item_type . '_' . $item_id;
		$now = time();
		$update = false;

		$data = AWS_APP::cache()->get($key);
		if (is_array($data))
		{
			$count = intval($data['count']) + 1;
			$created_at = intval($data['time']);
			$exipres_at = $created_at + 60;
			if ($now >= $exipres_at)
			{
				$update = true;
				$created_at = $now;
			}
		}
		else
		{
			$count = 1;
			$update = true;
			$created_at = $now;
		}

		if ($update)
		{
			$this->update($item_type, '`view_count` = `view_count` + ' . $count, ['id', 'eq', $item_id]);
			$count = 0;
		}

		AWS_APP::cache()->set($key, array(
			'time' => $created_at,
			'count' => $count
		), 3600);
	}

	public function change_uid($item_type, $item_id, $new_uid, $old_uid, $log_uid)
	{
		$new_uid = intval($new_uid);
		if (!$new_uid OR $new_uid == $old_uid)
		{
			return false;
		}

		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('uid' => ($new_uid)), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('uid' => ($new_uid)), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '变更作者', $log_uid, 'user', $old_uid);

		return true;
	}

	public function redirect($item_type, $item_id, $redirect_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array(
			'redirect_id' => intval($redirect_id),
			'lock' => 1
		), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '合并', $log_uid, $item_type, $redirect_id);

		return true;
	}

	public function unredirect($item_type, $item_id, $redirect_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array(
			'redirect_id' => 0,
			'lock' => 0
		), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '取消合并', $log_uid, $item_type, $redirect_id);

		return true;
	}

	public function change_category($item_type, $item_id, $category_id, $old_category_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('category_id' => intval($category_id)), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('category_id' => intval($category_id)), $where);

		$where = [['uid', 'eq', 0], ['thread_id', 'eq', $item_id, 'i'], ['thread_type', 'eq', $item_type]];
		$this->update('activity', array('category_id' => intval($category_id)), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '变更分类', $log_uid, 'category', $old_category_id);

		return true;
	}


	public function lock($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('lock' => 1), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '锁定', $log_uid);

		return true;
	}

	public function unlock($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('lock' => 0), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '取消锁定', $log_uid);

		return true;
	}


	public function bump($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];

		$this->update('posts_index', array(
			'update_time' => $this->model('posts')->get_last_update_time()
		), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '提升', $log_uid);

		return true;
	}

	public function sink($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];

		$this->update('posts_index', array(
			'update_time' => $this->model('posts')->get_last_update_time() - (7 * 24 * 3600)
		), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '下沉', $log_uid);

		return true;
	}

	public function recommend($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('recommend' => 1), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('recommend' => 1), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '推荐', $log_uid);
	}

	public function unrecommend($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('recommend' => 0), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('recommend' => 0), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '取消推荐', $log_uid);
	}


	public function pin($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('sort' => 1), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('sort' => 1), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '置顶', $log_uid);
	}

	public function unpin($item_type, $item_id, $log_uid)
	{
		if (!$this->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('sort' => 0), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('sort' => 0), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '取消置顶', $log_uid);
	}


	public function fold_reply($item_type, $item_id, $parent_type, $parent_id, $log_uid)
	{
		if (!$this->check_reply_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];

		$this->update($item_type, array('fold' => 1), $where);

		// 折叠的回复的通知标为已读
		$this->update('notification', array(
			'read_flag' => 1
		), [
			['id', 'notEq', 1],
			['item_type', 'eq', $item_type],
			['item_id', 'eq', $item_id, 'i']
		]);

		$this->model('content')->log($parent_type, $parent_id, $item_type, $item_id, '折叠', $log_uid);
	}

	public function unfold_reply($item_type, $item_id, $parent_type, $parent_id, $log_uid)
	{
		if (!$this->check_reply_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];

		$this->update($item_type, array('fold' => 0), $where);

		$this->model('content')->log($parent_type, $parent_id, $item_type, $item_id, '取消折叠', $log_uid);
	}


}