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
	public function get_answer_by_id($answer_id)
	{
		static $answers;

		if ($answers[$answer_id])
		{
			return $answers[$answer_id];
		}

		if ($answer = $this->fetch_row('answer', 'answer_id = ' . intval($answer_id)))
		{
			if (-$answer['agree_count'] >= get_setting('downvote_fold'))
			{
				$answer['fold'] = 2;
			}

			$answers[$answer_id] = $answer;
		}

		return $answers[$answer_id];
	}

	public function get_answers_by_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		if ($answers = $this->fetch_all('answer', "answer_id IN (" . implode(', ', $answer_ids) . ")"))
		{
			$downvote_fold = get_setting('downvote_fold');
			foreach ($answers AS $key => $val)
			{
				if (-$val['agree_count'] >= $downvote_fold)
				{
					$val['fold'] = 2;
				}

				$result[$val['answer_id']] = $val;
			}
		}

		return $result;
	}

	public function get_answer_count_by_question_id($question_id, $where = null)
	{
		if ($where)
		{
			$where = ' AND ' . $where;
		}

		return $this->count('answer', "question_id = " . intval($question_id) . $where);
	}

	public function get_answer_list_by_question_id($question_id, $limit = 20, $where = null, $order = 'answer_id DESC')
	{
		if ($where)
		{
			$_where = ' AND (' . $where . ')';
		}

		if ($answer_list = $this->fetch_all('answer', 'question_id = ' . intval($question_id) . $_where, $order, $limit))
		{
			$downvote_fold = get_setting('downvote_fold');
			foreach($answer_list as $key => $val)
			{
				if (-$val['agree_count'] >= $downvote_fold)
				{
					$answer_list[$key]['fold'] = 2;
				}

				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			if ($users_info = $this->model('account')->get_user_info_by_uids($uids, true))
			{
				foreach($answer_list as $key => $val)
				{
					$answer_list[$key]['user_info'] = $users_info[$val['uid']];
				}
			}
		}

		return $answer_list;
	}


	/**
	 *
	 * 更新问题回复内容
	 */
	public function update_answer($answer_id, $question_id, $answer_content, $uid, $anonymous = null)
	{
		$answer_id = intval($answer_id);
		$question_id = intval($question_id);

		if (!$answer_id OR !$question_id)
		{
			return false;
		}

		if (!$answer_info = $this->model('answer')->get_answer_by_id($answer_id))
		{
			return false;
		}

		if (isset($answer_content))
		{
			$answer_content = htmlspecialchars($answer_content);
		}

		$data = array(
			'answer_content' => $answer_content
		);

		// 更新问题最后时间
		$this->shutdown_update('question', array(
			'update_time' => fake_time(),
		), 'question_id = ' . intval($question_id));

		$this->update('answer', $data, 'answer_id = ' . intval($answer_id));

		if ($uid == $answer_info['uid'])
		{
			$is_anonymous =  $answer_info['anonymous'];
		}
		$this->model('question')->log($question_id, 'ANSWER', '编辑回复', $uid, $is_anonymous, $answer_id);
		return true;
	}

	public function update_answer_by_id($answer_id, $answer_info)
	{
		return $this->update('answer', $answer_info, 'answer_id = ' . intval($answer_id));
	}


	/**
	 * 删除问题关联的所有回复及相关的内容
	 */
	public function remove_answers_by_question_id($question_id)
	{
		if (!$answers = $this->get_answer_list_by_question_id($question_id))
		{
			return false;
		}

		foreach ($answers as $key => $val)
		{
			$answer_ids[] = $val['answer_id'];
		}

		return $this->remove_answer_by_ids($answer_ids);
	}

	/**
	 * 根据回复集合批量删除回复
	 */
	public function remove_answer_by_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		foreach ($answer_ids as $answer_id)
		{
			$this->remove_answer_by_id($answer_id);
		}

		return true;
	}

	public function remove_answer_by_id($answer_id)
	{
		if ($answer_info = $this->model('answer')->get_answer_by_id($answer_id))
		{
			$this->delete('answer_comments', 'answer_id = ' . intval($answer_id));	// 删除评论

			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_ANSWER . ' AND associate_id = ' . intval($answer_id));
			ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . intval($answer_id));

			$this->delete('answer', "answer_id = " . intval($answer_id));

			$this->model('question')->update_answer_count($answer_info['question_id']);
		}

		return true;
	}

	public function has_answer_by_uid($question_id, $uid)
	{
		return $this->fetch_one('answer', 'answer_id', "question_id = " . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function get_last_answer($question_id)
	{
		return $this->fetch_row('answer', 'question_id = ' . intval($question_id), 'answer_id DESC');
	}

	public function get_last_answer_by_question_ids($question_ids)
	{
		if (!is_array($question_ids) OR sizeof($question_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($question_ids, 'intval_string');

		if ($last_answer_query = $this->query_all("SELECT last_answer FROM " . get_table('question') . " WHERE question_id IN (" . implode(',', $question_ids) . ")"))
		{
			foreach ($last_answer_query AS $key => $val)
			{
				if ($val['last_answer'])
				{
					$last_answer_ids[] = $val['last_answer'];
				}
			}

			if ($last_answer_ids)
			{
				if ($last_answer = $this->fetch_all('answer', "answer_id IN (" . implode(',', $last_answer_ids) . ")"))
				{
					foreach ($last_answer AS $key => $val)
					{
						$result[$val['question_id']] = $val;
					}
				}
			}
		}

		return $result;
	}

	public function update_answer_comments_count($answer_id)
	{
		$count = $this->count('answer_comments', "answer_id = " . intval($answer_id));

		$this->shutdown_update('answer', array(
			'comment_count' => $count
		), "answer_id = " . intval($answer_id));
	}

	public function insert_answer_comment($answer_id, $uid, $message, $anonymous = 0)
	{
		if (!$answer_info = $this->model('answer')->get_answer_by_id($answer_id))
		{
			return false;
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']))
		{
			return false;
		}

		$message = $this->model('question')->parse_at_user($message, false, false, true);

		$comment_id = $this->insert('answer_comments', array(
			'uid' => intval($uid),
			'answer_id' => intval($answer_id),
			'message' => htmlspecialchars($message),
			'anonymous' => intval($anonymous),
			'time' => fake_time()
		));

		if ($answer_info['uid'] != $uid)
		{
			$this->model('notify')->send($uid, $answer_info['uid'], notify_class::TYPE_ANSWER_COMMENT, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
				'from_uid' => $uid,
				'question_id' => $answer_info['question_id'],
				'item_id' => $answer_info['answer_id'],
				'comment_id' => $comment_id,
				'anonymous' => intval($anonymous)
			));

		}

		if ($at_users = $this->model('question')->parse_at_user($message, false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $uid)
				{
					$this->model('notify')->send($uid, $user_id, notify_class::TYPE_ANSWER_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
						'from_uid' => $uid,
						'question_id' => $answer_info['question_id'],
						'item_id' => $answer_info['answer_id'],
						'comment_id' => $comment_id,
						'anonymous' => intval($anonymous)
					));

				}
			}
		}

		$this->update_answer_comments_count($answer_id);

		return $comment_id;
	}

	public function get_answer_comments($answer_id)
	{
		return $this->fetch_all('answer_comments', "answer_id = " . intval($answer_id), "id ASC");
	}

	public function get_comment_by_id($comment_id)
	{
		return $this->fetch_row('answer_comments', "id = " . intval($comment_id));
	}

	/*public function remove_comment($comment_id)
	{
		//return $this->delete('answer_comments', "id = " . intval($comment_id));
	}*/

	public function set_best_answer($answer_id, $uid = null)
	{
		if (!$answer_info = $this->get_answer_by_id($answer_id))
		{
			return false;
		}

		$this->shutdown_update('question', array(
			'best_answer' => $answer_info['answer_id']
		), 'question_id = ' . $answer_info['question_id']);

		if ($uid)
		{
			$this->model('question')->log($answer_info['question_id'], 'ANSWER', '设置最佳回复', $uid, 0, $answer_info['answer_id']);
		}

		return true;
	}

	public function unset_best_answer($answer_id, $uid = null)
	{
		if (!$answer_info = $this->get_answer_by_id($answer_id))
		{
			return false;
		}

		$this->shutdown_update('question', array(
			'best_answer' => 0
		), 'question_id = ' . $answer_info['question_id']);

		if ($uid)
		{
			$this->model('question')->log($answer_info['question_id'], 'ANSWER', '取消最佳回复', $uid, 0, $answer_info['answer_id']);
		}

		return true;
	}

}