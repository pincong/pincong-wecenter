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

class question_class extends AWS_MODEL
{
	public function get_question_discussions_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_question_discussions_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('question_discussion', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['question_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('content')->get_posts_by_ids('question', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['question_info'] = $parents[$val['question_id']];
			}
		}

		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, S::get('cache_level_normal'));
		}

		return $list;
	}

	public function get_answer_discussions_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_answer_discussions_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('answer_discussion', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['answer_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('content')->get_posts_by_ids('answer', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['answer_info'] = $parents[$val['answer_id']];
			}
		}

		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, S::get('cache_level_normal'));
		}

		return $list;
	}

	public function get_questions_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_questions_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('question', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, S::get('cache_level_normal'));
		}

		return $list;
	}

	public function get_answers_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_answers_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('answer', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['question_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('content')->get_posts_by_ids('question', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['question_info'] = $parents[$val['question_id']];
			}
		}

		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, S::get('cache_level_normal'));
		}

		return $list;
	}

	public function modify_question($id, $title, $message, $log_uid)
	{
		if (!$item_info = $this->model('content')->get_thread_info_by_id('question', $id))
		{
			return false;
		}

		$this->model('search_fulltext')->push_index('question', $title, $item_info['id']);

		$this->update('question', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message)
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('question', $id, 'question', $id, '编辑', $log_uid);

		return true;
	}


	public function clear_question($id, $log_uid)
	{
		if (!$item_info = $this->model('content')->get_thread_info_by_id('question', $id))
		{
			return false;
		}

		$data = array(
			'title' => null,
			'message' => null,
			'title_fulltext' => null,
		);

		$trash_category_id = S::get_int('trash_category_id');
		if ($trash_category_id)
		{
			$where = [['post_id', 'eq', $id, 'i'], ['post_type', 'eq', 'question']];
			$this->update('posts_index', array('category_id' => $trash_category_id), $where);
			$data['category_id'] = $trash_category_id;
		}

		$this->update('question', $data, ['id', 'eq', $id, 'i']);

		$this->model('content')->log('question', $id, 'question', $id, '删除', $log_uid, 'category', $item_info['category_id']);

		return true;
	}


	public function modify_answer($id, $message, $log_uid)
	{
		if (!$reply_info = $this->model('content')->get_reply_info_by_id('answer', $id))
		{
			return false;
		}

		$this->update('answer', array(
			'message' => htmlspecialchars($message)
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('question', $reply_info['question_id'], 'answer', $id, '编辑', $log_uid);

		return true;
	}


	public function clear_answer($id, $log_uid)
	{
		if (!$reply_info = $this->model('content')->get_reply_info_by_id('answer', $id))
		{
			return false;
		}

		$this->update('answer', array(
			'message' => null,
			'fold' => 1
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('question', $reply_info['question_id'], 'answer', $id, '删除', $log_uid);

		return true;
	}

	public function clear_question_discussion(&$comment, $log_uid)
	{
		$this->update('question_discussion', array(
			'message' => null
		), ['id', 'eq', $comment['id'], 'i']);

		$this->model('content')->log('question', $comment['question_id'], 'question_discussion', $comment['id'], '删除', $log_uid);

		return true;
	}

	public function clear_answer_discussion(&$comment, $log_uid)
	{
		$this->update('answer_discussion', array(
			'message' => null
		), ['id', 'eq', $comment['id'], 'i']);

		if ($answer = $this->fetch_row('answer', 'id = ' . intval($comment['answer_id'])))
		{
			$this->model('content')->log('question', $answer['question_id'], 'answer_discussion', $comment['id'], '删除', $log_uid);
		}

		return true;
	}


	// 同时获取用户信息
	public function get_question_by_id($id)
	{
		if ($item = $this->fetch_row('question', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_answer_by_id($id)
	{
		if ($item = $this->fetch_row('answer', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_answers($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		//array_walk_recursive($thread_ids, 'intval_string');
		$where = ['question_id', 'in', $thread_ids, 'i'];

		if ($list = $this->fetch_page('answer', $where, $order, $page, $per_page))
		{
			foreach($list as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			if ($user_infos = $this->model('account')->get_user_info_by_uids($uids))
			{
				foreach($list as $key => $val)
				{
					$list[$key]['user_info'] = $user_infos[$val['uid']];
				}
			}
		}

		return $list;
	}


	// 同时获取用户信息
	public function get_question_discussion_by_id($id)
	{
		if ($item = $this->fetch_row('question_discussion', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}
		return $item;
	}

	// 同时获取用户信息
	public function get_question_discussions($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		//array_walk_recursive($thread_ids, 'intval_string');
		$where = ['question_id', 'in', $thread_ids, 'i'];

		if ($list = $this->fetch_page('question_discussion', $where, $order, $page, $per_page))
		{
			foreach($list as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			if ($users_info = $this->model('account')->get_user_info_by_uids($uids))
			{
				foreach($list as $key => $val)
				{
					$list[$key]['user_info'] = $users_info[$val['uid']];
				}
			}
		}

		return $list;
	}

	// 同时获取用户信息
	public function get_answer_discussion_by_id($id)
	{
		if ($item = $this->fetch_row('answer_discussion', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}
		return $item;
	}

	// 同时获取用户信息
	public function get_answer_discussions($parent_id, $page, $per_page, $order = 'id ASC')
	{
		$where = ['answer_id', 'eq', $parent_id, 'i'];

		if ($list = $this->fetch_page('answer_discussion', $where, $order, $page, $per_page))
		{
			foreach($list as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			if ($users_info = $this->model('account')->get_user_info_by_uids($uids))
			{
				foreach($list as $key => $val)
				{
					$list[$key]['user_info'] = $users_info[$val['uid']];
				}
			}
		}

		return $list;
	}





	/**
	 *
	 * 根据问题ID,得到相关联的话题标题信息
	 * @param int $question_id
	 * @param string $limit
	 *
	 * @return array
	 */
	public function get_question_topic_by_questions($question_ids, $limit = null)
	{
		if (!is_array($question_ids) OR sizeof($question_ids) == 0)
		{
			return false;
		}

		if (!$topic_ids_query = $this->query_all("SELECT DISTINCT topic_id FROM " . $this->get_table('topic_relation') . " WHERE item_id IN(" . implode(',', $question_ids) . ") AND `type` = 'question'"))
		{
			return false;
		}

		foreach ($topic_ids_query AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		$topic_list = $this->query_all("SELECT * FROM " . $this->get_table('topic') . " WHERE topic_id IN(" . implode(',', $topic_ids) . ") ORDER BY discuss_count DESC", $limit);

		return $topic_list;
	}


	public function get_topic_info_by_question_ids($question_ids)
	{
		if (!is_array($question_ids))
		{
			return false;
		}

		$where = [
			['item_id', 'in', $question_ids, 'i'],
			['type', 'eq', 'question']
		];
		if ($topic_relation = $this->fetch_all('topic_relation', $where))
		{
			foreach ($topic_relation AS $key => $val)
			{
				$topic_ids[$val['topic_id']] = $val['topic_id'];
			}

			$topics_info = $this->model('topic')->get_topics_by_ids($topic_ids);

			foreach ($topic_relation AS $key => $val)
			{
				$topics_by_questions_ids[$val['item_id']][] = array(
					'topic_id' => $val['topic_id'],
					'topic_title' => $topics_info[$val['topic_id']]['topic_title'],
				);
			}
		}

		return $topics_by_questions_ids;
	}


	public function get_related_topics($title)
	{
		if ($question_related_list = $this->get_related_question_list(null, $title, 10))
		{
			foreach ($question_related_list AS $key => $val)
			{
				$question_related_ids[$val['id']] = $val['id'];
			}

			$where = [
				['item_id', 'in', $question_related_ids, 'i'],
				['type', 'eq', 'question']
			];
			if (!$topic_ids_query = $this->fetch_all('topic_relation', $where))
			{
				return false;
			}

			foreach ($topic_ids_query AS $key => $val)
			{
				if ($val['merged_id'])
				{
					continue;
				}

				$topic_hits[$val['topic_id']] = intval($topic_hits[$val['topic_id']]) + 1;
			}

			if (!$topic_hits)
			{
				return false;
			}

			arsort($topic_hits);

			$topic_hits = array_slice($topic_hits, 0, 3, true);

			foreach ($topic_hits AS $topic_id => $hits)
			{
				if ($topic_info = $this->model('topic')->get_topic_by_id($topic_id))
				{
					$topics[$topic_info['topic_title']] = $topic_info['topic_title'];
				}
			}
		}

		return $topics;
	}


	public function get_answer_users_by_question_id($question_id, $limit = 5, $uid = null)
	{
		if ($result = AWS_APP::cache()->get('answer_users_by_question_id_' . md5($question_id . $limit . $uid)))
		{
			return $result;
		}

		if (!$uid)
		{
			if (!$question_info = $this->model('content')->get_thread_info_by_id('question', $question_id))
			{
				return false;
			}

			$uid = $question_info['uid'];
		}

		if ($answer_users = $this->query_all("SELECT DISTINCT uid FROM " . get_table('answer') . " WHERE question_id = " . intval($question_id) . " AND uid <> " . intval($uid) . " ORDER BY agree_count DESC LIMIT " . intval($limit)))
		{
			foreach ($answer_users AS $key => $val)
			{
				$answer_uids[] = $val['uid'];
			}

			$result = $this->model('account')->get_user_info_by_uids($answer_uids);

			AWS_APP::cache()->set('answer_users_by_question_id_' . md5($question_id . $limit . $uid), $result, S::get('cache_level_normal'));
		}

		return $result;
	}


	public function get_related_question_list($question_id, $title, $limit = 10)
	{
		$cache_key = 'question_related_list_' . md5($title) . '_' . $limit;

		if ($question_related_list = AWS_APP::cache()->get($cache_key))
		{
			return $question_related_list;
		}

		if ($question_keywords = $this->model('system')->analysis_keyword($title))
		{
			if (sizeof($question_keywords) <= 1)
			{
				return false;
			}

			if ($question_list = $this->query_all($this->model('search_fulltext')->bulid_query('question', 'title', $question_keywords), 2000))
			{
				$question_list = aasort($question_list, 'score', 'DESC');
				$question_list = aasort($question_list, 'agree_count', 'DESC');

				$question_list = array_slice($question_list, 0, ($limit + 1));

				foreach ($question_list as $key => $val)
				{
					if ($val['id'] == $question_id)
					{
						unset($question_list[$key]);
					}
					else
					{
						if (! isset($question_related[$val['id']]))
						{
							$question_related[$val['id']] = $val['title'];

							$question_info[$val['id']] = $val;
						}
					}
				}
			}
		}

		if ($question_related)
		{
			foreach ($question_related as $key => $title)
			{
				$question_related_list[] = array(
					'id' => $key,
					'title' => $title,
					'answer_count' => $question_info[$key]['answer_count']
				);
			}
		}

		if (sizeof($question_related) > $limit)
		{
			array_pop($question_related);
		}

		AWS_APP::cache()->set($cache_key, $question_related_list, S::get('cache_level_low'));

		return $question_related_list;
	}

}
