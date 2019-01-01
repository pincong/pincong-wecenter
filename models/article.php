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

class article_class extends AWS_MODEL
{
	public function get_article_info_by_id($article_id)
	{
		if (!is_digits($article_id))
		{
			return false;
		}

		static $articles;

		if (!$articles[$article_id])
		{
            $and = ' AND add_time <= ' . real_time();
			if ($article = $this->fetch_row('article', 'id = ' . $article_id . $and))
			{
				if (-$article['votes'] >= get_setting('article_downvote_fold'))
				{
					$article['fold'] = 1;
				}

				$articles[$article_id] = $article;
			}
		}

		return $articles[$article_id];
	}

	public function get_article_info_by_ids($article_ids)
	{
		if (!is_array($article_ids) OR sizeof($article_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($article_ids, 'intval_string');

        $and = ' AND add_time <= ' . real_time();

		if ($articles_list = $this->fetch_all('article', 'id IN(' . implode(',', $article_ids) . ')' . $and))
		{
			$article_downvote_fold = get_setting('article_downvote_fold');
			foreach ($articles_list AS $key => $val)
			{
				if (-$val['votes'] >= $article_downvote_fold)
				{
					$val['fold'] = 1;
				}

				$result[$val['id']] = $val;
			}
		}

		return $result;
	}

	public function get_comment_by_id($comment_id)
	{
		if ($comment = $this->fetch_row('article_comments', 'id = ' . intval($comment_id)))
		{
			$comment_user_infos = $this->model('account')->get_user_info_by_uids(array(
				$comment['uid'],
				$comment['at_uid']
			));

			$comment['user_info'] = $comment_user_infos[$comment['uid']];
			$comment['at_user_info'] = $comment_user_infos[$comment['at_uid']];

			if (-$comment['votes'] >= get_setting('comment_downvote_fold'))
			{
				$comment['fold'] = 1;
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

		if ($comments = $this->fetch_all('article_comments', 'id IN (' . implode(',', $comment_ids) . ')'))
		{
			$comment_downvote_fold = get_setting('comment_downvote_fold');
			foreach ($comments AS $key => $val)
			{
				if (-$val['votes'] >= $comment_downvote_fold)
				{
					$val['fold'] = 1;
				}
				$article_comments[$val['id']] = $val;
			}
		}

		return $article_comments;
	}

	public function get_comments($article_id, $page, $per_page)
	{
		if ($comments = $this->fetch_page('article_comments', 'article_id = ' . intval($article_id), 'id ASC', $page, $per_page))
		{
			$comment_downvote_fold = get_setting('comment_downvote_fold');
			foreach ($comments AS $key => $val)
			{
				if (-$val['votes'] >= $comment_downvote_fold)
				{
					$comments[$key]['fold'] = 1;
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

	public function remove_article($article_id)
	{
		if (!$article_info = $this->get_article_info_by_id($article_id))
		{
			return false;
		}

		$this->delete('article_comments', "article_id = " . intval($article_id)); // 删除关联的回复内容

		$this->delete('article_vote', 'article_id = ' . intval($article_id));

		$this->delete('article_thanks', 'article_id = ' . intval($article_id));

		$this->delete('topic_relation', "`type` = 'article' AND item_id = " . intval($article_id));		// 删除话题关联

		ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action IN(' . ACTION_LOG::ADD_ARTICLE . ', ' . ACTION_LOG::ADD_AGREE_ARTICLE . ', ' . ACTION_LOG::ADD_COMMENT_ARTICLE . ') AND associate_id = ' . intval($article_id));	// 删除动作

		$this->model('notify')->delete_notify('model_type = 8 AND source_id = ' . intval($article_id));	// 删除相关的通知

		$this->model('posts')->remove_posts_index($article_id, 'article');

		$this->shutdown_update('users', array(
			'article_count' => $this->count('article', 'uid = ' . intval($uid))
		), 'uid = ' . intval($uid));

		return $this->delete('article', 'id = ' . intval($article_id));
	}

	public function remove_comment($comment_id)
	{
		$comment_info = $this->get_comment_by_id($comment_id);

		if (!$comment_info)
		{
			return false;
		}

		$this->delete('article_comments', 'id = ' . $comment_info['id']);

		$this->update('article', array(
			'comments' => $this->count('article_comments', 'article_id = ' . $comment_info['article_id'])
		), 'id = ' . $comment_info['article_id']);

		return true;
	}

	public function update_comment($comment_id, $message, $at_uid = null, $anonymous = null)
	{
		$comment_info = $this->get_comment_by_id($comment_id);

		if (!$comment_info)
		{
			return false;
		}

		if (isset($message))
		{
			$message = htmlspecialchars($message);
		}

		$this->update('article_comments', array(
			'message' => $message,
			'at_uid' => intval($at_uid),
			'anonymous' => intval($anonymous)
		), 'id = ' . $comment_info['id']);

		return true;
	}

	public function update_article($article_id, $uid, $title, $message, $anonymous = null, $category_id = null)
	{
		if (!$article_info = $this->model('article')->get_article_info_by_id($article_id))
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

		$this->model('search_fulltext')->push_index('article', $title, $article_info['id']);

		$this->update('article', $data, 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');

		return true;
	}

	public function get_articles_list($category_id, $page, $per_page, $order_by, $day = null)
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

        $where[] = 'add_time <= ' . real_time();

		return $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);
	}

	public function get_articles_list_by_topic_ids($page, $per_page, $order_by, $topic_ids)
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

		$result_cache_key = 'article_list_by_topic_ids_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);

		$found_rows_cache_key = 'article_list_by_topic_ids_found_rows_' . md5(implode('_', $topic_ids) . $order_by . $page . $per_page);

		if (!$result = AWS_APP::cache()->get($result_cache_key) OR $found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';
			$topic_relation_where[] = "`type` = 'article'";

			if ($topic_relation_query = $this->query_all("SELECT item_id FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
			{
				foreach ($topic_relation_query AS $key => $val)
				{
					$article_ids[$val['item_id']] = $val['item_id'];
				}
			}

			if (!$article_ids)
			{
				return false;
			}

			$where[] = "id IN (" . implode(',', $article_ids) . ")";
		}

        $where[] = 'add_time <= ' . real_time();

		if (!$result)
		{
			$result = $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);

			AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
		}


		if (!$found_rows)
		{
			$found_rows = $this->found_rows();

			AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
		}

		$this->article_list_total = $found_rows;

		return $result;
	}

	public function lock_article($article_id, $lock_status = true)
	{
		return $this->update('article', array(
			'lock' => intval($lock_status)
		), 'id = ' . intval($article_id));
	}

	/**
	 *
	 * 文章或评论投票
	 * @param string $type     //article|comment
	 * @param int $item_id     //文章或评论id
	 * @param int $rating      //-1反对 1 赞同
	 * @param int $uid         //投票用户ID
	 * @param int $reputation_factor
	 * @param int $item_uid    //被投票用户ID
	 *
	 * @return boolean true|false
	 */
	public function article_vote($type, $item_id, $rating, $uid, $reputation_factor, $item_uid)
	{
		$rating = intval($rating);
		if ($rating !== 1 AND $rating !== -1)
		{
			return false;
		}
		if ($type !== 'article' AND $type !== 'comment')
		{
			return false;
		}

		$item_id = intval($item_id);
		$uid = intval($uid);
		$item_uid = intval($item_uid);

		$vote_info = $this->fetch_row('article_vote', "`type` = '" . ($type) . "' AND item_id = " . ($item_id) . ' AND uid = ' . ($uid));
		if (!$vote_info) //添加记录
		{
			$this->insert('article_vote', array(
				'type' => $type,
				'item_id' => $item_id,
				'rating' => $rating,
				'time' => fake_time(),
				'uid' => $uid,
				'reputation_factor' => $reputation_factor
			));

			if ($type == 'article') //文章
			{
				if ($rating == 1)
				{
					if (!$this->model('currency')->fetch_log($uid, 'AGREE_ARTICLE', $item_id))
					{
						$this->model('currency')->process($uid, 'AGREE_ARTICLE', get_setting('currency_system_config_agree_question'), '赞同文章 #' . $item_id, $item_id);
						$this->model('currency')->process($item_uid, 'ARTICLE_AGREED', get_setting('currency_system_config_question_agreed'), '文章被赞同 #' . $item_id, $item_id);
					}
				}
				else
				{
					if (!$this->model('currency')->fetch_log($uid, 'DISAGREE_ARTICLE', $item_id))
					{
						$this->model('currency')->process($uid, 'DISAGREE_ARTICLE', get_setting('currency_system_config_disagree_question'), '反对文章 #' . $item_id, $item_id);
						$this->model('currency')->process($item_uid, 'ARTICLE_DISAGREED', get_setting('currency_system_config_question_disagreed'), '文章被反对 #' . $item_id, $item_id);
					}
				}
			}
			else //评论
			{
				if ($rating == 1)
				{
					if (!$this->model('currency')->fetch_log($uid, 'AGREE_ARTICLE_COMMENT', $item_id))
					{
						$this->model('currency')->process($uid, 'AGREE_ARTICLE_COMMENT', get_setting('currency_system_config_agree_answer'), '赞同文章评论 #' . $item_id, $item_id);
						$this->model('currency')->process($item_uid, 'ARTICLE_COMMENT_AGREED', get_setting('currency_system_config_answer_agreed'), '文章评论被赞同 #' . $item_id, $item_id);
					}
				}
				else
				{
					if (!$this->model('currency')->fetch_log($uid, 'DISAGREE_ARTICLE_COMMENT', $item_id))
					{
						$this->model('currency')->process($uid, 'DISAGREE_ARTICLE_COMMENT', get_setting('currency_system_config_disagree_answer'), '反对文章评论 #' . $item_id, $item_id);
						$this->model('currency')->process($item_uid, 'ARTICLE_COMMENT_DISAGREED', get_setting('currency_system_config_answer_disagreed'), '文章评论被反对 #' . $item_id, $item_id);
					}
				}
			}

			$add_agree_count = $rating;
		}
		else if ($vote_info['rating'] == $rating) //删除记录
		{
			$this->delete('article_vote', 'id = ' . intval($vote_info['id']));

			$add_agree_count = -$rating;
		}
		else //更新记录
		{
			$this->update('article_vote', array(
					'rating' => $rating,
					'time' => fake_time(),
					'reputation_factor' => $reputation_factor
			), 'id = ' . intval($vote_info['id']));

			$add_agree_count = $rating * 2;
		}

		switch ($type)
		{
			case 'article':
				$this->query('UPDATE ' . $this->get_table('article') . ' SET votes = votes + ' . $add_agree_count . ' WHERE id = ' . ($item_id));
			break;

			case 'comment':
				$this->query('UPDATE ' . $this->get_table('article_comments') . ' SET votes = votes + ' . $add_agree_count . ' WHERE id = ' . ($item_id));
			break;
		}

		// 更新作者赞同数和威望
		$this->model('reputation')->increase_agree_count_and_reputation($item_uid, $add_agree_count, $reputation_factor);

		return true;
	}

	public function get_article_vote_by_id($type, $item_id, $rating = null, $uid = null)
	{
		if ($article_vote = $this->get_article_vote_by_ids($type, array(
			$item_id
		), $rating, $uid))
		{
			return end($article_vote[$item_id]);
		}
	}

	public function get_article_vote_by_ids($type, $item_ids, $rating = null, $uid = null)
	{
		if (!is_array($item_ids))
		{
			return false;
		}

		if (sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($uid)
		{
			$where[] = 'uid = ' . intval($uid);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$result[$val['item_id']][] = $val;
			}
		}

		return $result;
	}

	public function get_article_vote_users_by_id($type, $item_id, $rating = null, $limit = null)
	{
		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id = ' . intval($item_id);

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}

			return $this->model('account')->get_user_info_by_uids($uids);
		}
	}

	public function get_article_vote_users_by_ids($type, $item_ids, $rating = null, $limit = null)
	{
		if (! is_array($item_ids))
		{
			return false;
		}

		if (sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		$where[] = "`type` = '" . $this->quote($type) . "'";
		$where[] = 'item_id IN(' . implode(',', $item_ids) . ')';

		if ($rating)
		{
			$where[] = 'rating = ' . intval($rating);
		}

		if ($article_votes = $this->fetch_all('article_vote', implode(' AND ', $where)))
		{
			foreach ($article_votes AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];
			}

			$users_info = $this->model('account')->get_user_info_by_uids($uids);

			foreach ($article_votes AS $key => $val)
			{
				$vote_users[$val['item_id']][$val['uid']] = $users_info[$val['uid']];
			}

			return $vote_users;
		}
	}

	public function update_views($article_id)
	{
		if (AWS_APP::cache()->get('update_views_article_' . md5(session_id()) . '_' . intval($article_id)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_views_article_' . md5(session_id()) . '_' . intval($article_id), time(), 60);

		$this->shutdown_query("UPDATE " . $this->get_table('article') . " SET views = views + 1 WHERE id = " . intval($article_id));

		return true;
	}

	public function set_recommend($article_id)
	{
		$this->update('article', array(
			'is_recommend' => 1
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');
	}

	public function unset_recommend($article_id)
	{
		$this->update('article', array(
			'is_recommend' => 0
		), 'id = ' . intval($article_id));

		$this->model('posts')->set_posts_index($article_id, 'article');
	}

	public function get_article_thanks($article_id, $uid)
	{
		if (!$article_id OR !$uid)
		{
			return false;
		}

		return $this->fetch_row('article_thanks', 'article_id = ' . intval($article_id) . " AND uid = " . intval($uid));
	}

	public function article_thanks($article_id, $uid)
	{
		if (!$article_id OR !$uid)
		{
			return false;
		}

		if (!$article_info = $this->get_article_info_by_id($article_id))
		{
			return false;
		}

		if ($article_thanks = $this->get_article_thanks($article_id, $uid))
		{
			//$this->delete('article_thanks', "id = " . $article_thanks['id']);

			return false;
		}
		else
		{
			$this->insert('article_thanks', array(
				'article_id' => $article_id,
				'uid' => $uid,
				'time' => fake_time()
			));

			$this->query('UPDATE ' . $this->get_table('article') . ' SET thanks_count = thanks_count + 1 WHERE id = ' . intval($article_id));

			$this->model('currency')->process($uid, 'ARTICLE_THANKS', get_setting('currency_system_config_thanks'), '感谢文章 #' . $article_id, $article_id);

			$this->model('currency')->process($article_info['uid'], 'THANKS_ARTICLE', -get_setting('currency_system_config_thanks'), '文章被感谢 #' . $article_id, $article_id);

			$this->model('account')->update_thanks_count($article_info['uid']);

			return true;
		}
	}

	public function delete_expired_votes()
	{
		$days = intval(get_setting('expiration_votes'));
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
		$this->delete('article_vote', 'time < ' . $time_before);
		$this->delete('article_thanks', 'time < ' . $time_before);
	}

}