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
			$parents = $this->get_video_info_by_ids($parent_ids);
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

	public function modify_video($id, $uid, $title, $message)
	{
		if (!$item_info = $this->model('video')->get_video_info_by_id($id))
		{
			return false;
		}

		$category_id = intval($category_id);

		//$this->model('search_fulltext')->push_index('video', $title, $item_info['id']);

		$this->update('video', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message)
		), 'id = ' . intval($id));

		$this->model('content')->log('video', $id, '编辑影片', $uid);

		return true;
	}


	public function clear_video($id, $uid = null)
	{
		if (!$item_info = $this->model('video')->get_video_info_by_id($id))
		{
			return false;
		}

		$this->update('video', array(
			'title' => null,
			'message' => null,
			'title_fulltext' => null,
			'source_type' => null,
			'source' => null,
			'duration' => 0,
		), 'id = ' . intval($id));

		if ($uid)
		{
			$this->model('content')->log('video', $id, '删除影片', $uid);
		}

		return true;
	}


	public function modify_video_comment($comment_id, $uid, $message)
	{
		if (!$comment_info = $this->model('video')->get_comment_by_id($comment_id))
		{
			return false;
		}

		$this->update('video_comment', array(
			'message' => htmlspecialchars($message)
		), 'id = ' . intval($comment_id));

		$this->model('content')->log('video', $comment_info['video_id'], '编辑影片评论', $uid, 'video_comment', $comment_id);

		return true;
	}

	public function clear_video_comment($comment_id, $uid)
	{
		if (!$comment_info = $this->model('video')->get_comment_by_id($comment_id))
		{
			return false;
		}

		$this->update('video_comment', array(
			'message' => null
		), 'id = ' . intval($comment_id));

		$this->model('content')->log('video', $comment_info['video_id'], '删除影片评论', $uid, 'video_comment', $comment_id);

		return true;
	}


	public function update_video_source($id, $source_type, $source, $duration)
	{
		$this->update('video', array(
			'source_type' => $source_type,
			'source' => $source,
			'duration' => $duration,
		), 'id = ' . intval($id));

		return true;
	}


	public function update_video_comment_count($video_id)
	{
		$video_id = intval($video_id);
		if (!$video_id)
		{
			return false;
		}

		return $this->update('video', array(
			'comment_count' => $this->count('video_comment', 'video_id = ' . ($video_id))
		), 'id = ' . ($video_id));
	}

	public function update_video_danmaku_count($video_id)
	{
		$video_id = intval($video_id);
		if (!$video_id)
		{
			return false;
		}

		return $this->update('video', array(
			'danmaku_count' => $this->count('video_danmaku', 'video_id = ' . ($video_id))
		), 'id = ' . ($video_id));
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
				$comment['fold'] = -2;
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
			// 不折叠
			foreach ($comments AS $key => $val)
			{
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
					$comments[$key]['fold'] = -2;
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

	// TODO
	/*
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
	*/

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

}