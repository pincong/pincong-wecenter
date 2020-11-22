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

class user_class extends AWS_MODEL
{
	private function update_question_discussion_count($question_id)
	{
		$question_id = intval($question_id);
		$this->update('question', array(
			'comment_count' => $this->count('question_discussion', 'question_id = ' . ($question_id))
		), 'id = ' . ($question_id));
	}

	private function update_answer_discussion_count($answer_id)
	{
		$answer_id = intval($answer_id);
		$this->update('answer', array(
			'comment_count' => $this->count('answer_discussion', "answer_id = " . ($answer_id))
		), "id = " . ($answer_id));
	}

	private function update_answer_count($question_id)
	{
		$question_id = intval($question_id);
		$this->update('question', array(
			'answer_count' => $this->count('answer', 'question_id = ' . ($question_id))
		), 'id = ' . ($question_id));
	}

	private function update_article_comment_count($article_id)
	{
		$article_id = intval($article_id);
		// TODO: rename comments to comment_count
		$this->update('article', array(
			'comments' => $this->count('article_comment', 'article_id = ' . ($article_id))
		), 'id = ' . ($article_id));
	}

	private function update_video_comment_count($video_id)
	{
		$video_id = intval($video_id);
		$this->update('video', array(
			'comment_count' => $this->count('video_comment', 'video_id = ' . ($video_id))
		), 'id = ' . ($video_id));
	}
    
    private function update_voting_comment_count($voting_id)
	{
		$voting_id = intval($voting_id);
		// TODO: rename comments to comment_count
		$this->update('voting', array(
			'comments' => $this->count('voting_comment', 'voting_id = ' . ($voting_id))
		), 'id = ' . ($voting_id));
	}


	public function delete_question_discussions($uid)
	{
		$discussions = $this->fetch_all('question_discussion', 'uid = ' . intval($uid));
		if (!$discussions)
		{
			return;
		}

		$this->delete('question_discussion', 'uid = ' . intval($uid));

		foreach ($discussions AS $key => $val)
		{
			$question_ids[$val['question_id']] = $val['question_id'];
		}

		if ($question_ids)
		{
			foreach ($question_ids AS $key => $val)
			{
				$this->update_question_discussion_count($key);
			}
		}
	}

	public function delete_answer_discussions($uid)
	{
		$discussions = $this->fetch_all('answer_discussion', 'uid = ' . intval($uid));
		if (!$discussions)
		{
			return;
		}

		$this->delete('answer_discussion', 'uid = ' . intval($uid));

		foreach ($discussions AS $key => $val)
		{
			$answer_ids[$val['answer_id']] = $val['answer_id'];
		}

		if ($answer_ids)
		{
			foreach ($answer_ids AS $key => $val)
			{
				$this->update_answer_discussion_count($key);
			}
		}
	}


	public function delete_answers($uid)
	{
		$answers = $this->fetch_all('answer', 'uid = ' . intval($uid));
		if (!$answers)
		{
			return;
		}

		$this->delete('answer', 'uid = ' . intval($uid));

		foreach ($answers AS $key => $val)
		{
			$question_ids[$val['question_id']] = $val['question_id'];

			$this->delete('answer_discussion', 'answer_id = ' . $val['id']);
		}

		if ($question_ids)
		{
			foreach ($question_ids AS $key => $val)
			{
				$this->update_answer_count($key);
			}

			$this->update('question', array('last_uid' => '-1'), 'last_uid = ' . intval($uid));
		}
	}


	public function delete_article_comments($uid)
	{
		$article_comments = $this->fetch_all('article_comment', 'uid = ' . intval($uid));
		if (!$article_comments)
		{
			return;
		}

		$this->delete('article_comment', 'uid = ' . intval($uid));

		foreach ($article_comments AS $key => $val)
		{
			$article_ids[$val['article_id']] = $val['article_id'];
		}

		if ($article_ids)
		{
			foreach ($article_ids AS $key => $val)
			{
				$this->update_article_comment_count($key);
			}

			$this->update('article', array('last_uid' => '-1'), 'last_uid = ' . intval($uid));
		}

		$this->update('article_comment', array('at_uid' => '-1'), 'at_uid = ' . intval($uid));
	}


