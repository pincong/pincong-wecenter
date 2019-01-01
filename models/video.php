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

	/**
	 * 记录日志
	 * @param int $item_id 内容id
	 * @param string $type VIDEO|VIDEO_COMMENT
	 * @param string $note
	 * @param int $uid
	 * @param int $anonymous
	 * @param int $child_id 回复/评论id
	 */
	public function log($item_id, $type, $note, $uid = 0, $anonymous = 0, $child_id = 0)
	{
		$this->insert('video_log', array(
			'item_id' => intval($item_id),
			'type' => $type,
			'note' => $note,
			'uid' => intval($uid),
			'anonymous' => intval($anonymous),
			'child_id' => intval($child_id),
			'time' => fake_time()
		));
	}

	/**
	 *
	 * 根据 item_id, 得到日志列表
	 *
	 * @param int     $item_id
	 * @param int     $limit
	 *
	 * @return array
	 */
	public function list_logs($item_id, $limit = 20)
	{
		$log_list = $this->fetch_all('video_log', 'item_id = ' . intval($item_id), 'id DESC', $limit);
		if (!$log_list)
		{
			return false;
		}

		foreach ($log_list AS $key => $log)
		{
			if (!$log['anonymous'])
			{
				$user_ids[] = $log['uid'];
			}
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

	public function get_video_info_by_id($video_id)
	{
		if (!is_digits($video_id))
		{
			return false;
		}

		static $videos;

		if (!$videos[$video_id])
		{
			if ($video = $this->fetch_row('video', 'id = ' . $video_id))
			{
				$videos[$video_id] = $video;
			}
		}

		return $videos[$video_id];
	}

	public function get_video_info_by_ids($video_ids)
	{
		if (!is_array($video_ids) OR sizeof($video_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($video_ids, 'intval_string');

		if ($video_list = $this->fetch_all('video', 'id IN(' . implode(',', $video_ids) . ')'))
		{
			foreach ($video_list AS $key => $val)
			{
				$result[$val['id']] = $val;
			}
		}

		return $result;
	}

	public function get_comment_by_id($comment_id)
	{
		if ($comment = $this->fetch_row('video_comment', 'id = ' . intval($comment_id)))
		{
			$comment_user_infos = $this->model('account')->get_user_info_by_uids(array(
				$comment['uid'],
				$comment['at_uid']
			));

			$comment['user_info'] = $comment_user_infos[$comment['uid']];
			$comment['at_user_info'] = $comment_user_infos[$comment['at_uid']];

			if (-$comment['agree_count'] >= get_setting('downvote_fold'))
			{
				$comment['fold'] = 2;
			}
		}

		return $comment;
	}

	public function get_comments_by_ids($comment_ids)
	{
		if (!is_array($comment_ids) OR !$comment_ids)
		{
			return false;
		}

		array_walk_recursive($comment_ids, 'intval_string');

		if ($comments = $this->fetch_all('video_comment', 'id IN (' . implode(',', $comment_ids) . ')'))
		{
			$downvote_fold = get_setting('downvote_fold');
			foreach ($comments AS $key => $val)
			{
				if (-$val['agree_count'] >= $downvote_fold)
				{
					$val['fold'] = 2;
				}

				$video_comments[$val['id']] = $val;
			}
		}

		return $video_comments;
	}

	public function get_comments($video_id, $page, $per_page)
	{
		if ($comments = $this->fetch_page('video_comment', 'video_id = ' . intval($video_id), 'id ASC', $page, $per_page))
		{
			$downvote_fold = get_setting('downvote_fold');
			foreach ($comments AS $key => $val)
			{
				if (-$val['agree_count'] >= $downvote_fold)
				{
					$comments[$key]['fold'] = 2;
				}

				$comment_uids[$val['uid']] = $val['uid'];

				if ($val['at_uid'])
				{
					$comment_uids[$val['at_uid']] = $val['at_uid'];
				}
			}

			if ($comment_uids)
			{
				$comment_user_infos = $this->model('account')->get_user_info_by_uids($comment_uids);
			}

			foreach ($comments AS $key => $val)
			{
				$comments[$key]['user_info'] = $comment_user_infos[$val['uid']];
				$comments[$key]['at_user_info'] = $comment_user_infos[$val['at_uid']];
			}
		}

		return $comments;
	}

	public function remove_video($video_id)
	{
		if (!$video_info = $this->get_video_info_by_id($video_id))
		{
			return false;
		}

		$this->delete('video_log', 'item_id = ' . intval($video_id));

		$this->delete('video_comment', "video_id = " . intval($video_id)); // 删除关联的回复内容

		$this->delete('video_danmaku', 'video_id = ' . intval($video_id));

		$this->delete('topic_relation', "`type` = 'video' AND item_id = " . intval($video_id));		// 删除话题关联

		//ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action IN(' . ACTION_LOG::ADD_VIDEO . ', ' . ACTION_LOG::ADD_AGREE_VIDEO . ', ' . ACTION_LOG::ADD_COMMENT_VIDEO . ') AND associate_id = ' . intval($video_id));	// 删除动作

		//$this->model('notify')->delete_notify('model_type = 8 AND source_id = ' . intval($video_id));	// 删除相关的通知

		$this->model('posts')->remove_posts_index($video_id, 'video');

		return $this->delete('video', 'id = ' . intval($video_id));
	}

	public function remove_video_comment($comment, $uid)
	{
		$this->update('video_comment', array(
			'message' => null
		), "id = " . $comment['id']);

		if ($uid == $comment['uid'])
		{
			$is_anonymous =  $comment['anonymous'];
		}
		$this->model('video')->log($comment['video_id'], 'VIDEO_COMMENT', '删除评论', $uid, $is_anonymous, $comment['id']);

		return true;
	}

	public function update_video($video_id, $uid, $title, $message, $anonymous = null, $category_id = null)
	{
		if (!$video_info = $this->model('video')->get_video_info_by_id($video_id))
		{
			return false;
		}

		if ($title)
		{
			$title = htmlspecialchars($title);
		}

		if ($message)
		{
			$message = htmlspecialchars($message);
		}

		$data = array(
			'title' => $title,
			'message' => $message
		);

		$this->model('search_fulltext')->push_index('video', $title, $video_info['id']);

		$this->update('video', $data, 'id = ' . intval($video_id));

		if ($uid == $video_info['uid'])
		{
			$is_anonymous =  $video_info['anonymous'];
		}
		$this->model('video')->log($video_id, 'VIDEO', '编辑投稿', $uid, $is_anonymous);

		// TODO: 修改分类
		// TODO: $source_type, $source, $duration

		return true;
	}

	public function get_video_list($category_id, $page, $per_page, $order_by, $day = null)
	{
		$where = array();

		if ($category_id)
		{
			$where[] = 'category_id = ' . intval($category_id);
		}

		if ($day)
		{
			$where[] = 'add_time > ' . (fake_time() - $day * 24 * 60 * 60);
		}

		return $this->fetch_page('video', implode(' AND ', $where), $order_by, $page, $per_page);
	}

	/*
	public function get_video_list_by_topic_ids($page, $per_page, $order_by, $topic_ids)
	{
		if (!$topic_ids)
		{
			return false;
		}

		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids
			);
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$result_cache_key = 'video_list_by_topic_ids_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);

		$found_rows_cache_key = 'video_list_by_topic_ids_found_rows_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);

		if (!$result = AWS_APP::cache()->get($result_cache_key) OR $found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';
			$topic_relation_where[] = "`type` = 'video'";

			if ($topic_relation_query = $this->query_all("SELECT item_id FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
			{
				foreach ($topic_relation_query AS $key => $val)
				{
					$video_ids[$val['item_id']] = $val['item_id'];
				}
			}

			if (!$video_ids)
			{
				return false;
			}

			$where[] = "id IN (" . implode(',', $video_ids) . ")";
		}

		if (!$result)
		{
			$result = $this->fetch_page('video', implode(' AND ', $where), $order_by, $page, $per_page);

			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
		}


		if (!$found_rows)
		{
			$found_rows = $this->found_rows();

			AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
		}

		$this->video_list_total = $found_rows;

		return $result;
	}
	*/

	public function lock_video($video_id, $lock_status = true, $uid = 0)
	{
		$lock_status = intval($lock_status);
		$this->update('video', array(
			'lock' => $lock_status
		), 'id = ' . intval($video_id));

		if ($lock_status)
		{
			$this->model('video')->log($video_id, 'VIDEO', '锁定投稿', $uid);
		}
		else
		{
			$this->model('video')->log($video_id, 'VIDEO', '解除锁定', $uid);
		}

		return true;
	}

	public function update_view_count($video_id)
	{
		if (AWS_APP::cache()->get('update_view_count_video_' . md5(session_id()) . '_' . intval($video_id)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_view_count_video_' . md5(session_id()) . '_' . intval($video_id), time(), 60);

		$this->shutdown_query("UPDATE " . $this->get_table('video') . " SET view_count = view_count + 1 WHERE id = " . intval($video_id));

		return true;
	}

	public function set_recommend($video_id)
	{
		$this->update('video', array(
			'is_recommend' => 1
		), 'id = ' . intval($video_id));

		$this->update('posts_index', array(
			'is_recommend' => 1
		), "post_id = " . intval($video_id) . " AND post_type = 'video'" );
	}

	public function unset_recommend($video_id)
	{
		$this->update('video', array(
			'is_recommend' => 0
		), 'id = ' . intval($video_id));

		$this->update('posts_index', array(
			'is_recommend' => 0
		), "post_id = " . intval($video_id) . " AND post_type = 'video'" );
	}

}