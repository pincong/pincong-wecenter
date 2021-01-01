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
	private $key;
	public function get_key()
	{
		if (!$this->key)
		{
			$this->key = AWS_APP::crypt()->new_key(G_SECUKEY);
		}
		return $this->key;
	}

	public function encrypt($message)
	{
		if (!$message)
		{
			return '';
		}
		return AWS_APP::crypt()->encode($message, $this->get_key());
	}

	public function decrypt($message)
	{
		if (!$message)
		{
			return '';
		}
		return AWS_APP::crypt()->decode($message, $this->get_key());
	}

	// 延迟显示的时间戳
	private function calc_later_time($minutes)
	{
		return real_time() + $minutes * 60 + rand(-30, 30);
	}

	private function save_topics($thread_type, $thread_id, $topics, $uid)
	{
		if (is_array($topics))
		{
			foreach ($topics AS $topic_title)
			{
				$topic_id = $this->model('topic')->save_topic($topic_title, $uid, true);
				$this->model('topic')->add_thread_topic($thread_type, $thread_id, $topic_id, null);
			}
		}
	}

	private function mention_users($thread_type, $thread_id, $reply_type, $reply_id, $sender_uid, $message)
	{
		if ($mentioned_uids = $this->model('mention')->get_mentioned_uids($message))
		{
			$this->model('notification')->multi_send(
				$sender_uid,
				$mentioned_uids,
				'MENTION_USER',
				$thread_type, $thread_id, $reply_type, $reply_id);
			return count($mentioned_uids);
		}
		return 0;
	}

	private function notify_followers($thread_type, $thread_id, $reply_type, $reply_id, $sender_uid)
	{
		if ($follower_uids = $this->model('postfollow')->get_follower_uids($thread_type, $thread_id))
		{
			$this->model('notification')->multi_send(
				$sender_uid,
				$follower_uids,
				'REPLY_THREAD',
				$thread_type, $thread_id, $reply_type, $reply_id);
		}
	}

	private function notify_user($thread_type, $thread_id, $reply_type, $reply_id, $sender_uid, $recipient_uid)
	{
		$this->model('notification')->send(
			$sender_uid,
			$recipient_uid,
			'REPLY_USER',
			$thread_type, $thread_id, $reply_type, $reply_id);
	}

	private function publish_scheduled_item($val)
	{
		// decrypt
		$data = unserialize_array($this->decrypt($val['data']));
		if (!$data['uid'])
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

			case 'question_reply':
				$this->real_publish_answer($data);
				break;

			case 'article_reply':
				$this->real_publish_article_comment($data);
				break;

			case 'video_reply':
				$this->real_publish_video_comment($data);
				break;

			case 'question_comment':
				$this->real_publish_question_discussion($data);
				break;

			case 'question_discussion':
				$this->real_publish_answer_discussion($data);
				break;
		}
	}

	public function check_user_permission($item_type, $user_info)
	{
		if (!is_array($user_info) OR !is_array($user_info['permission']))
		{
			return false;
		}
		$types = $user_info['permission']['unallowed_post_types'];
		if (!$types)
		{
			return true;
		}
		$types = array_map('trim', explode(',', $types));
		if (in_array($item_type, $types))
		{
			return false;
		}
		return true;
	}

	// model('crond') 调用
	public function publish_scheduled_posts()
	{
		$now = real_time();
		if ($items = $this->fetch_all('scheduled_posts', ['time', 'lt', $now]))
		{
			foreach ($items as $key => $val)
			{
				$this->publish_scheduled_item($val);
				$this->delete('scheduled_posts', ['id', 'eq', $val['id'], 'i']);
			}
		}
	}

	private function schedule($type, $time, $data)
	{
		return $this->insert('scheduled_posts', array(
			'type' => $type,
			'time' => $time,
			'uid' => $data['uid'],
			'parent_id' => intval($data['parent_id']),
			// encrypt
			'data' => $this->encrypt(serialize($data))
		));
	}


	private function real_publish_question($data, $view_count = 0)
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

		$this->model('threadindex')->set_posts_index($item_id, 'question');

		$this->save_topics('question', $item_id, $data['topics'], $data['uid']);

		if ($data['ask_user_id'])
		{
			$this->model('invite')->add_invite($item_id, $data['uid'], $data['ask_user_id']);
		}

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('question', $item_id, $data['uid']);
		}

		if (!$data['permission_inactive_user'])
		{
			$this->mention_users('question', $item_id, null, 0, $data['uid'], $data['message']);
		}

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

		return $item_id;
	}

	private function real_publish_article($data, $view_count = 0)
	{
		$now = fake_time();

		$item_id = $this->insert('article', array(
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

		$this->model('threadindex')->set_posts_index($item_id, 'article');

		$this->save_topics('article', $item_id, $data['topics'], $data['uid']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('article', $item_id, $data['uid']);
		}

		if (!$data['permission_inactive_user'])
		{
			$this->mention_users('article', $item_id, null, 0, $data['uid'], $data['message']);
		}

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

		return $item_id;
	}

	private function real_publish_video($data, $view_count = 0)
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
		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('threadindex')->set_posts_index($item_id, 'video');

		$this->save_topics('video', $item_id, $data['topics'], $data['uid']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('video', $item_id, $data['uid']);
		}

		if (!$data['permission_inactive_user'])
		{
			$this->mention_users('video', $item_id, null, 0, $data['uid'], $data['message']);
		}

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

		return $item_id;
	}


	private function real_publish_answer($data)
	{
		if (!$parent_info = $this->model('post')->get_thread_info_by_id('question', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('post')->has_user_relpied_to_thread('question', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_question_replied'), '问题收到回应', $data['parent_id'], 'question');
			}
		}

		$now = fake_time();

		$item_id = $this->insert('question_reply', array(
			'parent_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'uid' => $data['uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$this->update('question', array(
			'reply_count' => $this->count('question_reply', ['parent_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('threadindex')->set_posts_index($data['parent_id'], 'question');

		if (!$data['permission_inactive_user'])
		{
			$this->mention_users('question', $parent_info['id'], 'question_reply', $item_id, $data['uid'], $data['message']);
			$this->notify_followers('question', $parent_info['id'], 'question_reply', $item_id, $data['uid']);
		}

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('question', $data['parent_id'], $data['uid']);
		}

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);


		// 删除邀请
		$this->model('invite')->answer_question_invite($data['parent_id'], $data['uid']);

		return $item_id;
	}

	private function real_publish_article_comment($data)
	{
		if (!$parent_info = $this->model('post')->get_thread_info_by_id('article', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('post')->has_user_relpied_to_thread('article', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_article_replied'), '文章收到回应', $data['parent_id'], 'article');
			}
		}

		$now = fake_time();

		$item_id = $this->insert('article_reply', array(
			'uid' => $data['uid'],
			'parent_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$this->update('article', array(
			'reply_count' => $this->count('article_reply', ['parent_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('threadindex')->set_posts_index($data['parent_id'], 'article');

		if (!$data['permission_inactive_user'])
		{
			$this->mention_users('article', $parent_info['id'], 'article_reply', $item_id, $data['uid'], $data['message']);
			if ($data['at_uid'])
			{
				$this->notify_user('article', $parent_info['id'], 'article_reply', $item_id, $data['uid'], $data['at_uid']);
			}
			else
			{
				$this->notify_followers('article', $parent_info['id'], 'article_reply', $item_id, $data['uid']);
			}
		}

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('article', $data['parent_id'], $data['uid']);
		}

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

		return $item_id;
	}

	private function real_publish_video_comment($data)
	{
		if (!$parent_info = $this->model('post')->get_thread_info_by_id('video', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('post')->has_user_relpied_to_thread('video', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_video_replied'), '影片收到回应', $data['parent_id'], 'video');
			}
		}

		$now = fake_time();

		$item_id = $this->insert('video_reply', array(
			'uid' => $data['uid'],
			'parent_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$this->update('video', array(
			'reply_count' => $this->count('video_reply', ['parent_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('threadindex')->set_posts_index($data['parent_id'], 'video');

		if (!$data['permission_inactive_user'])
		{
			$this->mention_users('video', $parent_info['id'], 'video_reply', $item_id, $data['uid'], $data['message']);
			if ($data['at_uid'])
			{
				$this->notify_user('video', $parent_info['id'], 'video_reply', $item_id, $data['uid'], $data['at_uid']);
			}
			else
			{
				$this->notify_followers('video', $parent_info['id'], 'video_reply', $item_id, $data['uid']);
			}
		}

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('video', $data['parent_id'], $data['uid']);
		}

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

		return $item_id;
	}



	private function real_publish_question_discussion($data)
	{
		if (!$thread_info = $this->model('post')->get_thread_info_by_id('question', $data['parent_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('question_comment', array(
			'parent_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'uid' => $data['uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$discussion_count = $this->count('question_comment', ['parent_id', 'eq', $data['parent_id'], 'i']);

		// 被合并的主题已锁, 不可讨论, 无需 $thread_info['redirect_id']

		$this->update('question', array(
			'comment_count' => $discussion_count,
			'update_time' => $now,
			'last_uid' => $data['uid'],
		), ['id', 'eq', $thread_info['id'], 'i']);

		if (S::get('discussion_bring_top') == 'Y')
		{
			$this->model('threadindex')->bring_to_top($thread_info['id'], 'question');
		}

		if (!$data['permission_inactive_user'])
		{
			if (!$this->mention_users('question', $thread_info['id'], null, 0, $data['uid'], $data['message']))
			{
				$this->notify_user('question', $thread_info['id'], null, 0, $data['uid'], $thread_info['uid']);
			}
		}

		// TODO: 记录用户动态

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

		return $item_id;
	}


	private function real_publish_answer_discussion($data)
	{
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('question_reply', $data['parent_id']))
		{
			return false;
		}
		if (!$thread_info = $this->model('post')->get_thread_info_by_id('question', $reply_info['parent_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('question_discussion', array(
			'parent_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'uid' => $data['uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$discussion_count = $this->count('question_discussion', ['parent_id', 'eq', $data['parent_id'], 'i']);

		// 被合并的主题已锁, 但楼中楼仍可讨论
		$thread_id = ($thread_info['redirect_id'] ? $thread_info['redirect_id'] : $thread_info['id']);

		$this->update('question', array(
			'update_time' => $now,
			'last_uid' => $data['uid'],
		), ['id', 'eq', $thread_id, 'i']);

		if (S::get('discussion_bring_top') == 'Y')
		{
			$this->model('threadindex')->bring_to_top($thread_id, 'question');
		}

		$this->update('question_reply', array(
			'comment_count' => $discussion_count,
		), ['id', 'eq', $data['parent_id'], 'i']);

		if (!$data['permission_inactive_user'])
		{
			if (!$this->mention_users('question', $thread_info['id'], 'question_reply', $reply_info['id'], $data['uid'], $data['message']))
			{
				$this->notify_user('question', $thread_info['id'], 'question_reply', $reply_info['id'], $data['uid'], $reply_info['uid']);
			}
		}

		// TODO: 记录用户动态

		$this->model('account')->update_user_fields(array(
			'user_update_time' => $now
		), $data['uid']);

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
		$this->model('currency')->process($real_uid, 'NEW_THREAD', S::get('currency_system_config_new_question'), '发布问题', null, null, $is_anonymous);
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
		$this->model('currency')->process($real_uid, 'NEW_THREAD', S::get('currency_system_config_new_article'), '发布文章', null, null, $is_anonymous);
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
		$this->model('currency')->process($real_uid, 'NEW_THREAD', S::get('currency_system_config_new_video'), '发布影片', null, null, $is_anonymous);
		return $item_id;
	}


	public function publish_answer($data, $real_uid, $later, $pay)
	{
		if ($later)
		{
			$this->schedule('question_reply', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_answer($data);
		}

		if ($pay)
		{
			$is_anonymous = ($real_uid != $data['uid']);
			$this->model('currency')->process($real_uid, 'REPLY', S::get('currency_system_config_reply_question'), '回应问题', $data['parent_id'], 'question', $is_anonymous);
		}
		return $item_id;
	}

	public function publish_article_comment($data, $real_uid, $later, $pay)
	{
		if ($later)
		{
			$this->schedule('article_reply', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_article_comment($data);
		}

		if ($pay)
		{
			$is_anonymous = ($real_uid != $data['uid']);
			$this->model('currency')->process($real_uid, 'REPLY', S::get('currency_system_config_reply_article'), '回应文章', $data['parent_id'], 'article', $is_anonymous);
		}
		return $item_id;
	}

	public function publish_video_comment($data, $real_uid, $later, $pay)
	{
		if ($later)
		{
			$this->schedule('video_reply', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_video_comment($data);
		}

		if ($pay)
		{
			$is_anonymous = ($real_uid != $data['uid']);
			$this->model('currency')->process($real_uid, 'REPLY', S::get('currency_system_config_reply_video'), '回应影片', $data['parent_id'], 'video', $is_anonymous);
		}
		return $item_id;
	}



	public function publish_question_discussion($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('question_comment', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_question_discussion($data);
		}

		return $item_id;
	}

	public function publish_answer_discussion($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('question_discussion', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_answer_discussion($data);
		}

		return $item_id;
	}
}
