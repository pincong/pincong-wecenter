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

class video_class extends AWS_MODEL
{
	public function get_videos_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_videos_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('video', 'uid = ' . intval($uid), 'id DESC', $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

	public function get_video_comments_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_video_comments_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('video_comment', 'uid = ' . intval($uid), 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['video_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('content')->get_posts_by_ids('video', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['video_info'] = $parents[$val['video_id']];
			}
		}

		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

	public function modify_video($id, $title, $message, $log_uid)
	{
		if (!$item_info = $this->model('content')->get_thread_info_by_id('video', $id))
		{
			return false;
		}

		//$this->model('search_fulltext')->push_index('video', $title, $item_info['id']);

		$this->update('video', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message)
		), 'id = ' . intval($id));

		$this->model('content')->log('video', $id, 'video', $id, '编辑', $log_uid);

		return true;
	}


	public function clear_video($id, $log_uid)
	{
		if (!$item_info = $this->model('content')->get_thread_info_by_id('video', $id))
		{
			return false;
		}

		$data = array(
			'title' => null,
			'message' => null,
			'title_fulltext' => null,
			'source_type' => null,
			'source' => null,
		);

		$trash_category_id = intval(get_setting('trash_category_id'));
		if ($trash_category_id)
		{
			$where = "post_id = " . intval($id) . " AND post_type = 'video'";
			$this->update('posts_index', array('category_id' => $trash_category_id), $where);
			$data['category_id'] = $trash_category_id;
		}

		$this->update('video', $data, 'id = ' . intval($id));

		$this->model('content')->log('video', $id, 'video', $id, '删除', $log_uid, 'category', $item_info['category_id']);

		return true;
	}


	public function modify_video_comment($comment_id, $message, $log_uid)
	{
		if (!$comment_info = $this->model('content')->get_reply_info_by_id('video_comment', $comment_id))
		{
			return false;
		}

		$this->update('video_comment', array(
			'message' => htmlspecialchars($message)
		), 'id = ' . intval($comment_id));

		$this->model('content')->log('video', $comment_info['video_id'], 'video_comment', $comment_id, '编辑', $log_uid);

		return true;
	}

	public function clear_video_comment($comment_id, $log_uid)
	{
		if (!$comment_info = $this->model('content')->get_reply_info_by_id('video_comment', $comment_id))
		{
			return false;
		}

		$this->update('video_comment', array(
			'message' => null,
			'fold' => 1
		), 'id = ' . intval($comment_id));

		$this->model('content')->log('video', $comment_info['video_id'], 'video_comment', $comment_id, '删除', $log_uid);

		return true;
	}


	public function update_video_source($id, $source_type, $source)
	{
		$this->update('video', array(
			'source_type' => $source_type,
			'source' => $source,
		), 'id = ' . intval($id));

		return true;
	}


	// 同时获取用户信息
	public function get_video_by_id($id)
	{
		if ($item = $this->fetch_row('video', 'id = ' . intval($id)))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_video_comment_by_id($id)
	{
		if ($item = $this->fetch_row('video_comment', 'id = ' . intval($id)))
		{
			$user_infos = $this->model('account')->get_user_info_by_uids(array(
				$item['uid'],
				$item['at_uid']
			));

			$item['user_info'] = $user_infos[$item['uid']];
			$item['at_user_info'] = $user_infos[$item['at_uid']];
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_video_comments($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		array_walk_recursive($thread_ids, 'intval_string');
		$where = 'video_id IN (' . implode(',', $thread_ids) . ')';

		if ($list = $this->fetch_page('video_comment', $where, $order, $page, $per_page))
		{
			foreach ($list AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];

				if ($val['at_uid'])
				{
					$uids[$val['at_uid']] = $val['at_uid'];
				}
			}

			if ($uids)
			{
				$user_infos = $this->model('account')->get_user_info_by_uids($uids);
			}

			foreach ($list AS $key => $val)
			{
				$list[$key]['user_info'] = $user_infos[$val['uid']];
				$list[$key]['at_user_info'] = $user_infos[$val['at_uid']];
			}
		}

		return $list;
	}

}