	public function delete_video_comments($uid)
	{
		$video_comments = $this->fetch_all('video_comment', 'uid = ' . intval($uid));
		if (!$video_comments)
		{
			return;
		}

		$this->delete('video_comment', 'uid = ' . intval($uid));

		foreach ($video_comments AS $key => $val)
		{
			$video_ids[$val['video_id']] = $val['video_id'];
		}

		if ($video_ids)
		{
			foreach ($video_ids AS $key => $val)
			{
				$this->update_video_comment_count($key);
			}

			$this->update('video', array('last_uid' => '-1'), 'last_uid = ' . intval($uid));
		}

		$this->update('video_comment', array('at_uid' => '-1'), 'at_uid = ' . intval($uid));
	}
    
    public function delete_voting_comments($uid)
	{
		$voting_comments = $this->fetch_all('voting_comment', 'uid = ' . intval($uid));
		if (!$voting_comments)
		{
			return;
		}

		$this->delete('voting_comment', 'uid = ' . intval($uid));

		foreach ($voting_comments AS $key => $val)
		{
			$voting_ids[$val['voting_id']] = $val['voting_id'];
		}

		if ($voting_ids)
		{
			foreach ($voting_ids AS $key => $val)
			{
				$this->update_voting_comment_count($key);
			}

			$this->update('voting', array('last_uid' => '-1'), 'last_uid = ' . intval($uid));
		}

		$this->update('voting_comment', array('at_uid' => '-1'), 'at_uid = ' . intval($uid));
	}


	public function delete_questions($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('question', array(
			'uid' => '-1'
		), 'uid = ' . intval($uid));
	}


	public function delete_articles($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('article', array(
			'uid' => '-1'
		), 'uid = ' . intval($uid));
	}


	public function delete_videos($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('video', array(
			'uid' => '-1'
		), 'uid = ' . intval($uid));
	}
    
    public function delete_votings($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('voting', array(
			'uid' => '-1'
		), 'uid = ' . intval($uid));
	}


	public function delete_posts_index($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('posts_index', array(
			'uid' => '-1'
		), 'uid = ' . intval($uid));
	}


	public function delete_private_messages($uid)
	{
		if ($dialogues = $this->fetch_all('inbox_dialog', 'recipient_uid = ' . intval($uid) . ' OR sender_uid = ' . intval($uid)))
		{
			foreach ($dialogues AS $key => $val)
			{
				$this->delete('inbox', 'dialog_id = ' . $val['id']);
				$this->delete('inbox_dialog', 'id = ' . $val['id']);
			}
		}
	}


	// 删除用户发表过的内容
	public function delete_user_contents($uid)
	{
		$uid = intval($uid);

		$this->delete('favorite', 'uid = ' . ($uid));
		$this->delete('post_follow', 'uid = ' . ($uid));
		$this->delete('topic_focus', 'uid = ' . ($uid));

		$this->delete('question_invite', 'sender_uid = ' . ($uid) . ' OR recipients_uid = ' . ($uid));
		$this->delete('user_follow', 'fans_uid = ' . ($uid) . ' OR friend_uid = ' . ($uid));

		$this->update('topic_merge', array('uid' => '-1'), 'uid = ' . ($uid));
		$this->update('topic_relation', array('uid' => '-1'), 'uid = ' . ($uid));

		$this->delete('notification', 'sender_uid = ' . ($uid) . ' OR recipient_uid = ' . ($uid));

		$this->delete_private_messages($uid);

		$this->delete_posts_index($uid);

		// 注意顺序 自上而下、从外到内
		$this->delete_questions($uid);
		$this->delete_question_discussions($uid);
		$this->delete_answers($uid);
		$this->delete_answer_discussions($uid);

		$this->delete_articles($uid);
		$this->delete_article_comments($uid);

		$this->delete_videos($uid);
		$this->delete_video_comments($uid);
        
        $this->delete_votings($uid);
		$this->delete_voting_comments($uid);
	}

	public function delete_user_by_uid($uid, $delete_user_contents = false)
	{
		if ($delete_user_contents)
		{
			$this->delete_user_contents($uid);
		}

		$uid = intval($uid);

		$this->delete('users', 'uid = ' . ($uid));
	}


