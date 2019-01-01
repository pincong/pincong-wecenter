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
				$this->model('question')->update_question_discussion_count($key);
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
				$this->model('answer')->update_answer_discussion_count($key);
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

			// TODO: rename column answer_id to id
			$this->delete('answer_discussion', 'answer_id = ' . $val['answer_id']);

			//$this->update('question', array('best_answer' => 0), 'best_answer = ' . $val['answer_id']);
			//$this->update('question', array('last_answer' => 0), 'last_answer = ' . $val['answer_id']);
		}

		if ($question_ids)
		{
			foreach ($question_ids AS $key => $val)
			{
				$this->model('question')->update_answer_count($key);
			}
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
				$this->model('article')->update_article_comment_count($key);
			}
		}

		$this->update('article_comment', array('at_uid' => '-1'), 'at_uid = ' . ($uid));
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
				$this->model('video')->update_video_comment_count($key);
			}
		}

		$this->update('video_comment', array('at_uid' => '-1'), 'at_uid = ' . ($uid));
	}

	public function delete_video_danmakus($uid)
	{
		$video_danmakus = $this->fetch_all('video_danmaku', 'uid = ' . intval($uid));
		if (!$video_danmakus)
		{
			return;
		}

		$this->delete('video_danmaku', 'uid = ' . intval($uid));

		foreach ($video_danmakus AS $key => $val)
		{
			$video_ids[$val['video_id']] = $val['video_id'];
		}

		if ($video_ids)
		{
			foreach ($video_ids AS $key => $val)
			{
				$this->model('video')->update_video_danmaku_count($key);
			}
		}
	}


	public function delete_questions($uid)
	{
		// TODO: 需要彻底删除?
		$this->update('question', array(
			'published_uid' => '-1'
		), 'published_uid = ' . intval($uid));
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
		$this->delete('question_focus', 'uid = ' . ($uid));
		$this->delete('topic_focus', 'uid = ' . ($uid));
		$this->delete('verify_apply', 'uid = ' . ($uid));

		$this->delete('question_invite', 'sender_uid = ' . ($uid) . ' OR recipients_uid = ' . ($uid));
		$this->delete('user_follow', 'fans_uid = ' . ($uid) . ' OR friend_uid = ' . ($uid));

		$this->update('redirect', array('uid' => '-1'), 'uid = ' . ($uid));
		$this->update('topic_merge', array('uid' => '-1'), 'uid = ' . ($uid));
		$this->update('topic_relation', array('uid' => '-1'), 'uid = ' . ($uid));

		$this->model('notify')->delete_notify('sender_uid = ' . ($uid) . ' OR recipient_uid = ' . ($uid));
		ACTION_LOG::delete_action_history('uid = ' . ($uid));

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
		$this->delete_video_danmakus($uid);
	}

	public function delete_user_by_uid($uid)
	{
		$this->delete_user_contents($uid);

		$uid = intval($uid);

		$this->delete('users', 'uid = ' . ($uid));
		$this->delete('users_attrib', 'uid = ' . ($uid));
	}

	public function auto_delete_users()
	{
		$days = intval(get_setting('days_delete_forbidden_users'));
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

		$where = 'forbidden = 1 AND user_update_time < ' . $time_before;
		$users = $this->fetch_all('users', $where);
		if (!$users)
		{
			return;
		}

		foreach ($users AS $key => $val)
		{
			$this->delete_user_by_uid($val['uid']);
		}
	}
}