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
	/**
	 * 记录日志
	 * @param string $thread_type
	 * @param int $thread_id
	 * @param string $item_type
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
	 * @param string  $thread_type
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
		if ($thread_type AND !$this->model('post')->check_thread_type($thread_type))
		{
			return false;
		}
		if ($item_type AND !$this->model('post')->check_post_type($item_type))
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

	public function change_uid($item_type, $item_id, $new_uid, $old_uid, $log_uid)
	{
		$new_uid = intval($new_uid);
		if (!$new_uid OR $new_uid == $old_uid)
		{
			return false;
		}

		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
		{
			return false;
		}

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];

		$this->update('posts_index', array(
			'update_time' => $this->model('threadindex')->get_last_update_time()
		), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '提升', $log_uid);

		return true;
	}

	public function sink($item_type, $item_id, $log_uid)
	{
		if (!$this->model('post')->check_thread_type($item_type))
		{
			return false;
		}

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];

		$this->update('posts_index', array(
			'update_time' => $this->model('threadindex')->get_last_update_time() - (7 * 24 * 3600)
		), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '下沉', $log_uid);

		return true;
	}

	public function recommend($item_type, $item_id, $log_uid)
	{
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
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
		if (!$this->model('post')->check_thread_type($item_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];
		$this->update($item_type, array('sort' => 0), $where);

		$where = [['post_id', 'eq', $item_id, 'i'], ['post_type', 'eq', $item_type]];
		$this->update('posts_index', array('sort' => 0), $where);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '取消置顶', $log_uid);
	}


	public function fold($item_type, $item_id, $thread_type, $thread_id, $log_uid)
	{
		if ($this->model('post')->check_thread_type($item_type))
		{
			return false;
		}
		if (!$this->model('post')->check_post_type($item_type))
		{
			return false;
		}
		if (!$this->model('post')->check_thread_type($thread_type))
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

		$this->model('content')->log($thread_type, $thread_id, $item_type, $item_id, '折叠', $log_uid);
	}

	public function unfold($item_type, $item_id, $thread_type, $thread_id, $log_uid)
	{
		if ($this->model('post')->check_thread_type($item_type))
		{
			return false;
		}
		if (!$this->model('post')->check_post_type($item_type))
		{
			return false;
		}
		if (!$this->model('post')->check_thread_type($thread_type))
		{
			return false;
		}

		$where = ['id', 'eq', $item_id, 'i'];

		$this->update($item_type, array('fold' => 0), $where);

		$this->model('content')->log($thread_type, $thread_id, $item_type, $item_id, '取消折叠', $log_uid);
	}


}
