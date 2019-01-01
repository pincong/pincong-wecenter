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

class publish_class extends AWS_MODEL
{
	public function publish_answer($question_id, $answer_content, $uid, $anonymous = null, $attach_access_key = null, $auto_focus = true)
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			return false;
		}

		if (!$answer_id = $this->model('answer')->save_answer($question_id, $answer_content, $uid, $anonymous))
		{
			return false;
		}

		if ($at_users = $this->model('question')->parse_at_user($answer_content, false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $uid)
				{
					$this->model('notify')->send($uid, $user_id, notify_class::TYPE_ANSWER_AT_ME, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
						'from_uid' => $uid,
						'question_id' => $question_info['question_id'],
						'item_id' => $answer_id,
						'anonymous' => intval($anonymous)
					));
				}
			}
		}

        set_repeat_submission_digest($answer_content);

		set_human_valid('answer_valid_hour');

		if ($auto_focus)
		{
			if (! $this->model('question')->has_focus_question($question_id, $uid))
			{
				$this->model('question')->add_focus_question($question_id, $uid, $anonymous, false);
			}
		}

		ACTION_LOG::save_action($uid, $answer_id, ACTION_LOG::CATEGORY_ANSWER, ACTION_LOG::ANSWER_QUESTION, $answer_content, $question_id);

		ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ANSWER_QUESTION, $answer_content, $answer_id, null, intval($anonymous));

		if ($question_info['published_uid'] != $uid AND !$this->model('currency')->fetch_log($uid, 'ANSWER_QUESTION', $question_id))
		{
			$this->model('currency')->process($uid, 'ANSWER_QUESTION', get_setting('currency_system_config_answer_question'), '回答问题 #' . $question_id, $question_id);

			$this->model('currency')->process($question_info['published_uid'], 'QUESTION_ANSWER', get_setting('currency_system_config_question_answered'), '问题被回答 #' . $question_id, $question_id);
		}

		$this->model('question')->save_last_answer($question_id, $answer_id);

		if ($focus_uids = $this->model('question')->get_focus_uid_by_question_id($question_id))
		{
			foreach ($focus_uids as $focus_user)
			{
				if ($focus_user['uid'] != $uid)
				{
					$this->model('notify')->send($uid, $focus_user['uid'], notify_class::TYPE_NEW_ANSWER, notify_class::CATEGORY_QUESTION, $question_id, array(
						'question_id' => $question_id,
						'from_uid' => $uid,
						'item_id' => $answer_id,
						'anonymous' => intval($anonymous)
					));
				}
			}
		}

		// 删除回复邀请
		$this->model('question')->answer_question_invite($question_id, $uid);

		$this->model('posts')->set_posts_index($question_id, 'question');

		return $answer_id;
	}

	public function publish_question($question_content, $question_detail, $category_id, $uid, $topics = null, $anonymous = null, $attach_access_key = null, $ask_user_id = null, $create_topic = true, $from = null, $later = null)
	{
		if ($question_id = $this->model('question')->save_question($question_content, $question_detail, $uid, $anonymous, $later, $from))
		{

            set_repeat_submission_digest($question_content);

			set_human_valid('question_valid_hour');

			if ($category_id)
			{
				$this->update('question', array(
					'category_id' => intval($category_id)
				), 'question_id = ' . intval($question_id));
			}

			if (is_array($topics))
			{
				foreach ($topics AS $topic_title)
				{
					$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);

					$this->model('topic')->save_topic_relation($uid, $topic_id, $question_id, 'question');
				}
			}

			if ($ask_user_id)
			{
				$this->model('question')->add_invite($question_id, $uid, $ask_user_id);

				$this->model('notify')->send($uid, $ask_user_id, notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, $question_id, array(
					'from_uid' => $uid,
					'question_id' => $question_id,
				));

				$user_info = $this->model('account')->get_user_info_by_uid($uid);

			}

			// 自动关注该问题
			$this->model('question')->add_focus_question($question_id, $uid, $anonymous, false);

			//TODO: 延迟显示
            //if (intval($later)) {
            //    $anonymous = 1;
            //}

			// 记录日志
			ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_QUESTION, $question_content, $question_detail, null, intval($anonymous));

			$this->model('currency')->process($uid, 'NEW_QUESTION', get_setting('currency_system_config_new_question'), '发起问题 #' . $question_id, $question_id);

			$this->model('posts')->set_posts_index($question_id, 'question');

			if ($from AND is_array($from))
			{
				foreach ($from AS $type => $from_id)
				{
					if (!is_digits($from_id))
					{
						continue;
					}

					$this->update($type, array(
						'question_id' => $question_id
					), 'id = ' . $from_id);
				}
			}
		}

		return $question_id;
	}

	public function publish_article($title, $message, $uid, $topics = null, $category_id = null, $attach_access_key = null, $create_topic = true, $anonymous = null, $later = null)
	{
		//TODO: 延迟显示
        //$now = intval($later) ? future_time() : fake_time();
        $now = fake_time();
		if ($article_id = $this->insert('article', array(
			'uid' => intval($uid),
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message),
			'category_id' => intval($category_id),
			'add_time' => $now,
			'update_time' => $now,
			'anonymous' => intval($anonymous)
		)))
		{

            set_repeat_submission_digest($title);

			set_human_valid('question_valid_hour');

			if (is_array($topics))
			{
				foreach ($topics as $key => $topic_title)
				{
					$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $create_topic);

					$this->model('topic')->save_topic_relation($uid, $topic_id, $article_id, 'article');
				}
			}

			$this->model('search_fulltext')->push_index('article', $title, $article_id);

			//TODO: 延迟显示
            //if (intval($later)) {
            //    $anonymous = 1;
            //}

			// 记录日志
			ACTION_LOG::save_action($uid, $article_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_ARTICLE, $title, $message, null, intval($anonymous));

            $this->model('currency')->process($uid, 'NEW_ARTICLE', get_setting('currency_system_config_new_article'), '发起文章 #' . $article_id, $article_id);

			$this->model('posts')->set_posts_index($article_id, 'article');

			$this->shutdown_update('users', array(
				'article_count' => $this->count('article', 'uid = ' . intval($uid))
			), 'uid = ' . intval($uid));
		}

		return $article_id;
	}

	public function publish_article_comment($article_id, $message, $uid, $at_uid = null, $anonymous = null)
	{
		if (!$article_info = $this->model('article')->get_article_info_by_id($article_id))
		{
			return false;
		}
		$article_id = $article_info['id'];
        $now = fake_time();

		$comment_id = $this->insert('article_comments', array(
			'uid' => intval($uid),
			'article_id' => intval($article_id),
			'message' => htmlspecialchars($message),
			'add_time' => $now,
			'at_uid' => intval($at_uid),
			'anonymous' => intval($anonymous)
		));

		$this->update('article', array(
			'comments' => $this->count('article_comments', 'article_id = ' . intval($article_id)),
			'update_time' => $now
		), 'id = ' . intval($article_id));

		if ($at_uid AND $at_uid != $uid)
		{
			$this->model('notify')->send($uid, $at_uid, notify_class::TYPE_ARTICLE_COMMENT_AT_ME, notify_class::CATEGORY_ARTICLE, $article_id, array(
				'from_uid' => $uid,
				'article_id' => $article_id,
				'item_id' => $comment_id,
				'anonymous' => intval($anonymous)
			));
		}

		if ($at_users = $this->model('question')->parse_at_user($message, false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $uid)
				{
					$this->model('notify')->send($uid, $user_id, notify_class::TYPE_ARTICLE_COMMENT_AT_ME, notify_class::CATEGORY_ARTICLE, $article_id, array(
						'from_uid' => $uid,
						'article_id' => $article_id,
						'item_id' => $comment_id,
						'anonymous' => intval($anonymous)
					));
				}
			}
		}

        set_repeat_submission_digest($message);

		set_human_valid('answer_valid_hour');

		if ($article_info['uid'] != $uid)
		{
			$this->model('notify')->send($uid, $article_info['uid'], notify_class::TYPE_ARTICLE_NEW_COMMENT, notify_class::CATEGORY_ARTICLE, $article_id, array(
				'from_uid' => $uid,
				'article_id' => $article_id,
				'item_id' => $comment_id,
				'anonymous' => intval($anonymous)
			));
		}

		ACTION_LOG::save_action($uid, $article_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_COMMENT_ARTICLE, $message, $comment_id, null, intval($anonymous));

		if ($article_info['uid'] != $uid AND !$this->model('currency')->fetch_log($uid, 'COMMENT_ARTICLE', $article_id))
		{
			$this->model('currency')->process($uid, 'COMMENT_ARTICLE', get_setting('currency_system_config_comment_article'), '评论文章 #' . $article_id, $article_id);

			$this->model('currency')->process($article_info['uid'], 'ARTICLE_COMMENTED', get_setting('currency_system_config_article_commented'), '文章被评论 #' . $article_id, $article_id);
		}

		$this->model('posts')->set_posts_index($article_id, 'article');

		return $comment_id;
	}

}
