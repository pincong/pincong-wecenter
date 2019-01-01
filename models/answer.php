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
			if (-$answer['agree_count'] >= get_setting('answer_downvote_fold'))
			{
				$answer['fold'] = 1;
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
			$answer_downvote_fold = get_setting('answer_downvote_fold');
			foreach ($answers AS $key => $val)
			{
				if (-$val['agree_count'] >= $answer_downvote_fold)
				{
					$val['fold'] = 1;
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
			$answer_downvote_fold = get_setting('answer_downvote_fold');
			foreach($answer_list as $key => $val)
			{
				if (-$val['agree_count'] >= $answer_downvote_fold)
				{
					$answer_list[$key]['fold'] = 1;
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

	public function get_vote_user_by_answer_id($answer_id)
	{
		if (!$answer_id)
		{
			return array();
		}

		if ($users = $this->query_all("SELECT vote_uid FROM " . $this->get_table('answer_vote') . " WHERE answer_id = " . intval($answer_id) . " AND vote_value = 1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['vote_uid']] = $vote_users_info[$val['vote_uid']]['user_name'];
			}
		}

		return $data;
	}

	public function get_vote_user_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return array();
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($users = $this->query_all("SELECT vote_uid, answer_id FROM " . $this->get_table('answer_vote') . " WHERE answer_id IN(" . implode(',', $answer_ids) . ") AND vote_value = 1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['answer_id']][$val['vote_uid']] = $vote_users_info[$val['vote_uid']];
			}
		}

		return $data;
	}


	/**
	 *
	 * 根据回复问题ID，得到反对的用户
	 * @param int $answer_id
	 *
	 * @return array
	 */
	public function get_vote_against_user_by_answer_id($answer_id)
	{
		if (!$answer_id)
		{
			return array();
		}

		if ($users = $this->query_all("SELECT vote_uid FROM " . $this->get_table('answer_vote') . " WHERE answer_id = " . intval($answer_id) . " AND vote_value = -1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['vote_uid']] = $vote_users_info[$val['vote_uid']]['user_name'];
			}
		}

		return $data;
	}

	public function get_vote_agree_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($votes = $this->fetch_all('answer_vote', 'answer_id IN(' . implode(',', $answer_ids) . ') AND vote_value = 1'))
		{
			foreach ($votes as $key => $val)
			{
				$data[$val['answer_id']][] = $val;
			}
		}

		return $data;
	}

	public function get_vote_against_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return false;
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($votes = $this->fetch_all('answer_vote', 'answer_id IN(' . implode(',', $answer_ids) . ') AND vote_value = -1'))
		{
			foreach ($votes as $key => $val)
			{
				$data[$val['answer_id']][] = $val;
			}
		}

		return $data;
	}

	/**
	 *
	 * 根据回复问题ID，得到反对的用户
	 * @param int $answer_id
	 *
	 * @return array
	 */
	public function get_vote_against_user_by_answer_ids($answer_ids)
	{
		if (!is_array($answer_ids))
		{
			return array();
		}

		array_walk_recursive($answer_ids, 'intval_string');

		if ($users = $this->query_all("SELECT vote_uid, answer_id FROM " . $this->get_table('answer_vote') . " WHERE answer_id IN(" . implode(',', $answer_ids) . ") AND vote_value = -1"))
		{
			foreach ($users as $key => $val)
			{
				$vote_users_ids[] = $val['vote_uid'];
			}

			$vote_users_info = $this->model('account')->get_user_info_by_uids($vote_users_ids);

			foreach ($users as $key => $val)
			{
				$data[$val['answer_id']][$val['vote_uid']] = $vote_users_info[$val['vote_uid']]['user_name'];
			}
		}

		return $data;
	}

	/**
	 *
	 * 保存问题回复内容
	 */
	public function save_answer($question_id, $answer_content, $uid, $anonymous = 0)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}

        $now = fake_time();

		if (!$answer_id = $this->insert('answer', array(
			'question_id' => $question_info['question_id'],
			'answer_content' => htmlspecialchars($answer_content),
			'add_time' => $now,
			'uid' => intval($uid),
			'category_id' => $question_info['category_id'],
			'anonymous' => intval($anonymous)
		)))
		{
			return false;
		}

		$this->update('question', array(
			'update_time' => $now,
		), 'question_id = ' . intval($question_id));

		$this->model('question')->update_answer_count($question_id);

		$this->shutdown_update('users', array(
			'answer_count' => $this->count('answer', 'uid = ' . intval($uid))
		), 'uid = ' . intval($uid));

		return $answer_id;
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
	 *
	 * 回复投票
	 * @param int $answer_id   //回复id
	 * @param int $question_id //问题ID
	 * @param int $vote_value  //-1反对 1 赞同
	 * @param int $uid         //用户ID
	 * @param int $answer_uid //被投票用户ID
	 *
	 * @return boolean true|false
	 */
	public function change_answer_vote($answer_id, $vote_value, $uid, $reputation_factor, $answer_uid)
	{
		if (!$answer_id)
		{
			return false;
		}

		if (! in_array($vote_value, array(
			- 1,
			1
		)))
		{
			return false;
		}

		$answer_id = intval($answer_id);
		$answer_uid = intval($answer_uid);

		if (!$vote_info = $this->get_answer_vote_status($answer_id, $uid)) //添加记录
		{
			$this->insert('answer_vote', array(
				'answer_id' => $answer_id,
				'vote_uid' => $uid,
				'add_time' => fake_time(),
				'vote_value' => $vote_value,
				'reputation_factor' => $reputation_factor
			));

			if ($vote_value == 1)
			{
				if (!$this->model('currency')->fetch_log($uid, 'AGREE_ANSWER', $answer_id))
				{
					$this->model('currency')->process($uid, 'AGREE_ANSWER', get_setting('currency_system_config_agree_answer'), '赞同回复 #' . $answer_id, $answer_id);
					$this->model('currency')->process($answer_uid, 'ANSWER_AGREED', get_setting('currency_system_config_answer_agreed'), '回复被赞同 #' . $answer_id, $answer_id);
				}
			}
			else if ($vote_value == -1)
			{
				if (!$this->model('currency')->fetch_log($uid, 'DISAGREE_ANSWER', $answer_id))
				{
					$this->model('currency')->process($uid, 'DISAGREE_ANSWER', get_setting('currency_system_config_disagree_answer'), '反对回复 #' . $answer_id, $answer_id);
					$this->model('currency')->process($answer_uid, 'ANSWER_DISAGREED', get_setting('currency_system_config_answer_disagreed'), '回复被反对 #' . $answer_id, $answer_id);
				}
			}

			$add_agree_count = $vote_value;
		}
		else if ($vote_info['vote_value'] == $vote_value) //删除记录
		{
			$this->delete_answer_vote($vote_info['voter_id']);

			$add_agree_count = -$vote_value;
		}
		else //更新记录
		{
			$this->set_answer_vote_status($vote_info['voter_id'], $vote_value);

			$add_agree_count = $vote_value * 2;
		}

		$this->update_vote_count($answer_id, $add_agree_count);

		// 更新作者赞同数和威望
		$this->model('reputation')->increase_agree_count_and_reputation($answer_uid, $add_agree_count, $reputation_factor);

		return true;
	}

	/**
	 * 删除回复投票
	 * Enter description here ...
	 * @param unknown_type $voter_id
	 */
	public function delete_answer_vote($voter_id)
	{
		return $this->delete('answer_vote', "voter_id = " . intval($voter_id));
	}

	public function update_vote_count($answer_id, $delta)
	{
		$this->query('UPDATE ' . $this->get_table('answer') . ' SET agree_count = agree_count + ' . $delta . ' WHERE answer_id = ' . intval($answer_id));
	}

	public function set_answer_vote_status($voter_id, $vote_value)
	{
		return $this->update('answer_vote', array(
			"add_time" => fake_time(),
			"vote_value" => $vote_value
		), "voter_id = " . intval($voter_id));
	}

	public function get_answer_vote_status($answer_id, $uid)
	{
		if (is_array($answer_id))
		{
			if ($result = $this->query_all("SELECT answer_id, vote_value FROM " . get_table('answer_vote') . " WHERE answer_id IN(" . implode(',', $answer_id) . ") AND vote_uid = " . intval($uid)))
			{
				foreach ($result AS $key => $val)
				{
					$vote_status[$val['answer_id']] = $val;
				}
			}

			foreach ($answer_id AS $aid)
			{
				if ($vote_status[$aid])
				{
					$result[$aid] = $vote_status[$aid]['vote_value'];
				}
				else
				{
					$result[$aid] = '0';
				}
			}

			return $result;
		}
		else
		{
			return $this->fetch_row('answer_vote', "answer_id = " . intval($answer_id) . " AND vote_uid = " . intval($uid));
		}
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
			$this->delete('answer_vote', "answer_id = " . intval($answer_id)); // 删除赞同

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

	public function get_answer_agree_users($answer_id)
	{
		if ($agrees = $this->fetch_all('answer_vote', "answer_id = " . intval($answer_id) . " AND vote_value = 1"))
		{
			foreach ($agrees as $key => $val)
			{
				$agree_uids[] = $val['vote_uid'];
			}
		}

		if ($users = $this->model('account')->get_user_info_by_uids($agree_uids))
		{
			foreach ($users as $key => $val)
			{
				$user_infos[$val['uid']] = $val;
			}
		}

		if ($agree_uids)
		{
			foreach ($agree_uids as $key => $val)
			{
				$agree_users[$val] = $user_infos[$val]['user_name'];
			}
		}
		return $agree_users;
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

		$this->model('currency')->process($answer_info['uid'], 'BEST_ANSWER', get_setting('currency_system_config_best_answer'), '问题 #' . $answer_info['question_id'] . ' 最佳回复', $answer_info['answer_id']);

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

	public function calc_best_answer()
	{
		if (!$best_answer_day = intval(get_setting('best_answer_day')))
		{
			return false;
		}

		$start_time = fake_time() - $best_answer_day * 3600 * 24;

		if ($questions = $this->query_all("SELECT question_id FROM " . $this->get_table('question') . " WHERE add_time < " . $start_time . " AND best_answer = 0 AND answer_count > " . get_setting('best_answer_min_count')))
		{
			foreach ($questions AS $key => $val)
			{
				$best_answer = $this->fetch_row('answer', 'question_id = ' . intval($val['question_id']), 'agree_count DESC');

				if ($best_answer['agree_count'] > get_setting('best_agree_min_count'))
				{
					$this->set_best_answer($best_answer['answer_id']);
				}
			}
		}

		return true;
	}
}