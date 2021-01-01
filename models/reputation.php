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

class reputation_class extends AWS_MODEL
{
	private function check_reputation_type(&$item_type)
	{
		$reputation_types = get_setting('reputation_types');
		if (!$reputation_types)
		{
			return true;
		}
		$reputation_types = explode(',', $reputation_types);
		if (!is_array($reputation_types))
		{
			return false;
		}
		$reputation_types = array_map('trim', $reputation_types);
		if (!in_array($item_type, $reputation_types))
		{
			return false;
		}
		return true;
	}

	// 更新被赞用户赞数和声望
	private function update_user_agree_count_and_reputation(&$item_type, &$recipient_user, $agree_value, $reputation_value)
	{
		// 用户已注销
		if (!$recipient_user)
		{
			return;
		}

		if ($reputation_value > 0)
		{
			// 被标记的用户不增加声望
			// reputation_types以外post不增加声望
			if ($recipient_user['flagged'] OR !$this->check_reputation_type($item_type))
			{
				$reputation_value = 0;
			}
		}

		$sql = 'UPDATE ' . $this->get_table('users') . ' SET agree_count = agree_count + ' . ($agree_value) . ', reputation = reputation + ' . ($reputation_value) . ' WHERE uid = ' . ($recipient_user['uid']);
		$this->query($sql);

		// 如果开启了自动封禁功能
		if ($auto_banning_type = get_setting('auto_banning_type'))
		{
			if ($auto_banning_type != 'OFF')
			{
				$agree_count = $recipient_user['agree_count'] + $agree_value;
				$reputation = $recipient_user['reputation'] + $reputation_value;
				$this->model('user')->auto_forbid_user($uid, $recipient_user['forbidden'], $agree_count, $reputation, $auto_banning_type);
			}
		}
	}

	// 更新被赞post赞数和声望(热度)
	private function update_item_agree_count_and_reputation(&$item_type, $item_id, $agree_value, $reputation_value)
	{
		$sql = 'UPDATE ' . $this->get_table($item_type) . ' SET agree_count = agree_count + ' . ($agree_value) . ', reputation = reputation + ' . ($reputation_value) . ' WHERE id = ' . ($item_id);
		$this->query($sql);
	}

	// 更新posts_index表声望(用于热门排序)
	private function update_index_reputation(&$item_type, $item_id, &$item_info, $reputation_value)
	{
		switch ($item_type)
		{
			case 'answer':
				$parent_id = $item_info['question_id'];
				$parent_type = 'question';
				break;

			case 'article_comment':
				$parent_id = $item_info['article_id'];
				$parent_type = 'article';
				break;

			case 'video_comment':
				$parent_id = $item_info['video_id'];
				$parent_type = 'video';
				break;

			default:
				return;
		}

		$where = "post_id = " . $parent_id . " AND post_type = '" . $parent_type . "'";
		$sql = 'UPDATE ' . $this->get_table('posts_index') . ' SET reputation = reputation + ' . $reputation_value . ' WHERE ' . $where;
		$this->query($sql);
	}

	// 推入精选
	private function push_item_with_high_reputation(&$item_type, $item_id, $item_reputation)
	{
		$push_reputation = get_setting('push_reputation');

		if (!is_numeric($push_reputation) OR $item_reputation < $push_reputation)
		{
			return;
		}

		if (!$this->model('activity')->check_push_type($item_type))
		{
			return;
		}

		$where = "`uid` = 0 AND `item_type` = '". ($item_type) . "' AND `item_id` = " . ($item_id);
		if (!!$this->fetch_one('activity', 'id', $where))
		{
			return;
		}

		$parent_info = $this->model('content')->get_item_thread_info_by_id($item_type, $item_id);
		$category_id = intval($parent_info['category_id']);

		if (!$this->model('activity')->check_push_category($category_id))
		{
			return;
		}

		$this->model('activity')->log($item_type, $item_id, 0, $parent_info['thread_type'], $parent_info['thread_id'], $category_id);
	}


