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

	private function mention_users($thread_type, $thread_id, $reply_type, $reply_id, $sender_uid, $recipient_uid, &$message)
	{
		if ($mentioned_uids = $this->model('mention')->get_mentioned_uids($message))
		{
			foreach ($mentioned_uids as $mentioned_uid)
			{
				$this->model('notification')->send(
					$sender_uid,
					$mentioned_uid,
					'MENTION_USER',
					$thread_type, $thread_id, $reply_type, $reply_id);
			}
		}
	}

	private function notify_users($thread_type, $thread_id, $reply_type, $reply_id, $sender_uid, $recipient_uid, &$message)
	{
		if ($recipient_uid)
		{
			$this->model('notification')->send(
				$sender_uid,
				$recipient_uid,
				'REPLY_USER',
				$thread_type, $thread_id, $reply_type, $reply_id);
			return;
		}

		if ($followers = $this->model('postfollow')->get_followers($thread_type, $thread_id))
		{
			foreach ($followers as $follower)
			{
				$this->model('notification')->send(
					$sender_uid,
					$follower['uid'],
					'REPLY_THREAD',
					$thread_type, $thread_id, $reply_type, $reply_id);
			}
		}

	}

	private function publish_scheduled_item(&$val)
	{
		// 暂时用 model('message')->decrypt
		$data = unserialize_array($this->model('message')->decrypt($val['data']));
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
            case 'voting':
				$this->real_publish_voting($data, 1);
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
            case 'voting_comment':
				$this->real_publish_voting_comment($data);
				break;

			case 'question_discussion':
				$this->real_publish_question_discussion($data);
				break;

			case 'answer_discussion':
				$this->real_publish_answer_discussion($data);
				break;
		}
	}

	public function check_user_permission($item_type, &$user_info)
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
		}

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('question', $item_id, $data['uid']);
		}

		$this->mention_users('question', $item_id, null, 0, $data['uid'], null, $data['message']);

		// 记录用户动态
		$this->model('activity')->push('question', $item_id, $data['uid']);

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
			'view_count' => $view_count,
		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('posts')->set_posts_index($item_id, 'article');

		$this->model('search_fulltext')->push_index('article', $data['title'], $item_id);

		$this->save_topics('article', $data['uid'], $item_id, $data['topics'], $data['permission_create_topic']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('article', $item_id, $data['uid']);
		}

		$this->mention_users('article', $item_id, null, 0, $data['uid'], null, $data['message']);

		// 记录用户动态
		$this->model('activity')->push('article', $item_id, $data['uid']);

		return $item_id;
	}
    
    
    private function real_publish_voting(&$data, $view_count = 0)
	{
		$now = fake_time();
        
        
		$item_id = $this->insert('voting', array(
			'uid' => $data['uid'],
			'title' => htmlspecialchars($data['title']),
			'message' => htmlspecialchars($data['message']),
            'message_count' => htmlspecialchars($data['message_count']),
            'message_vote' => htmlspecialchars($data['mesage_vote']),
			'category_id' => $data['category_id'],
			'add_time' => $now,
			'update_time' => $now,
			'view_count' => $view_count,

		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('posts')->set_posts_index($item_id, 'voting');
        
        
		$this->model('search_fulltext')->push_index('voting', $data['title'], $item_id);

		$this->save_topics('voting', $data['uid'], $item_id, $data['topics'], $data['permission_create_topic']);

		if ($data['ask_user_id'])
		{
			$this->model('invite')->add_invite($item_id, $data['uid'], $data['ask_user_id']);
		}

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('voting', $item_id, $data['uid']);
		}

		$this->mention_users('voting', $item_id, null, 0, $data['uid'], null, $data['message']);

		// 记录用户动态
		$this->model('activity')->push('voting', $item_id, $data['uid']);

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
		));

		if (!$item_id)
		{
			return false;
		}

		$this->model('posts')->set_posts_index($item_id, 'video');

		// 搜索相关 暂不实现
		//$this->model('search_fulltext')->push_index('video', $data['title'], $item_id);

		$this->save_topics('video', $data['uid'], $item_id, $data['topics'], $data['permission_create_topic']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('video', $item_id, $data['uid']);
		}

		$this->mention_users('video', $item_id, null, 0, $data['uid'], null, $data['message']);

		// 记录用户动态
		$this->model('activity')->push('video', $item_id, $data['uid']);

		return $item_id;
	}


	private function real_publish_answer(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('question', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('content')->has_user_relpied_to_thread('question', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_question_replied'), '问题收到回应', $data['parent_id'], 'question');
			}
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
			'answer_count' => $this->count('answer', ['question_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('posts')->set_posts_index($data['parent_id'], 'question');

		$this->mention_users('question', $parent_info['id'], 'answer', $item_id, $data['uid'], null, $data['message']);
		$this->notify_users('question', $parent_info['id'], 'answer', $item_id, $data['uid'], null, $data['message']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('question', $data['parent_id'], $data['uid']);
		}

		// 记录用户动态
		$this->model('activity')->push('answer', $item_id, $data['uid']);


		// 删除邀请
		$this->model('invite')->answer_question_invite($data['parent_id'], $data['uid']);

		return $item_id;
	}

	private function real_publish_article_comment(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('article', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('content')->has_user_relpied_to_thread('article', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_article_replied'), '文章收到回应', $data['parent_id'], 'article');
			}
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
			'comments' => $this->count('article_comment', ['article_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('posts')->set_posts_index($data['parent_id'], 'article');

		$this->mention_users('article', $parent_info['id'], 'article_comment', $item_id, $data['uid'], $data['at_uid'], $data['message']);
		$this->notify_users('article', $parent_info['id'], 'article_comment', $item_id, $data['uid'], $data['at_uid'], $data['message']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('article', $data['parent_id'], $data['uid']);
		}

		// 记录用户动态
		$this->model('activity')->push('article_comment', $item_id, $data['uid']);

		return $item_id;
	}

	private function real_publish_video_comment(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('video', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('content')->has_user_relpied_to_thread('video', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_video_replied'), '影片收到回应', $data['parent_id'], 'video');
			}
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
			'comment_count' => $this->count('video_comment', ['video_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('posts')->set_posts_index($data['parent_id'], 'video');

		$this->mention_users('video', $parent_info['id'], 'video_comment', $item_id, $data['uid'], $data['at_uid'], $data['message']);
		$this->notify_users('video', $parent_info['id'], 'video_comment', $item_id, $data['uid'], $data['at_uid'], $data['message']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('video', $data['parent_id'], $data['uid']);
		}

		// 记录用户动态
		$this->model('activity')->push('video_comment', $item_id, $data['uid']);

		return $item_id;
	}
    
    private function real_publish_voting_comment(&$data)
	{
		if (!$parent_info = $this->model('content')->get_thread_info_by_id('voting', $data['parent_id']))
		{
			return false;
		}

		// 给题主增加游戏币
		if ($data['permission_affect_currency'] AND $data['uid'] != $parent_info['uid'])
		{
			if (!$this->model('content')->has_user_relpied_to_thread('voting', $data['parent_id'], $data['uid']))
			{
				$this->model('currency')->process($parent_info['uid'], 'REPLIED', S::get('currency_system_config_voting_replied'), '文章收到回应', $data['parent_id'], 'voting');
			}
		}

		$now = fake_time();

		$item_id = $this->insert('voting_comment', array(
			'uid' => $data['uid'],
			'voting_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		// TODO: comments 字段改为 comment_count
		$this->update('voting', array(
			'comments' => $this->count('voting_comment', ['voting_id', 'eq', $data['parent_id'], 'i']),
			'update_time' => $now,
			'last_uid' => $data['uid']
		), ['id', 'eq', $data['parent_id'], 'i']);

		$this->model('posts')->set_posts_index($data['parent_id'], 'voting');

		$this->mention_users('voting', $parent_info['id'], 'voting_comment', $item_id, $data['uid'], $data['at_uid'], $data['message']);
		$this->notify_users('voting', $parent_info['id'], 'voting_comment', $item_id, $data['uid'], $data['at_uid'], $data['message']);

		if ($data['follow'])
		{
			$this->model('postfollow')->follow('voting', $data['parent_id'], $data['uid']);
		}

		// 记录用户动态
		$this->model('activity')->push('voting_comment', $item_id, $data['uid']);

		return $item_id;
	}



	private function real_publish_question_discussion(&$data)
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('question', $data['parent_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('question_discussion', array(
			'question_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'uid' => $data['uid'],
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$discussion_count = $this->count('question_discussion', ['question_id', 'eq', $data['parent_id'], 'i']);

		if (S::get('discussion_bring_top') == 'Y')
		{
			$this->update('question', array(
				'comment_count' => $discussion_count,
				'update_time' => $now,
				'last_uid' => $data['uid'],
			), ['id', 'eq', $thread_info['id'], 'i']);

			$this->model('posts')->bring_to_top($thread_info['id'], 'question');
		}
		else
		{
			$this->update('question', array(
				'comment_count' => $discussion_count,
			), ['id', 'eq', $data['parent_id'], 'i']);
		}

		$this->mention_users('question', $thread_info['id'], null, 0, $data['uid'], $data['at_uid'], $data['message']);
		$this->notify_users('question', $thread_info['id'], null, 0, $data['uid'], $data['at_uid'], $data['message']);

		// TODO: 记录用户动态

		return $item_id;
	}


	private function real_publish_answer_discussion(&$data)
	{
		if (!$reply_info = $this->model('content')->get_reply_info_by_id('answer', $data['parent_id']))
		{
			return false;
		}
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('question', $reply_info['question_id']))
		{
			return false;
		}

		$now = fake_time();

		$item_id = $this->insert('answer_discussion', array(
			'answer_id' => $data['parent_id'],
			'message' => htmlspecialchars($data['message']),
			'add_time' => $now,
			'uid' => $data['uid'],
			'at_uid' => $data['at_uid'],
		));

		if (!$item_id)
		{
			return false;
		}

		$discussion_count = $this->count('answer_discussion', ['answer_id', 'eq', $data['parent_id'], 'i']);

		if (S::get('discussion_bring_top') == 'Y')
		{
			$thread_id = ($thread_info['redirect_id'] ? $thread_info['redirect_id'] : $thread_info['id']);

			$this->update('question', array(
				'update_time' => $now,
				'last_uid' => $data['uid'],
			), ['id', 'eq', $thread_id, 'i']);

			$this->model('posts')->bring_to_top($thread_id, 'question');
		}

		$this->update('answer', array(
			'comment_count' => $discussion_count,
		), ['id', 'eq', $data['parent_id'], 'i']);


		if ($data['at_uid'])
		{
			$recipient_uid = $data['at_uid'];
		}
		else
		{
			$recipient_uid = $reply_info['uid'];
		}

		$this->mention_users('question', $thread_info['id'], 'answer', $reply_info['id'], $data['uid'], $recipient_uid, $data['message']);
		$this->notify_users('question', $thread_info['id'], 'answer', $reply_info['id'], $data['uid'], $recipient_uid, $data['message']);

		// TODO: 记录用户动态

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
    public function publish_voting($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('voting', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_voting($data);
		}

		$is_anonymous = ($real_uid != $data['uid']);
		$this->model('currency')->process($real_uid, 'NEW_THREAD', S::get('currency_system_config_new_voting'), '发布投票', null, null, $is_anonymous);
		return $item_id;
	}


	public function publish_answer($data, $real_uid, $later, $pay)
	{
		if ($later)
		{
			$this->schedule('answer', $this->calc_later_time($later), $data);
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
			$this->schedule('article_comment', $this->calc_later_time($later), $data);
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
			$this->schedule('video_comment', $this->calc_later_time($later), $data);
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
    
    public function publish_voting_comment($data, $real_uid, $later, $pay)
	{
		if ($later)
		{
			$this->schedule('voting_comment', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_voting_comment($data);
		}

		if ($pay)
		{
			$is_anonymous = ($real_uid != $data['uid']);
			$this->model('currency')->process($real_uid, 'REPLY', S::get('currency_system_config_reply_voting'), '回应文章', $data['parent_id'], 'voting', $is_anonymous);
		}
		return $item_id;
	}



	public function publish_question_discussion($data, $real_uid, $later)
	{
		if ($later)
		{
			$this->schedule('question_discussion', $this->calc_later_time($later), $data);
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
			$this->schedule('answer_discussion', $this->calc_later_time($later), $data);
		}
		else
		{
			$item_id = $this->real_publish_answer_discussion($data);
		}

		return $item_id;
	}
}
