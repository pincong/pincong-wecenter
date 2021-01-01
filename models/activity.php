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
	/**
	 * 记录用户动态
	 * @param string $item_type question|question_discussion|answer|answer_discussion|article|article_comment|video|video_comment
	 * @param int $item_id
	 * @param string $note
	 * @param int $uid
	 */
	public function log($item_type, $item_id, $note, $uid = 0, $thread_id = 0, $category_id = 0)
	{
		$this->insert('activity', array(
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'note' => $note,
			'uid' => intval($uid),
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
			$answers = $this->model('answer')->get_answers_by_ids($answer_ids);
			foreach ($answers AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
				$question_ids[] = $val['question_id'];
			}
		}
		if ($question_ids)
		{
			$questions = $this->model('question')->get_question_info_by_ids($question_ids);
			foreach ($questions AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}
			if ($answers)
			{
				foreach ($answers AS $key => $val)
				{
					$answers[$key]['question_info'] = $questions[$val['question_id']];
				}
			}
		}

		// 获取文章和评论
		if ($article_comment_ids)
		{
			$article_comments = $this->model('article')->get_comments_by_ids($article_comment_ids);
			foreach ($article_comments AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
				$article_ids[] = $val['article_id'];
			}
		}
		if ($article_ids)
		{
			$articles = $this->model('article')->get_article_info_by_ids($article_ids);
			foreach ($articles AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}
			if ($article_comments)
			{
				foreach ($article_comments AS $key => $val)
				{
					$article_comments[$key]['article_info'] = $articles[$val['article_id']];
				}
			}
		}

		// 获取影片和评论
		if ($video_comment_ids)
		{
			$video_comments = $this->model('video')->get_comments_by_ids($video_comment_ids);
			foreach ($video_comments AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
				$video_ids[] = $val['video_id'];
			}
		}
		if ($video_ids)
		{
			$videos = $this->model('video')->get_video_info_by_ids($video_ids);
			foreach ($videos AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}
			if ($video_comments)
			{
				foreach ($video_comments AS $key => $val)
				{
					$video_comments[$key]['video_info'] = $videos[$val['video_id']];
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