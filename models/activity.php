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
	 * @param string $item_type question|question_discussion|answer|answer_discussion|article|article_comment|video|video_danmaku|video_comment
	 * @param int $item_id
	 * @param string $note
	 * @param int $uid
	 */
	public function log($item_type, $item_id, $note, $uid = 0)
	{
		$this->insert('activity', array(
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'note' => $note,
			'uid' => intval($uid),
			'time' => fake_time()
		));
	}

	// 列出我关注的人的动态
	private function do_list_activities($uid, $page, $per_page)
	{
		$following_uids = $this->model('follow')->get_user_friends_ids($uid, 1000);
		if (!$following_uids)
		{
			return array();
		}

		$where = "uid IN(" . implode(',', $following_uids) . ")";

		// 获取元数据
		$activities = $this->fetch_page('activity', $where, 'id DESC', $page, $per_page);

		foreach ($activities AS $key => $val)
		{
			$uids[$val['uid']] = $val['uid'];

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

		$users = $this->model('account')->get_user_info_by_uids($uids);
		if (!$users)
		{
			return array();
		}

		// 获取问题和回答
		if ($answer_ids)
		{
			$answers = $this->model('answer')->get_answers_by_ids($answer_ids);
			foreach ($answers AS $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
		}
		if ($question_ids)
		{
			$questions = $this->model('question')->get_question_info_by_ids($question_ids);
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
				$article_ids[] = $val['article_id'];
			}
		}
		if ($article_ids)
		{
			$articles = $this->model('article')->get_article_info_by_ids($article_ids);
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
				$video_ids[] = $val['video_id'];
			}
		}
		if ($video_ids)
		{
			$videos = $this->model('video')->get_video_info_by_ids($video_ids);
			if ($video_comments)
			{
				foreach ($video_comments AS $key => $val)
				{
					$video_comments[$key]['video_info'] = $videos[$val['video_id']];
				}
			}
		}

		// 把数据附着到 $activities
		foreach ($activities AS $key => $val)
		{
			$activities[$key]['user_info'] = $users[$val['uid']];

			if ($val['item_type'] == 'question')
			{
				$activities[$key]['item'] = $questions[$val['item_id']];
			}
			elseif ($val['item_type'] == 'answer')
			{
				$activities[$key]['item'] = $answers[$val['item_id']];
			}
			elseif ($val['item_type'] == 'article')
			{
				$activities[$key]['item'] = $articles[$val['item_id']];
			}
			elseif ($val['item_type'] == 'article_comment')
			{
				$activities[$key]['item'] = $article_comments[$val['item_id']];
			}
			elseif ($val['item_type'] == 'video')
			{
				$activities[$key]['item'] = $videos[$val['item_id']];
			}
			elseif ($val['item_type'] == 'video_comment')
			{
				$activities[$key]['item'] = $video_comments[$val['item_id']];
			}
		}

		return $activities;
	}

	public function list_activities($uid, $page, $per_page)
	{
		$cache_key = 'user_activities_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->do_list_activities($uid, $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

}