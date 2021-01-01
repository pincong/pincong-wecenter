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
	public function get_articles_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_articles_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('article', 'uid = ' . intval($uid), 'id DESC', $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

	public function get_article_comments_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_article_comments_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('article_comment', 'uid = ' . intval($uid), 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['article_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->get_article_info_by_ids($parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['article_info'] = $parents[$val['article_id']];
			}
		}

		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, get_setting('cache_level_normal'));
		}

		return $list;
	}

	public function modify_article($id, $uid, $title, $message)
	{
		if (!$item_info = $this->model('content')->get_thread_info_by_id('article', $id))
		{
			return false;
		}

		$this->model('search_fulltext')->push_index('article', $title, $item_info['id']);

		$this->update('article', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message)
		), 'id = ' . intval($id));

		$this->model('content')->log('article', $id, '编辑文章', $uid);

		return true;
	}

	public function clear_article($id, $uid = null)
	{
		if (!$item_info = $this->model('content')->get_thread_info_by_id('article', $id))
		{
			return false;
		}

		$data = array(
			'title' => null,
			'message' => null,
			'title_fulltext' => null,
		);

		$trash_category_id = intval(get_setting('trash_category_id'));
		if ($trash_category_id)
		{
			$where = "post_id = " . intval($id) . " AND post_type = 'article'";
			$this->update('posts_index', array('category_id' => $trash_category_id), $where);
			$data['category_id'] = $trash_category_id;
		}

		$this->update('article', $data, 'id = ' . intval($id));

		if ($uid)
		{
			$this->model('content')->log('article', $id, '删除文章', $uid);
		}

		return true;
	}

	public function modify_article_comment($comment_id, $uid, $message)
	{
		if (!$comment_info = $this->model('content')->get_reply_info_by_id('article_comment', $comment_id))
		{
			return false;
		}

		$this->update('article_comment', array(
			'message' => htmlspecialchars($message)
		), 'id = ' . intval($comment_id));

		$this->model('content')->log('article', $comment_info['article_id'], '编辑文章评论', $uid, 'article_comment', $comment_id);

		return true;
	}

	public function clear_article_comment($comment_id, $uid)
	{
		if (!$comment_info = $this->model('content')->get_reply_info_by_id('article_comment', $comment_id))
		{
			return false;
		}

		$this->update('article_comment', array(
			'message' => null
		), 'id = ' . intval($comment_id));

		$this->model('content')->log('article', $comment_info['article_id'], '删除文章评论', $uid, 'article_comment', $comment_id);

		return true;
	}


	public function update_article_comment_count($article_id)
	{
		$article_id = intval($article_id);
		if (!$article_id)
		{
			return false;
		}

		// TODO: rename comments to comment_count
		return $this->update('article', array(
			'comments' => $this->count('article_comment', 'article_id = ' . ($article_id))
		), 'id = ' . ($article_id));
	}

	// 同时获取用户信息
	public function get_article_info_by_id($article_id)
	{
		if ($article = $this->fetch_row('article', 'id = ' . intval($article_id)))
		{
			$article['user_info'] = $this->model('account')->get_user_info_by_uid($article['uid']);
		}

		return $article;
	}

	public function get_article_info_by_ids($article_ids)
	{
		if (!is_array($article_ids) OR sizeof($article_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($article_ids, 'intval_string');

		if ($article_list = $this->fetch_all('article', 'id IN(' . implode(',', $article_ids) . ')'))
		{
			foreach ($article_list AS $key => $val)
			{
				$result[$val['id']] = $val;
			}
		}

		return $result;
	}

	// 同时获取用户信息
	public function get_comment_by_id($comment_id)
	{
		if ($comment = $this->fetch_row('article_comment', 'id = ' . intval($comment_id)))
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

		if ($comments = $this->fetch_all('article_comment', 'id IN (' . implode(',', $comment_ids) . ')'))
		{
			// 不折叠
			foreach ($comments AS $key => $val)
			{
				$article_comments[$val['id']] = $val;
			}
		}

		return $article_comments;
	}

	public function get_comments($article_id, $page, $per_page)
	{
		if ($comments = $this->fetch_page('article_comment', 'article_id = ' . intval($article_id), 'id ASC', $page, $per_page))
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
	public function remove_article($article_id)
	{
		if (!$article_info = $this->model('content')->get_thread_info_by_id('article', $article_id))
		{
			return false;
		}

		$this->delete('article_log', 'item_id = ' . intval($article_id));

		$this->delete('article_comment', "article_id = " . intval($article_id)); // 删除关联的回复内容

		$this->delete('topic_relation', "`type` = 'article' AND item_id = " . intval($article_id));		// 删除话题关联

		//ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action IN(' . ACTION_LOG::ADD_ARTICLE . ', ' . ACTION_LOG::ADD_AGREE_ARTICLE . ', ' . ACTION_LOG::ADD_COMMENT_ARTICLE . ') AND associate_id = ' . intval($article_id));	// 删除动作

		$this->model('notify')->delete_notify('model_type = 8 AND source_id = ' . intval($article_id));	// 删除相关的通知

		$this->model('posts')->remove_posts_index($article_id, 'article');

		return $this->delete('article', 'id = ' . intval($article_id));
	}
	*/

	public function get_article_list($category_id, $page, $per_page, $order_by, $day = null)
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

		return $this->fetch_page('article', implode(' AND ', $where), $order_by, $page, $per_page);
	}

	public function get_article_list_by_topic_ids($page, $per_page, $order_by, $topic_ids)
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

}