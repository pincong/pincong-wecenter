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

class activity_class extends AWS_MODEL
{
	public function check_push_category($category_id)
	{
		$push_categories = get_setting('push_categories');
		if (!$push_categories)
		{
			return true;
		}
		$push_categories = explode(',', $push_categories);
		if (!is_array($push_categories))
		{
			return false;
		}
		$push_categories = array_map('intval', $push_categories);
		if (!in_array($category_id, $push_categories))
		{
			return false;
		}
		return true;
	}

	public function check_push_type($type)
	{
		$push_types = get_setting('push_types');
		if (!$push_types)
		{
			return true;
		}
		$push_types = explode(',', $push_types);
		if (!is_array($push_types))
		{
			return false;
		}
		$push_types = array_map('trim', $push_types);
		if (!in_array($type, $push_types))
		{
			return false;
		}
		return true;
	}

	public function push($item_type, $item_id, $uid = 0, $thread_type = null, $thread_id = 0, $category_id = 0)
	{
		if (!$this->check_push_type($item_type))
		{
			return;
		}
		if ($category_id AND !$this->check_push_category($category_id))
		{
			return;
		}
		$this->log($item_type, $item_id, $uid, $thread_type, $thread_id, $category_id);
	}

	/**
	 * 记录动态
	 * @param string $item_type question|answer|article|article_comment|video|video_comment
	 * @param int $item_id
	 * @param int $uid
	 * @param string $thread_type question|article|video
	 * @param int $thread_id
	 * @param int $category_id
	 */
	public function log($item_type, $item_id, $uid = 0, $thread_type = null, $thread_id = 0, $category_id = 0)
	{
		$this->insert('activity', array(
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'uid' => intval($uid),
			'thread_type' => $thread_type,
			'thread_id' => intval($thread_id),
			'category_id' => intval($category_id),
			'time' => fake_time()
		));
	}

	private function query_activities($where, $page, $per_page)
	{
		// 获取元数据
		$activities = $this->fetch_page('activity', $where, 'id DESC', $page, $per_page);

		foreach ($activities AS $key => $val)
		{
			if ($val['item_type'] == 'question')
			{
				$question_ids[] = $val['item_id'];
			}
			elseif ($val['item_type'] == 'answer')
			{
				$answer_ids[] = $val['item_id'];
			}
			elseif ($val['item_type'] == 'article')
			{
				$article_ids[] = $val['item_id'];
			}
			elseif ($val['item_type'] == 'article_comment')
			{
				$article_comment_ids[] = $val['item_id'];
			}
			elseif ($val['item_type'] == 'video')
			{
				$video_ids[] = $val['item_id'];
			}
			elseif ($val['item_type'] == 'video_comment')
			{
				$video_comment_ids[] = $val['item_id'];
			}
		}

		// 获取问题和回答
		if ($answer_ids)
		{
			$answers = $this->model('content')->get_posts_by_ids('answer', $answer_ids);
			foreach ($answers AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
				$question_ids[] = $val['question_id'];
			}
		}
		if ($question_ids)
		{
			$questions = $this->model('content')->get_posts_by_ids('question', $question_ids);
			foreach ($questions AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}
			if ($answers)
			{
				foreach ($answers AS $key => $val)
				{
					$answers[$key]['thread_info'] = $questions[$val['question_id']];
				}
			}
		}

		// 获取文章和评论
		if ($article_comment_ids)
		{
			$article_comments = $this->model('content')->get_posts_by_ids('article_comment', $article_comment_ids);
			foreach ($article_comments AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
				$article_ids[] = $val['article_id'];
			}
		}
		if ($article_ids)
		{
			$articles = $this->model('content')->get_posts_by_ids('article', $article_ids);
			foreach ($articles AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}
			if ($article_comments)
			{
				foreach ($article_comments AS $key => $val)
				{
					$article_comments[$key]['thread_info'] = $articles[$val['article_id']];
				}
			}
		}

		// 获取影片和评论
		if ($video_comment_ids)
		{
			$video_comments = $this->model('content')->get_posts_by_ids('video_comment', $video_comment_ids);
			foreach ($video_comments AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
				$video_ids[] = $val['video_id'];
			}
		}
		if ($video_ids)
		{
			$videos = $this->model('content')->get_posts_by_ids('video', $video_ids);
			foreach ($videos AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}
			if ($video_comments)
			{
				foreach ($video_comments AS $key => $val)
				{
					$video_comments[$key]['thread_info'] = $videos[$val['video_id']];
				}
			}
		}

		$users = $this->model('account')->get_user_info_by_uids($uids);
		$items = array();

		// 把数据放入 $items
		foreach ($activities AS $key => $val)
		{
			if ($val['item_type'] == 'question')
			{
				$item = $questions[$val['item_id']];
				$item['item_type'] = 'question';
			}
			elseif ($val['item_type'] == 'answer')
			{
				$item = $answers[$val['item_id']];
				$item['item_type'] = 'answer';
			}
			elseif ($val['item_type'] == 'article')
			{
				$item = $articles[$val['item_id']];
				$item['item_type'] = 'article';
			}
			elseif ($val['item_type'] == 'article_comment')
			{
				$item = $article_comments[$val['item_id']];
				$item['item_type'] = 'article_comment';
			}
			elseif ($val['item_type'] == 'video')
			{
				$item = $videos[$val['item_id']];
				$item['item_type'] = 'video';
			}
			elseif ($val['item_type'] == 'video_comment')
			{
				$item = $video_comments[$val['item_id']];
				$item['item_type'] = 'video_comment';
			}
			else
			{
				continue;
			}
			$item['user_info'] = $users[$item['uid']];
			if ($item['thread_info'])
			{
				$item['thread_info']['user_info'] = $users[$item['thread_info']['uid']];
			}
			$item['item_category_id'] = $val['category_id'];
			$item['item_thread_id'] = $val['thread_id'];
			$items[] = $item;
		}

		return $items;
	}


	// 列出我关注的人的动态
	public function list_activities($uid, $page, $per_page)
	{
		$cache_key = 'user_activities_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$following_uids = $this->model('follow')->get_user_friends_ids($uid, 1000);
		if (!$following_uids)
		{
			return array();
		}

		$where = "uid IN(" . implode(',', $following_uids) . ")";

		$list = $this->query_activities($where, $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

	// 列出精选动态
	public function list_hot_activities($category_id, $page, $per_page)
	{
		$category_id = intval($category_id);
		$cache_key = 'hot_activities_' . $category_id . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$where = "uid = 0";
		if ($category_id)
		{
			$where = $where . " AND category_id = " . ($category_id);
		}
		else
		{
			$where = $where . ' AND `category_id` IN(' . implode(',', $this->model('posts')->get_default_category_ids()) . ')';
		}

		$list = $this->query_activities($where, $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

	public function delete_expired_data()
	{
		$days = intval(get_setting('expiration_user_actions'));
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
		$this->delete('activity', 'time < ' . $time_before);
	}

}