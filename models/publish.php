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
	public function approval_publish($id)
	{
		if (!$approval_item = $this->get_approval_item($id))
		{
			return false;
		}

		switch ($approval_item['type'])
		{
			case 'question':
				$question_id = $this->publish_question(
                    $approval_item['data']['question_content'],
                    $approval_item['data']['question_detail'],
                    $approval_item['data']['category_id'],
                    $approval_item['uid'],
                    $approval_item['data']['topics'],
                    $approval_item['data']['anonymous'],
                    $approval_item['data']['attach_access_key'],
                    $approval_item['data']['ask_user_id'],
                    $approval_item['data']['permission_create_topic'],
                    null,
                    $approval_item['data']['later']
                );

				$this->model('notify')->send(0, $approval_item['uid'], notify_class::TYPE_QUESTION_APPROVED, notify_class::CATEGORY_QUESTION, 0, array('question_id' => $question_id));

				break;

			case 'answer':
				$answer_id = $this->publish_answer($approval_item['data']['question_id'], $approval_item['data']['answer_content'], $approval_item['uid'], $approval_item['data']['anonymous'], $approval_item['data']['attach_access_key'], $approval_item['data']['auto_focus']);

				break;

			case 'article':
				$article_id = $this->publish_article(
                    $approval_item['data']['title'],
                    $approval_item['data']['message'],
                    $approval_item['uid'],
                    $approval_item['data']['topics'],
                    $approval_item['data']['category_id'],
                    $approval_item['data']['attach_access_key'],
                    $approval_item['data']['permission_create_topic'],
                    $approval_item['data']['anonymous'],
                    $approval_item['data']['later']
                );

				$this->model('notify')->send(0, $approval_item['uid'], notify_class::TYPE_ARTICLE_APPROVED, notify_class::CATEGORY_ARTICLE, 0, array('article_id' => $article_id));

				break;

			case 'article_comment':
				$article_comment_id = $this->publish_article_comment(
                    $approval_item['data']['article_id'],
                    $approval_item['data']['message'],
                    $approval_item['uid'],
                    $approval_item['data']['at_uid'],
                    $approval_item['data']['anonymous']
                );

				break;
		}

		$this->delete('approval', 'id = ' . intval($id));

		return true;
	}

	public function decline_publish($id)
	{
		$approval_item = $this->get_approval_item($id);

		if (!$approval_item)
		{
			return false;
		}

		switch ($approval_item['type'])
		{
			case 'question':
			case 'answer':
			case 'article':
				$this->delete('approval', 'id = ' . $approval_item['id']);

				if ($approval_item['data']['attach_access_key'])
				{
					if ($attachs = $this->get_attach_by_access_key($approval_item['type'], $approval_item['data']['attach_access_key']))
					{
						foreach ($attachs AS $key => $val)
						{
							$this->remove_attach($val['id'], $val['access_key']);
						}
					}
				}

				break;

			case 'article_comment':
				$this->delete('approval', 'id = ' . $approval_item['id']);

				break;
		}

		switch ($approval_item['type'])
		{
			case 'question':
				$this->model('notify')->send(0, $approval_item['uid'], notify_class::TYPE_QUESTION_REFUSED, notify_class::CATEGORY_QUESTION, 0, array('title' => $approval_item['data']['question_content']));

				break;

			case 'article':
				$this->model('notify')->send(0, $approval_item['uid'], notify_class::TYPE_ARTICLE_REFUSED, notify_class::CATEGORY_ARTICLE, 0, array('title' => $approval_item['data']['title']));

				break;
		}

		return true;
	}

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

		if ($question_info['published_uid'] != $uid AND !$this->model('integral')->fetch_log($uid, 'ANSWER_QUESTION', $question_id))
		{
			$this->model('integral')->process($uid, 'ANSWER_QUESTION', get_setting('integral_system_config_answer_question'), '回答问题 #' . $question_id, $question_id);

			$this->model('integral')->process($question_info['published_uid'], 'QUESTION_ANSWER', get_setting('integral_system_config_question_answered'), '问题被回答 #' . $question_id, $question_id);
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

		if ($attach_access_key)
		{
			$this->model('publish')->update_attach('answer', $answer_id, $attach_access_key);
		}

		$this->model('posts')->set_posts_index($question_id, 'question');

		return $answer_id;
	}

	public function publish_approval($type, $data, $uid, $attach_access_key = null)
	{
		if ($attach_access_key)
		{
			$this->update('attach', array(
				'wait_approval' => 1
			), "access_key = '" . $this->quote($attach_access_key) . "'");
		}

		return $this->insert('approval', array(
			'type' => $type,
			'data' => serialize($data),
			'uid' => intval($uid),
			'time' => fake_time()
		));
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

			if ($attach_access_key)
			{
				{
					$this->model('publish')->update_attach('question', $question_id, $attach_access_key);
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

            if (intval($later)) {
                $anonymous = 1;
            }
			// 记录日志
			ACTION_LOG::save_action($uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_QUESTION, $question_content, $question_detail, null, intval($anonymous));

			$this->model('integral')->process($uid, 'NEW_QUESTION', get_setting('integral_system_config_new_question'), '发起问题 #' . $question_id, $question_id);

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
        $now = intval($later) ? future_time() : fake_time();
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

			if ($attach_access_key)
			{
				$this->model('publish')->update_attach('article', $article_id, $attach_access_key);
			}

			$this->model('search_fulltext')->push_index('article', $title, $article_id);

            if (intval($later)) {
                $anonymous = 1;
            }
			// 记录日志
			ACTION_LOG::save_action($uid, $article_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_ARTICLE, $title, $message, null, intval($anonymous));

            $this->model('integral')->process($uid, 'NEW_ARTICLE', get_setting('integral_system_config_new_article'), '发起文章 #' . $article_id, $article_id);

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

		if ($article_info['uid'] != $uid AND !$this->model('integral')->fetch_log($uid, 'COMMENT_ARTICLE', $article_id))
		{
			$this->model('integral')->process($uid, 'COMMENT_ARTICLE', get_setting('integral_system_config_comment_article'), '评论文章 #' . $article_id, $article_id);

			$this->model('integral')->process($article_info['uid'], 'ARTICLE_COMMENTED', get_setting('integral_system_config_article_commented'), '文章被评论 #' . $article_id, $article_id);
		}

		$this->model('posts')->set_posts_index($article_id, 'article');

		return $comment_id;
	}

	public function update_attach($item_type, $item_id, $attach_access_key)
	{
		if (!is_digits($item_id) OR !$attach_access_key)
		{
			return false;
		}

		$update_result = $this->update('attach', array(
			'item_id' => $item_id
		), "item_type = '" . $this->quote($item_type) . "' AND item_id = 0 AND access_key = '" . $this->quote($attach_access_key) . "'");

		if ($update_result)
		{
			switch ($item_type)
			{
				default:
					$update_key = 'id';

					break;

				case 'question':
				case 'answer':
					$update_key = $item_type . '_id';

					break;

				// Modify by wecenter
				case 'support':
					return true;

					break;
			}

			$this->update($item_type, array(
				'has_attach' => 1
			), $update_key . ' = ' . $item_id);
		}

		return $update_result;
	}

	public function add_attach($item_type, $file_name, $attach_access_key, $add_time, $file_location, $is_image = false)
	{
		if ($is_image)
		{
			$is_image = 1;
		}

		return $this->insert('attach', array(
			'file_name' => htmlspecialchars($file_name),
			'access_key' => $attach_access_key,
			'add_time' => $add_time,
			'file_location' => htmlspecialchars($file_location),
			'is_image' => $is_image,
			'item_type' => $item_type
		));
	}

	public function remove_attach($id, $access_key, $update_associate_table = true)
	{
		if (! $attach = $this->fetch_row('attach', "id = " . intval($id) . " AND access_key = '" . $this->quote($access_key) . "'"))
		{
			return false;
		}

		$this->delete('attach', "id = " . intval($id) . " AND access_key = '" . $this->quote($access_key) . "'");

		if (!$this->fetch_row('attach', 'item_id = ' . $attach['item_id']) AND $update_associate_table)
		{
			switch ($attach['item_type'])
			{
				default:
					$update_key = $attach['item_type'] . '_id';

					break;

				case 'article':
					$update_key = 'id';

					break;
			}

			$this->update($attach['item_type'], array(
				'has_attach' => 0
			), $update_key . ' = ' . $attach['item_id']);
		}

		if (in_array($attach['item_type'], array(
			'question'
		)))
		{
			$attach['item_type'] = 'questions';
		}

		$attach_dir = get_setting('upload_dir') . '/' . $attach['item_type'] . '/' . gmdate('Ymd/', $attach['add_time']);

		foreach(AWS_APP::config()->get('image')->attachment_thumbnail AS $key => $val)
		{
			@unlink($attach_dir . $val['w'] . 'x' . $val['h'] . '_' . $attach['file_location']);
		}

		@unlink($attach_dir . $attach['file_location']);

		return true;
	}

	public function get_attach_by_id($id)
	{
		if ($attach = $this->fetch_row('attach', 'id = ' . intval($id)))
		{
			$data = $this->parse_attach_data(array($attach), $attach['item_type'], 'square');

			return $data[$id];
		}

		return false;
	}

	public function parse_attach_data($attach, $item_type, $size = null)
	{
		if (!$attach OR !$item_type)
		{
			return false;
		}

		foreach ($attach as $key => $data)
		{
			if (in_array($item_type, array(
				'question'
			)))
			{
				$item_type = 'questions';
			}

			// Fix 2.0 attach time zone bug
			$date_dir = gmdate('Ymd', $data['add_time']);

			if (! file_exists(get_setting('upload_dir') . '/' . $item_type . '/' . $date_dir . '/' . $data['file_location']))
			{
				$date_dir = gmdate('Ymd', ($data['add_time'] + 86400));
			}

			if (! file_exists(get_setting('upload_dir') . '/' . $item_type . '/' . $date_dir . '/' . $data['file_location']))
			{
				$date_dir = gmdate('Ymd', ($data['add_time'] - 86400));
			}

			$attach_url = get_setting('upload_url') . '/' . $item_type . '/' . $date_dir . '/';

			$attach_path = get_setting('upload_dir') . '/' . $item_type . '/' . $date_dir . '/';

			$attach_list[$data['id']] = array(
				'id' => $data['id'],
				'is_image' => $data['is_image'],
				'file_name' => $data['file_name'],
				'access_key' => $data['access_key'],
				'file_location' => $data['file_location'],
				'attachment' => $attach_url . $data['file_location'],
				'path' => $attach_path . $data['file_location']
			);

			if ($data['is_image'] == 1 AND $size)
			{
				$attach_list[$data['id']]['thumb'] = $attach_url . AWS_APP::config()->get('image')->attachment_thumbnail[$size]['w'] . 'x' . AWS_APP::config()->get('image')->attachment_thumbnail[$size]['h'] . '_' . $data['file_location'];
			}
		}

		return $attach_list;
	}

	public function get_attach($item_type, $item_id, $size = 'square')
	{
		if (!is_digits($item_id))
		{
			return false;
		}

		$attach = $this->fetch_all('attach', "item_type = '" .  $this->quote($item_type). "' AND item_id = " . $item_id, "is_image DESC, id ASC");

		return $this->parse_attach_data($attach, $item_type, $size);
	}

	public function get_attachs($item_type, $item_ids, $size = 'square')
	{
		if (!is_array($item_ids))
		{
			return false;
		}

		$attach_list = array();

		array_walk_recursive($item_ids, 'intval_string');

		if (!$attachs = $this->fetch_all('attach', "item_type = '" .  $this->quote($item_type). "' AND item_id IN (" . implode(',', $item_ids) . ")", "is_image DESC, id ASC"))
		{
			return false;
		}

		foreach ($attachs AS $key => $val)
		{
			$result[$val['item_id']][] = $val;
		}

		foreach ($result AS $key => $val)
		{
			$result[$key] = $this->parse_attach_data($val, $item_type, $size);
		}

		return $result;
	}

	public function get_attach_by_access_key($item_type, $access_key, $size = 'square')
	{
		$attach = $this->fetch_all('attach', "item_type = '" .  $this->quote($item_type). "' AND access_key = '" . $this->quote($access_key) . "'", "is_image DESC, id ASC");

		return $this->parse_attach_data($attach, $item_type, $size);
	}

	public function get_file_class($file_name)
	{
		switch (strtolower(H::get_file_ext($file_name)))
		{
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'bmp':
			case 'png':
				return 'image';
				break;

			case '3ds' :
				return '3ds';
				break;

			case 'ace' :
			case 'zip' :
			case 'rar' :
			case 'gz' :
			case 'tar' :
			case 'cab' :
			case '7z' :
				return 'zip';
				break;

			case 'ai' :
			case 'psd' :
			case 'cdr' :
				return 'gif';
				break;

			default :
				return 'txt';
				break;
		}
	}

	public function get_approval_list($type, $page, $per_page)
	{
		if ($approval_list = $this->fetch_page('approval', "`type` = '" . $this->quote($type) . "'", 'time ASC', $page, $per_page))
		{
			foreach ($approval_list AS $key => $val)
			{
				$approval_list[$key]['data'] = unserialize($val['data']);
			}
		}

		return $approval_list;
	}

	public function get_approval_item($id)
	{
		if ($approval_item = $this->fetch_row('approval', 'id = ' . intval($id)))
		{
			$approval_item['data'] = unserialize($approval_item['data']);
		}

		return $approval_item;
	}

	public function insert_attach_is_self_upload($message, $attach_ids = null)
	{
		if (!$message)
		{
			return true;
		}

		if (!$attach_ids)
		{
			$attach_ids = array();
		}

		if ($question_attachs_ids = FORMAT::parse_attachs($message, true))
		{
			foreach ($question_attachs_ids AS $attach_id)
			{
				if (!in_array($attach_id, $attach_ids))
				{
					return false;
				}
			}
		}

		return true;
	}
}
