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
	private function should_user_be_flagged($recipient_user, $content_reputation, $agree_value)
	{
		if (!$recipient_user OR !!$recipient_user['forbidden'])
		{
			return null;
		}

		if ($agree_value > 0)
		{
			$reputation = $recipient_user['permission']['flagging_reputation_gt'];
			$group_id = $recipient_user['permission']['flagging_reputation_gt_group_id'];
			if (is_numeric($reputation) AND is_numeric($group_id))
			{
				if ($content_reputation > $reputation)
				{
					return $this->model('usergroup')->get_group_id_by_value_flagged($group_id);
				}
			}
		}
		else
		{
			$reputation = $recipient_user['permission']['flagging_reputation_lt'];
			$group_id = $recipient_user['permission']['flagging_reputation_lt_group_id'];
			if (is_numeric($reputation) AND is_numeric($group_id))
			{
				if ($content_reputation < $reputation)
				{
					return $this->model('usergroup')->get_group_id_by_value_flagged($group_id);
				}
			}
		}

		return null;
	}

	private function check_reputation_type($item_type)
	{
		$reputation_types = S::get('reputation_types');
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
	private function update_recipient_user_info($item_type, $recipient_user, $agree_value, $reputation_value, $flag_group_id = null)
	{
		// 用户已注销
		if (!$recipient_user)
		{
			return;
		}

		if ($agree_value > 0)
		{
			// 被标记的用户不增加声望
			// reputation_types以外post不增加声望
			if ($recipient_user['permission']['no_reputation_upvote'] OR !$this->check_reputation_type($item_type))
			{
				$reputation_value = 0;
			}
		}
		else
		{
			if ($recipient_user['permission']['no_reputation_downvote'])
			{
				$reputation_value = 0;
			}
		}

		$set = '`agree_count` = `agree_count` + ' . ($agree_value) . ', `reputation` = `reputation` + ' . ($reputation_value);
		if (!is_null($flag_group_id))
		{
			$set .= ', `flagged` = ' . $flag_group_id;
		}

		$this->update('users', $set, ['uid', 'eq', $recipient_user['uid'], 'i']);
	}

	// 更新被赞post赞数和声望(热度)
	private function update_item_agree_count_and_reputation($item_type, $item_id, $agree_value, $reputation_value)
	{
		$this->update(
			$item_type,
			'`agree_count` = `agree_count` + ' . $agree_value . ', `reputation` = `reputation` + ' . $reputation_value,
			['id', 'eq', $item_id]
		);
	}

	// 更新posts_index表声望(用于热门排序)
	private function update_index_reputation($item_type, $item_id, $item_info, $reputation_value)
	{
		switch ($item_type)
		{
			case 'question_reply':
				$parent_type = 'question';
				break;

			case 'article_reply':
				$parent_type = 'article';
				break;

			case 'video_reply':
				$parent_type = 'video';
				break;

			default:
				return;
		}

		$this->update('posts_index', '`reputation` = `reputation` + ' . $reputation_value, [['post_id', 'eq', $item_info['parent_id'], 'i'], ['post_type', 'eq', $parent_type]]);
	}


	// 根据post字数获得额外奖励声望
	private function get_bonus_factor($item_info)
	{
		$bonus_factor = S::get('bonus_factor');
		if (!is_numeric($bonus_factor))
		{
			return 1;
		}

		$bonus_max_count = S::get_int('bonus_max_count');
		$bonus_min_count = S::get_int('bonus_min_count');

		$message = $item_info['message'];
		if (!$message)
		{
			return 1;
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
				return $bonus_factor / 4 * (1 + $sigmoid);
			}
			else
			{
				return $bonus_factor / 2 * (1 + $sigmoid);
			}
		}
		return 1;
	}

	// 根据一星期点赞次数计算动态声望
	private function get_dynamic_factor($uid, $agree_value)
	{
		// 1赞同 -1反对
		if ($agree_value > 0)
		{
			$arg = S::get('reputation_dynamic_weight_agree');
		}
		else
		{
			$arg = S::get('reputation_dynamic_weight_disagree');
		}

		if (is_numeric($arg))
		{
			$total = $this->model('vote')->get_user_vote_count($uid, 7, $agree_value);

			if ($total > 0)
			{
				return exp(-($arg) * $total);
			}
		}
		return 1;
	}


	private function update_item_and_user_info($item_type, $item_id, $vote_user, $recipient_user, $agree_value, $user_reputation_value, $content_reputation_value)
	{
		$flag_group_id = null;

		if ($user_reputation_value OR $content_reputation_value)
		{
			// 已缓存过
			$item_info = $this->model('post')->get_thread_or_reply_info_by_id($item_type, $item_id);

			if (!$vote_user['permission']['no_dynamic_reputation_factor'])
			{
				if ($user_reputation_value)
				{
					$factor = $this->get_dynamic_factor($vote_user['uid'], $agree_value);
					$user_reputation_value = $user_reputation_value * $factor;
				}
			}
			if (!$vote_user['permission']['no_bonus_reputation_factor'])
			{
				$factor = $this->get_bonus_factor($item_info);
				$user_reputation_value = $user_reputation_value * $factor;
				$content_reputation_value = $content_reputation_value * $factor;
			}

			if (is_infinite($user_reputation_value))
			{
				$user_reputation_value = 0;
			}
			if (is_infinite($content_reputation_value))
			{
				$content_reputation_value = 0;
			}
			$user_reputation_value = round($user_reputation_value, 6);
			$content_reputation_value = round($content_reputation_value, 6);

			if ($content_reputation_value)
			{
				$content_reputation_now = $item_info['reputation'] + $content_reputation_value;
				if ($agree_value > 0)
				{
					$this->model('activity')->push_item_with_high_reputation($item_type, $item_id, $content_reputation_now, $item_info['uid']);
				}

				$flag_group_id = $this->should_user_be_flagged($recipient_user, $content_reputation_now, $agree_value);

				$this->update_index_reputation($item_type, $item_id, $item_info, $content_reputation_value);
			}
		}

		$this->update_item_agree_count_and_reputation($item_type, $item_id, $agree_value, $content_reputation_value);
		$this->update_recipient_user_info($item_type, $recipient_user, $agree_value, $user_reputation_value, $flag_group_id);
	}


	private function get_initial_reputation($vote_user, $recipient_user, $agree_value, &$result_user_reputation, &$result_content_reputation)
	{
		if (!!$recipient_user AND is_numeric($recipient_user['reputation_factor_receive']))
		{
			$user_reputation_factor = $recipient_user['reputation_factor_receive'];
		}
		else
		{
			$user_reputation_factor = $vote_user['reputation_factor'];
		}

		if ($agree_value > 0 AND $vote_user['permission']['no_upvote_reputation_factor'])
		{
			$user_reputation_factor = 0;
		}
		else if ($agree_value < 0 AND $vote_user['permission']['no_downvote_reputation_factor'])
		{
			$user_reputation_factor = 0;
		}

		if (is_numeric($vote_user['content_reputation_factor']))
		{
			$content_reputation_factor = $vote_user['content_reputation_factor'];
		}
		else
		{
			$content_reputation_factor = $user_reputation_factor;
		}

		$result_user_reputation = $agree_value * $user_reputation_factor;
		$result_content_reputation = $agree_value * $content_reputation_factor;
	}


	public function apply($item_type, $item_id, $uid, $item_uid, $agree_value, $update_agree_count_only = false)
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
			$user_reputation_value = 0;
			$content_reputation_value = 0;
		}
		else
		{
			$this->get_initial_reputation($vote_user, $recipient_user, $agree_value, $user_reputation_value, $content_reputation_value);
		}

		$this->update_item_and_user_info($item_type, $item_id, $vote_user, $recipient_user, $agree_value, $user_reputation_value, $content_reputation_value);
	}

}
