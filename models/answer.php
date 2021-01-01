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

class answer_class extends AWS_MODEL
{
	public function update_answer_discussion_count($answer_id)
	{
		$answer_id = intval($answer_id);
		if (!$answer_id)
		{
			return false;
		}

		return $this->update('answer', array(
			'comment_count' => $this->count('answer_discussion', "answer_id = " . ($answer_id))
		), "id = " . ($answer_id));
	}


	// 同时获取用户信息
	public function get_answer_by_id($answer_id)
	{
		if ($answer = $this->fetch_row('answer', 'id = ' . intval($answer_id)))
		{
			$answer['user_info'] = $this->model('account')->get_user_info_by_uid($answer['uid']);
		}

		return $answer;
	}

	public function get_answers($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		array_walk_recursive($thread_ids, 'intval_string');
		$where = 'question_id IN (' . implode(',', $thread_ids) . ')';

		if ($answer_list = $this->fetch_page('answer', $where, $order, $page, $per_page))
		{
			foreach($answer_list as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			if ($users_info = $this->model('account')->get_user_info_by_uids($uids))
			{
				foreach($answer_list as $key => $val)
				{
					$answer_list[$key]['user_info'] = $users_info[$val['uid']];
				}
			}
		}

		return $answer_list;
	}


	public function has_answer_by_uid($question_id, $uid)
	{
		return $this->fetch_one('answer', 'id', "question_id = " . intval($question_id) . " AND uid = " . intval($uid));
	}


	public function get_answer_discussions($answer_id)
	{
		return $this->fetch_all('answer_discussion', "answer_id = " . intval($answer_id), "id ASC");
	}

	public function get_answer_discussion_by_id($comment_id)
	{
		return $this->fetch_row('answer_discussion', "id = " . intval($comment_id));
	}

}