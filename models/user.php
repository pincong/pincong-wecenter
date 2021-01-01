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
			'comment_count' => $this->count('question_comment', ['parent_id', 'eq', $question_id])
		), ['id', 'eq', $question_id]);
	}

	private function update_answer_discussion_count($answer_id)
	{
		$answer_id = intval($answer_id);
		$this->update('question_reply', array(
			'comment_count' => $this->count('question_discussion', ['parent_id', 'eq', $answer_id])
		), ['id', 'eq', $answer_id]);
	}

	private function update_answer_count($question_id)
	{
		$question_id = intval($question_id);
		$this->update('question', array(
			'reply_count' => $this->count('question_reply', ['parent_id', 'eq', $question_id])
		), ['id', 'eq', $question_id]);
	}

	private function update_article_comment_count($article_id)
	{
		$article_id = intval($article_id);
		$this->update('article', array(
			'reply_count' => $this->count('article_reply', ['parent_id', 'eq', $article_id])
		), ['id', 'eq', $article_id]);
	}

	private function update_video_comment_count($video_id)
	{
		$video_id = intval($video_id);
		$this->update('video', array(
			'reply_count' => $this->count('video_reply', ['parent_id', 'eq', $video_id])
		), ['id', 'eq', $video_id]);
	}


	public function delete_question_discussions($uid)
	{
		$discussions = $this->fetch_all('question_comment', ['uid', 'eq', $uid, 'i']);
		if (!$discussions)
		{
			return;
		}

		$this->delete('question_comment', ['uid', 'eq', $uid, 'i']);

		foreach ($discussions AS $key => $val)
		{
			$question_ids[$val['parent_id']] = $val['parent_id'];
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
		$discussions = $this->fetch_all('question_discussion', ['uid', 'eq', $uid, 'i']);
		if (!$discussions)
		{
			return;
		}

		$this->delete('question_discussion', ['uid', 'eq', $uid, 'i']);

		foreach ($discussions AS $key => $val)
		{
			$answer_ids[$val['parent_id']] = $val['parent_id'];
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
		$answers = $this->fetch_all('question_reply', ['uid', 'eq', $uid, 'i']);
		if (!$answers)
		{
			return;
		}

		$this->delete('question_reply', ['uid', 'eq', $uid, 'i']);

		foreach ($answers AS $key => $val)
		{
			$question_ids[$val['parent_id']] = $val['parent_id'];

			$this->delete('question_discussion', ['parent_id', 'eq', $val['id'], 'i']);
		}

		if ($question_ids)
		{
			foreach ($question_ids AS $key => $val)
			{
				$this->update_answer_count($key);
			}

			$this->update('question', array('last_uid' => '-1'), ['last_uid', 'eq', $uid, 'i']);
		}
	}


	public function delete_article_comments($uid)
	{
		$article_comments = $this->fetch_all('article_reply', ['uid', 'eq', $uid, 'i']);
		if (!$article_comments)
		{
			return;
		}

		$this->delete('article_reply', ['uid', 'eq', $uid, 'i']);

		foreach ($article_comments AS $key => $val)
		{
			$article_ids[$val['parent_id']] = $val['parent_id'];
		}

		if ($article_ids)
		{
			foreach ($article_ids AS $key => $val)
			{
				$this->update_article_comment_count($key);
			}

			$this->update('article', array('last_uid' => '-1'), ['last_uid', 'eq', $uid, 'i']);
		}

		$this->update('article_reply', array('at_uid' => '-1'), ['at_uid', 'eq', $uid, 'i']);
	}


	public function delete_video_comments($uid)
	{
		$video_comments = $this->fetch_all('video_reply', ['uid', 'eq', $uid, 'i']);
		if (!$video_comments)
		{
			return;
		}

		$this->delete('video_reply', ['uid', 'eq', $uid, 'i']);

		foreach ($video_comments AS $key => $val)
		{
			$video_ids[$val['parent_id']] = $val['parent_id'];
		}

		if ($video_ids)
		{
			foreach ($video_ids AS $key => $val)
			{
				$this->update_video_comment_count($key);
			}

			$this->update('video', array('last_uid' => '-1'), ['last_uid', 'eq', $uid, 'i']);
		}

		$this->update('video_reply', array('at_uid' => '-1'), ['at_uid', 'eq', $uid, 'i']);
	}


	public function delete_questions($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('question', array(
			'uid' => '-1'
		), ['uid', 'eq', $uid, 'i']);
	}


	public function delete_articles($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('article', array(
			'uid' => '-1'
		), ['uid', 'eq', $uid, 'i']);
	}


	public function delete_videos($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('video', array(
			'uid' => '-1'
		), ['uid', 'eq', $uid, 'i']);
	}


	public function delete_posts_index($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('posts_index', array(
			'uid' => '-1'
		), ['uid', 'eq', $uid, 'i']);
	}


	public function delete_private_messages($uid)
	{
		$uid = intval($uid);

		if ($dialogues = $this->fetch_all('pm_conversation', [
			['uid_1', 'eq', $uid],
			'or',
			['uid_2', 'eq', $uid],
			'or',
			['uid_3', 'eq', $uid],
			'or',
			['uid_4', 'eq', $uid],
			'or',
			['uid_5', 'eq', $uid],
		]))
		{
			foreach ($dialogues AS $key => $val)
			{
				$this->delete('pm_message', ['conversation_id', 'eq', $val['id'], 'i']);
				$this->delete('pm_conversation', ['id', 'eq', $val['id'], 'i']);
			}
		}
	}


	// 删除用户发表过的内容
	public function delete_user_contents($uid)
	{
		$uid = intval($uid);

		$this->delete('favorite', ['uid', 'eq', $uid]);
		$this->delete('post_follow', ['uid', 'eq', $uid]);
		$this->delete('topic_focus', ['uid', 'eq', $uid]);

		$this->delete('question_invite', [['sender_uid', 'eq', $uid], 'or', ['recipients_uid', 'eq', $uid]]);

		$this->update('topic_merge', array('uid' => '-1'), ['uid', 'eq', $uid]);
		$this->update('topic_relation', array('uid' => '-1'), ['uid', 'eq', $uid]);

		$this->delete('notification', [['sender_uid', 'eq', $uid], 'or', ['recipient_uid', 'eq', $uid]]);

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
	}

	public function delete_user_by_uid($uid, $delete_user_contents = false)
	{
		if ($delete_user_contents)
		{
			$this->delete_user_contents($uid);
		}

		$uid = intval($uid);

		$this->delete('users', ['uid', 'eq', $uid]);
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
			'mod_time' => fake_time(),
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
			'mod_time' => fake_time(),
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
			"delete_user",
		);

		if ($type AND !in_array($type, $valid_types))
		{
			return false;
		}

		if ($type)
		{
			$where[] = ['type', 'eq', $type];
		}
		if (intval($uid))
		{
			$where[] = ['uid', 'eq', $uid, 'i'];
		}
		if (intval($admin_uid))
		{
			$where[] = ['admin_uid', 'eq', $admin_uid, 'i'];
		}

		if ($status != '')
		{
			$status = explode(',', $status);
			if (is_array($status))
			{
				$where[] = ['status', 'in', $status, 'i'];
			}
		}

		$log_list = $this->fetch_page('admin_log', $where, 'id DESC', $page, $per_page);
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