	// ===== 封禁/标记相关 =====

	public function forbid_user_by_uid($uid, $status, $admin_uid = null, $reason = null, $detail = null)
	{
		if (!$uid)
		{
			return false;
		}

		$status = intval($status);
		$this->model('account')->update_user_fields(array(
			'forbidden' => ($status),
			'user_update_time' => fake_time()
		), $uid);

		if (!$status)
		{
			$extra_data = array(
				'banned_by' => null,
				'banned_reason' => null,
				'banned_detail' => null,
			);
		}
		else
		{
			if ($reason)
			{
				$reason = htmlspecialchars($reason);
			}
			if ($detail)
			{
				$detail = htmlspecialchars($detail);
			}
			$extra_data = array(
				'banned_by' => $admin_uid,
				'banned_reason' => $reason,
				'banned_detail' => $detail,
			);
		}

		$this->model('account')->update_user_extra_data($extra_data, $uid);
	}

	public function flag_user_by_uid($uid, $status, $admin_uid = null, $reason = null, $detail = null)
	{
		if (!$uid)
		{
			return false;
		}

		$status = intval($status);
		$this->model('account')->update_user_fields(array(
			'flagged' => ($status),
			'user_update_time' => fake_time()
		), $uid);

		if (!$status)
		{
			$extra_data = array(
				'flagged_by' => null,
				'flagged_reason' => null,
				'flagged_detail' => null,
			);
		}
		else
		{
			if ($reason)
			{
				$reason = htmlspecialchars($reason);
			}
			if ($detail)
			{
				$detail = htmlspecialchars($detail);
			}
			$extra_data = array(
				'flagged_by' => $admin_uid,
				'flagged_reason' => $reason,
				'flagged_detail' => $detail,
			);
		}

		$this->model('account')->update_user_extra_data($extra_data, $uid);
	}

	/**
	 *
	 * 取得管理记录
	 *
	 * @param int     $uid        被操作者UID
	 * @param int     $admin_uid  操作者UID
	 * @param string  $type       操作类型
	 * @param string  $status     状态 (封禁/标记/取消)
	 * @param int     $page
	 * @param int     $per_page
	 *
	 * @return array
	 */
	public function list_admin_logs($uid, $admin_uid, $type, $status, $page, $per_page)
	{
		$valid_types = array(
			"flag_user",
			"forbid_user",
			"change_group",
			"edit_title",
			"edit_signature",
			"add_blocked",
		);

		if ($type AND !in_array($type, $valid_types))
		{
			return false;
		}

		$where = array();
		if ($type)
		{
			$where[] = "`type` = '" . ($type) . "'";
		}
		if (intval($uid))
		{
			$where[] = "uid = " . intval($uid);
		}
		if (intval($admin_uid))
		{
			$where[] = "admin_uid = " . intval($admin_uid);
		}

		if ($status != '')
		{
			$status = explode(',', $status);
			if (is_array($status))
			{
				array_walk_recursive($status, 'intval_string');
				$where[] = '`status` IN(' . implode(',', $status) . ')';
			}
		}

		$log_list = $this->fetch_page('admin_log', implode(' AND ', $where), 'id DESC', $page, $per_page);
		if (!$log_list)
		{
			return false;
		}

		foreach ($log_list AS $key => $log)
		{
			$user_ids[] = $log['uid'];
			$user_ids[] = $log['admin_uid'];
		}

		if ($user_ids)
		{
			$users = $this->model('account')->get_user_info_by_uids($user_ids);
		}
		else
		{
			$users = array();
		}

		foreach ($log_list as $key => $log)
		{
			$log_list[$key]['user_info'] = $users[$log['uid']];
			$log_list[$key]['admin_user_info'] = $users[$log['admin_uid']];
		}
		return $log_list;
	}

	public function insert_admin_log($uid, $admin_uid, $type, $status, $detail)
	{
		$this->model('user')->insert('admin_log', array(
			'uid' => intval($uid),
			'admin_uid' => intval($admin_uid),
			'type' => $type,
			'status' => intval($status),
			'detail' => htmlspecialchars($detail),
			'add_time' => fake_time()
		));
	}
}