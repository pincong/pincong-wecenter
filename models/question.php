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

		$list = $this->fetch_page('question_comment', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['parent_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('post')->get_posts_by_ids('question', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['question_info'] = $parents[$val['parent_id']];
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

		$list = $this->fetch_page('question_discussion', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['parent_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('post')->get_posts_by_ids('question_reply', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['answer_info'] = $parents[$val['parent_id']];
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

		$list = $this->fetch_page('question_reply', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['parent_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('post')->get_posts_by_ids('question', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['question_info'] = $parents[$val['parent_id']];
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
		if (!$item_info = $this->model('post')->get_thread_info_by_id('question', $id))
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
		if (!$item_info = $this->model('post')->get_thread_info_by_id('question', $id))
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
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('question_reply', $id))
		{
			return false;
		}

		$this->update('question_reply', array(
			'message' => htmlspecialchars($message)
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('question', $reply_info['parent_id'], 'question_reply', $id, '编辑', $log_uid);

		return true;
	}


	public function clear_answer($id, $log_uid)
	{
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('question_reply', $id))
		{
			return false;
		}

		$this->update('question_reply', array(
			'message' => null,
			'fold' => 1
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('question', $reply_info['parent_id'], 'question_reply', $id, '删除', $log_uid);

		return true;
	}

	public function clear_question_discussion($comment, $log_uid)
	{
		$this->update('question_comment', array(
			'message' => null
		), ['id', 'eq', $comment['id'], 'i']);

		$this->model('content')->log('question', $comment['parent_id'], 'question_comment', $comment['id'], '删除', $log_uid);

		return true;
	}

	public function clear_answer_discussion($comment, $log_uid)
	{
		$this->update('question_discussion', array(
			'message' => null
		), ['id', 'eq', $comment['id'], 'i']);

		if ($answer = $this->fetch_row('question_reply', ['id', 'eq', $comment['parent_id'], 'i']))
		{
			$this->model('content')->log('question', $answer['parent_id'], 'question_discussion', $comment['id'], '删除', $log_uid);
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
		if ($item = $this->fetch_row('question_reply', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_answers($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		$where = ['parent_id', 'in', $thread_ids, 'i'];

		if ($list = $this->fetch_page('question_reply', $where, $order, $page, $per_page))
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
		if ($item = $this->fetch_row('question_comment', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}
		return $item;
	}

	// 同时获取用户信息
	public function get_question_discussions($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		$where = ['parent_id', 'in', $thread_ids, 'i'];

		if ($list = $this->fetch_page('question_comment', $where, $order, $page, $per_page))
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
		if ($item = $this->fetch_row('question_discussion', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}
		return $item;
	}

	// 同时获取用户信息
	public function get_answer_discussions($parent_id, $page, $per_page, $order = 'id ASC')
	{
		$where = ['parent_id', 'eq', $parent_id, 'i'];

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


	public function get_answer_users_by_question_id($question_id, $limit, $question_uid)
	{
		$cache_key = 'answer_users_by_question_id_' . md5($question_id . $limit . $question_uid);
		if ($result = AWS_APP::cache()->get($cache_key))
		{
			return $result;
		}

		$answer_uids = $this->fetch_column('question_reply', 'uid', [['parent_id', 'eq', $question_id, 'i'], ['uid', 'notEq', $question_uid, 'i']], 'agree_count DESC', $limit);
		if ($answer_uids)
		{
			$result = $this->model('account')->get_user_info_by_uids($answer_uids);

			AWS_APP::cache()->set($cache_key, $result, S::get('cache_level_normal'));
		}

		return $result;
	}

}
