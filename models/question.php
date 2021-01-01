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

	public function clear_question_discussion($comment, $log_uid)
	{
		$this->update('question_discussion', array(
			'message' => null
		), ['id', 'eq', $comment['id'], 'i']);

		$this->model('content')->log('question', $comment['question_id'], 'question_discussion', $comment['id'], '删除', $log_uid);

		return true;
	}

	public function clear_answer_discussion($comment, $log_uid)
	{
		$this->update('answer_discussion', array(
			'message' => null
		), ['id', 'eq', $comment['id'], 'i']);

		if ($answer = $this->fetch_row('answer', ['id', 'eq', $comment['answer_id'], 'i']))
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
					$list[$key]['user_info'] = $user_infos[$val['uid']] ?? null;
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
					$list[$key]['user_info'] = $users_info[$val['uid']] ?? null;
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
					$list[$key]['user_info'] = $users_info[$val['uid']] ?? null;
				}
			}
		}

		return $list;
	}


	public function get_answer_users_by_question_id($question_id, $limit, $question_uid)
	{
		$cache_key = 'answer_users_by_question_id_' . md5($question_id . $limit . $question_uid);
		if ($result = AWS_APP::cache()->get($cache_key))
		{
			return $result;
		}

		$answer_uids = $this->fetch_distinct('answer', 'uid', [['question_id', 'eq', $question_id, 'i'], ['uid', 'notEq', $question_uid, 'i']], 'agree_count DESC', $limit);
		if ($answer_uids)
		{
			$result = $this->model('account')->get_user_info_by_uids($answer_uids);

			AWS_APP::cache()->set($cache_key, $result, S::get('cache_level_normal'));
		}

		return $result;
	}

}