	// 根据post字数获得额外奖励声望
	private function get_bonus_reputation(&$item_info, $reputation_value)
	{
		$bonus_factor = get_setting('bonus_factor');
		if (!is_numeric($bonus_factor))
		{
			return $reputation_value;
		}

		$bonus_max_count = intval(get_setting('bonus_max_count'));
		$bonus_min_count = intval(get_setting('bonus_min_count'));

		$message = $item_info['message'];
		if (!$message)
		{
			return $reputation_value;
		}

		$message = preg_replace('/\[quote\](.*?)\[\/quote\]/is', '', $message);
		$message = preg_replace('/\[(.*?)\]/is', '', $message);

		// 字数 = 字节数 / 3
		$word_count = intval(strlen($message) / 3);

		// factor设定
		if ($word_count > $bonus_min_count)
		{
			$sigmoid = 1 / (1 + exp(-6 * (($word_count - $bonus_min_count) / $bonus_max_count - 0.5)));
			if ($word_count < ($bonus_max_count / 2))
			{
				$reputation_value = $bonus_factor / 4 * (1 + $sigmoid) * $reputation_value;
			}
			else
			{
				$reputation_value = $bonus_factor / 2 * (1 + $sigmoid) * $reputation_value;
			}
		}
		return $reputation_value;
	}


	// 根据一星期点赞次数计算动态声望
	private function calc_dynamic_reputation($uid, $agree_value, $reputation_value)
	{
		// 1赞同 -1反对
		if ($agree_value > 0)
		{
			$arg = get_setting('reputation_dynamic_weight_agree');
		}
		else
		{
			$arg = get_setting('reputation_dynamic_weight_disagree');
		}

		if (is_numeric($arg))
		{
			$total = $this->model('vote')->get_user_vote_count($uid, 7, $agree_value);

			if ($total > 0)
			{
				$reputation_value = $reputation_value * exp(-($arg) * $total);
			}
		}
		return $reputation_value;
	}


	private function update_agree_count_and_reputation($item_type, $item_id, &$vote_user, &$recipient_user, $agree_value, $reputation_value)
	{
		if ($reputation_value)
		{
			// 已缓存过
			$item_info = $this->model('content')->get_thread_or_reply_info_by_id($item_type, $item_id);

			if (!$vote_user['permission']['no_dynamic_reputation_factor'])
			{
				$reputation_value = $this->calc_dynamic_reputation($vote_user['uid'], $agree_value, $reputation_value);
			}
			if (!$vote_user['permission']['no_bonus_reputation_factor'])
			{
				$reputation_value = $this->get_bonus_reputation($item_info, $reputation_value);
			}

			if (is_infinite($reputation_value))
			{
				$reputation_value = 0;
			}
			else
			{
				$reputation_value = round($reputation_value, 6);
			}

			$item_reputation = $item_info['reputation'] + $reputation_value;
			if ($agree_value > 0)
			{
				$this->push_item_with_high_reputation($item_type, $item_id, $item_reputation);
			}
			$this->update_index_reputation($item_type, $item_id, $item_info, $reputation_value);
		}

		$this->update_item_agree_count_and_reputation($item_type, $item_id, $agree_value, $reputation_value);
		$this->update_user_agree_count_and_reputation($item_type, $recipient_user, $agree_value, $reputation_value);
	}


	private function get_initial_reputation(&$vote_user, &$recipient_user, $agree_value)
	{
		if ($vote_user['flagged'])
		{
			return 0;
		}

		if (!!$recipient_user AND is_numeric($recipient_user['reputation_factor_receive']))
		{
			$reputation_factor = $recipient_user['reputation_factor_receive'];
		}
		else
		{
			$reputation_factor = $vote_user['reputation_factor'];
		}

		if ($agree_value > 0 AND $vote_user['permission']['no_upvote_reputation_factor'])
		{
			$reputation_factor = 0;
		}
		else if ($agree_value < 0 AND $vote_user['permission']['no_downvote_reputation_factor'])
		{
			$reputation_factor = 0;
		}

		return $agree_value * $reputation_factor;
	}


	public function update($item_type, $item_id, $uid, $item_uid, $agree_value, $update_agree_count_only = false)
	{
		if ($agree_value > 0)
		{
			$agree_value = 1;
		}
		else
		{
			$agree_value = -1;
		}

		// 已缓存过
		$vote_user = $this->model('account')->get_user_and_group_info_by_uid($uid);

		$recipient_user = $this->model('account')->get_user_and_group_info_by_uid($item_uid);

		if ($update_agree_count_only)
		{
			$reputation_value = 0;
		}
		else
		{
			$reputation_value = $this->get_initial_reputation($vote_user, $recipient_user, $agree_value);
		}

		$this->update_agree_count_and_reputation($item_type, $item_id, $vote_user, $recipient_user, $agree_value, $reputation_value);
	}

}