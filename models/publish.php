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
	// 延迟显示的时间戳
	private function calc_later_time($minutes)
	{
		return real_time() + $minutes * 60 + rand(-30, 30);
	}

	private function save_topics($type, $uid, $item_id, &$topics, $permission_create_topic)
	{
		if (is_array($topics))
		{
			foreach ($topics AS $topic_title)
			{
				$topic_id = $this->model('topic')->save_topic($topic_title, $uid, $permission_create_topic);
				$this->model('topic')->save_topic_relation($uid, $topic_id, $item_id, $type);
			}
		}
	}

	private function publish_scheduled_item(&$val)
	{
		// 暂时用 model('message')->decrypt
		$data = unserialize($this->model('message')->decrypt($val['data']));
		if (!$data OR !$data['uid'])
		{
			return;
		}

		switch ($val['type'])
		{
			case 'question':
				$this->real_publish_question($data, 1);
				break;

			case 'article':
				$this->real_publish_article($data, 1);
				break;

			case 'video':
				$this->real_publish_video($data, 1);
				break;

			case 'answer':
				$this->real_publish_answer($data);
				break;

			case 'article_comment':
				$this->real_publish_article_comment($data);
				break;

			case 'video_comment':
				$this->real_publish_video_comment($data);
				break;

			case 'question_discussion':
				//$this->real_publish_question_discussion($data);
				break;

			case 'answer_discussion':
				//$this->real_publish_answer_discussion($data);
				break;
		}
	}

	// model('crond') 调用
	public function publish_scheduled_posts()
	{
		$now = real_time();
		if ($items = $this->query_all("SELECT * FROM " . $this->get_table('scheduled_posts') . " WHERE time < " . $now))
		{
			foreach ($items as $key => $val)
			{
				$this->publish_scheduled_item($val);
				$this->delete('scheduled_posts', 'id = ' . ($val['id']));
			}
		}
	}

	private function schedule($type, $time, &$data)
	{
		return $this->insert('scheduled_posts', array(
			'type' => $type,
			'time' => $time,
			'uid' => $data['uid'],
			'parent_id' => intval($data['parent_id']),
			// 暂时用 model('message')->encrypt
			'data' => $this->model('message')->encrypt(serialize($data))
		));
	}


	private function real_publish_question(&$data, $view_count = 0)
	{
		$now = fake_time();

		$item_id = $this->insert('question', array(
			'uid' => $data['uid'],
			'title' => htmlspecialchars($data['title']),
			'message' => htmlspecialchars($data['message']),
			'category_id' => $data['category_id'],
			'add_time' => $now,
			'update_time' => $now,
			'view_count' => $view_count,
		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('posts')->set_posts_index($item_id, 'question');

		$this->model('search_fulltext')->push_index('question', $data['title'], $item_id);

		$this->save_topics('question', $data['uid'], $item_id, $data['topics'], $data['permission_create_topic']);

		if ($data['ask_user_id'])
		{
			$this->model('invite')->add_invite($item_id, $data['uid'], $data['ask_user_id']);

			$this->model('notify')->send($data['uid'], $data['ask_user_id'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, $item_id, array(
				'from_uid' => $data['uid'],
				'question_id' => $item_id
			));
		}

		if ($data['auto_focus'])
		{
			$this->model('focus')->add_focus_question($item_id, $data['uid']);
		}

		// 记录用户动态
		$this->model('activity')->log('question', $item_id, '发起了问题', $data['uid']);

		return $item_id;
	}

	private function real_publish_article(&$data, $view_count = 0)
	{
		$now = fake_time();

		$item_id = $this->insert('article', array(
			'uid' => $data['uid'],
			'title' => htmlspecialchars($data['title']),
			'message' => htmlspecialchars($data['message']),
			'category_id' => $data['category_id'],
			'add_time' => $now,
			'update_time' => $now,
			'views' => $view_count,
		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('posts')->set_posts_index($item_id, 'article');

		$this->model('search_fulltext')->push_index('article', $data['title'], $item_id);

		$this->save_topics('article', $data['uid'], $item_id, $data['topics'], $data['permission_create_topic']);

		// 记录用户动态
		$this->model('activity')->log('article', $item_id, '发表了文章', $data['uid']);

		return $item_id;
	}

	private function real_publish_video(&$data, $view_count = 0)
	{
		$now = fake_time();

		$item_id = $this->insert('video', array(
			'uid' => $data['uid'],
			'title' => htmlspecialchars($data['title']),
			'message' => htmlspecialchars($data['message']),
			'category_id' => $data['category_id'],
			'add_time' => $now,
			'update_time' => $now,
			'view_count' => $view_count,
			'source_type' => $data['source_type'],
			'source' => $data['source'],
			'duration' => $data['duration'],
		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('posts')->set_posts_index($item_id, 'video');

		// 搜索相关 暂不实现
		//$this->model('search_fulltext')->push_index('video', $data['title'], $item_id);

		$this->save_topics('video', $data['uid'], $item_id, $data['topics'], $data['permission_create_topic']);

		// 记录用户动态
		$this->model('activity')->log('video', $item_id, '投稿了影片', $data['uid']);

		return $item_id;
	}


	private function real_publish_answer(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('question', $data['parent_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('answer', array(
			'question_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'uid' => $data['uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$this->update('question', array(
			'answer_count' => $this->count('answer', 'question_id = ' . intval($data['parent_id'])),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), 'id = ' . intval($data['parent_id']));

		$this->model('posts')->set_posts_index($data['parent_id'], 'question');


		if ($at_users = $this->model('mention')->parse_at_user($data['message'], false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $data['uid'])
				{
					$this->model('notify')->send($data['uid'], $user_id, notify_class::TYPE_ANSWER_AT_ME, notify_class::CATEGORY_QUESTION, $data['parent_id'], array(
						'from_uid' => $data['uid'],
						'question_id' => $data['parent_id'],
						'item_id' => $item_id
					));
				}
			}
		}

		if ($data['auto_focus'])
		{
			if (!$this->model('focus')->has_focus_question($data['parent_id'], $data['uid']))
			{
				$this->model('focus')->add_focus_question($data['parent_id'], $data['uid']);
			}
		}

		if ($focus_uids = $this->model('focus')->get_focus_uid_by_question_id($data['parent_id']))
		{
			foreach ($focus_uids as $focus_user)
			{
				if ($focus_user['uid'] != $data['uid'])
				{
					$this->model('notify')->send($data['uid'], $focus_user['uid'], notify_class::TYPE_NEW_ANSWER, notify_class::CATEGORY_QUESTION, $data['parent_id'], array(
						'question_id' => $data['parent_id'],
						'from_uid' => $data['uid'],
						'item_id' => $item_id
					));
				}
			}
		}

		// 删除邀请
		$this->model('invite')->answer_question_invite($data['parent_id'], $data['uid']);

		// 记录用户动态
		$this->model('activity')->log('answer', $item_id, '回答了问题', $data['uid']);

		// TODO: 防止匿名回复刷代币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			$this->model('currency')->process($parent_info['uid'], 'QUESTION_REPLIED', get_setting('currency_system_config_question_replied'), '问题收到回应', $data['parent_id'], 'question');
		}
		return $item_id;
	}

	private function real_publish_article_comment(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('article', $data['parent_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('article_comment', array(
			'uid' => $data['uid'],
			'article_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		// TODO: comments 字段改为 comment_count
		$this->update('article', array(
			'comments' => $this->count('article_comment', 'article_id = ' . intval($data['parent_id'])),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), 'id = ' . intval($data['parent_id']));

		$this->model('posts')->set_posts_index($data['parent_id'], 'article');


		if ($data['at_uid'] AND $data['at_uid'] != $data['uid'])
		{
			$this->model('notify')->send($data['uid'], $data['at_uid'], notify_class::TYPE_ARTICLE_COMMENT_AT_ME, notify_class::CATEGORY_ARTICLE, $data['parent_id'], array(
				'from_uid' => $data['uid'],
				'article_id' => $data['parent_id'],
				'item_id' => $item_id
			));
		}

		if ($at_users = $this->model('mention')->parse_at_user($data['message'], false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $data['uid'])
				{
					$this->model('notify')->send($data['uid'], $user_id, notify_class::TYPE_ARTICLE_COMMENT_AT_ME, notify_class::CATEGORY_ARTICLE, $data['parent_id'], array(
						'from_uid' => $data['uid'],
						'article_id' => $data['parent_id'],
						'item_id' => $item_id
					));
				}
			}
		}

		if ($parent_info['uid'] != $data['uid'])
		{
			$this->model('notify')->send($data['uid'], $parent_info['uid'], notify_class::TYPE_ARTICLE_NEW_COMMENT, notify_class::CATEGORY_ARTICLE, $data['parent_id'], array(
				'from_uid' => $data['uid'],
				'article_id' => $data['parent_id'],
				'item_id' => $item_id
			));
		}

		// 记录用户动态
		$this->model('activity')->log('article_comment', $item_id, '评论了文章', $data['uid']);

		// TODO: 防止匿名回复刷代币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			$this->model('currency')->process($parent_info['uid'], 'ARTICLE_REPLIED', get_setting('currency_system_config_article_replied'), '文章收到回应', $data['parent_id'], 'article');
		}
		return $item_id;
	}

	private function real_publish_video_comment(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('video', $data['parent_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('video_comment', array(
			'uid' => $data['uid'],
			'video_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$this->update('video', array(
			'comment_count' => $this->count('video_comment', 'video_id = ' . intval($data['parent_id'])),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), 'id = ' . intval($data['parent_id']));

		$this->model('posts')->set_posts_index($data['parent_id'], 'video');


		if ($data['at_uid'] AND $data['at_uid'] != $data['uid'])
		{
			/*
			// 通知 暂不实现
			$this->model('notify')->send($data['uid'], $data['at_uid'], notify_class::TYPE_VIDEO_COMMENT_AT_ME, notify_class::CATEGORY_VIDEO, $data['parent_id'], array(
				'from_uid' => $data['uid'],
				'video_id' => $data['parent_id'],
				'item_id' => $item_id
			));
			*/
		}

		if ($at_users = $this->model('mention')->parse_at_user($data['message'], false, true))
		{
			foreach ($at_users as $user_id)
			{
				if ($user_id != $data['uid'])
				{
					/*
					// 通知 暂不实现
					$this->model('notify')->send($data['uid'], $user_id, notify_class::TYPE_VIDEO_COMMENT_AT_ME, notify_class::CATEGORY_VIDEO, $data['parent_id'], array(
						'from_uid' => $data['uid'],
						'video_id' => $data['parent_id'],
						'item_id' => $item_id
					));
					*/
				}
			}
		}

		if ($parent_info['uid'] != $data['uid'])
		{
			/*
			// 通知 暂不实现
			$this->model('notify')->send($data['uid'], $parent_info['uid'], notify_class::TYPE_VIDEO_NEW_COMMENT, notify_class::CATEGORY_VIDEO, $data['parent_id'], array(
				'from_uid' => $data['uid'],
				'video_id' => $data['parent_id'],
				'item_id' => $item_id
			));
			*/
		}

		// 记录用户动态
		$this->model('activity')->log('video_comment', $item_id, '评论了影片', $data['uid']);

		// TODO: 防止匿名回复刷代币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			$this->model('currency')->process($parent_info['uid'], 'VIDEO_REPLIED', get_setting('currency_system_config_video_replied'), '影片收到回应', $data['parent_id'], 'video');
		}
		return $item_id;
	}


	public function publish_question($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('question', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_question($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'NEW_QUESTION', get_setting('currency_system_config_new_question'), '发起问题', null, null, $is_anonymous);
		return $item_id;
	}

	public function publish_article($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('article', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_article($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'NEW_ARTICLE', get_setting('currency_system_config_new_article'), '发起文章', null, null, $is_anonymous);
		return $item_id;
	}

	public function publish_video($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('video', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_video($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'NEW_VIDEO', get_setting('currency_system_config_new_video'), '投稿影片', null, null, $is_anonymous);
		return $item_id;
	}


	public function publish_answer($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('answer', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_answer($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'REPLY_QUESTION', get_setting('currency_system_config_reply_question'), '回应问题', $data['parent_id'], 'question', $is_anonymous);
		return $item_id;
	}

	public function publish_article_comment($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('article_comment', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_article_comment($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'REPLY_ARTICLE', get_setting('currency_system_config_reply_article'), '回应文章', $data['parent_id'], 'article', $is_anonymous);
		return $item_id;
	}

	public function publish_video_comment($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('video_comment', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_video_comment($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'REPLY_VIDEO', get_setting('currency_system_config_reply_video'), '回应影片', $data['parent_id'], 'video', $is_anonymous);
		return $item_id;
	}

}
