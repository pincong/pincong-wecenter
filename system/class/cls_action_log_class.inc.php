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

class ACTION_LOG
{
	const CATEGORY_QUESTION = 1;	// 问题

	const CATEGORY_ANSWER = 2;	// 回答

	const CATEGORY_COMMENT = 3;	// 评论



	const ADD_QUESTION = 101;	// 添加问题

	const MOD_QUESTION_TITLE = 102;	// 修改问题标题

	const MOD_QUESTION_DESCRI = 103;	// 修改问题描述

	const ADD_REQUESTION_FOCUS = 105;	// 添加问题关注

	const REDIRECT_QUESTION = 107;	// 问题重定向

	const MOD_QUESTION_CATEGORY = 108;	// 修改问题分类

	const DEL_REDIRECT_QUESTION = 110;	// 删除问题重定向

	const ANSWER_QUESTION = 201;	// 回复问题

	const ADD_AGREE = 204;	// 增加赞同

	const ADD_USEFUL = 206;	// 加感谢作者

	const ADD_UNUSEFUL = 207;	// 问题没有帮助



	const ADD_ARTICLE = 501;	// 添加文章

	const ADD_AGREE_ARTICLE = 502;	// 赞同文章

	const ADD_COMMENT_ARTICLE = 503;	// 评论文章



	public static function associate_fresh_action($history_id, $associate_id, $associate_type, $associate_action, $uid, $ignore, $add_time)
	{
		// 删除相同用户关联 ID 下相同动作的旧动态
		AWS_APP::model()->delete('user_action_history_fresh', 'associate_id = ' . intval($associate_id) . ' AND associate_type = ' . intval($associate_type) . ' AND uid = ' . intval($uid));

		if (in_array($associate_action, array(
			self::ADD_AGREE,
			self::ANSWER_QUESTION,
			self::ADD_REQUESTION_FOCUS,
			self::ADD_AGREE_ARTICLE,
			self::ADD_COMMENT_ARTICLE
		)))
		{
			// 删除相同关联 ID 下相同动作的旧动态
			AWS_APP::model()->delete('user_action_history_fresh', 'associate_id = ' . intval($associate_id) . ' AND associate_type = ' . intval($associate_type) . ' AND associate_action = ' . intval($associate_action));
		}

		return AWS_APP::model()->insert('user_action_history_fresh', array(
			'history_id' => intval($history_id),
			'associate_id' => intval($associate_id),
			'associate_type' => intval($associate_type),
			'associate_action' => intval($associate_action),
			'uid' => intval($uid),
			'add_time' => $add_time
		));
	}

	public static function save_action($uid, $associate_id, $action_type, $action_id, $action_content = null, $action_attch = null, $add_time = null, $ignore = null, $addon_data = null)
	{
		if (!$uid OR !$associate_id)
		{
			return false;
		}

		if (is_digits($action_attch))
		{
			$action_attch_insert = $action_attch;
		}
		else
		{
			$action_attch_insert = '-1';
			$action_attch_update = $action_attch;
		}

		if (!$add_time)
		{
			$add_time = fake_time();
		}

		$history_id = AWS_APP::model()->insert('user_action_history', array(
			'uid' => intval($uid),
			'associate_type' => $action_type,
			'associate_action' => $action_id,
			'associate_id' => $associate_id,
			'associate_attached' => $action_attch_insert,
			'add_time' => $add_time
		));

		self::associate_fresh_action($history_id, $associate_id, $action_type, $action_id, $uid, $ignore, $add_time);

		return $history_id;
	}

	/**
	 *
	 * 根据事件 ID,得到事件列表
	 *
	 * @param boolean $count
	 * @param int     $event_id
	 * @param int     $limit
	 * @param int     $action_type
	 * @param int     $action_id
	 * @param int     $associate_attached
	 *
	 * @return array
	 */
	public static function get_action_by_event_id($event_id = 0, $limit = 20, $action_type = null, $action_id = null, $associate_attached = null)
	{
		if ($event_id)
		{
			$where[] = 'associate_id = ' . intval($event_id);
		}

		if ($action_type)
		{
			$where[] = 'associate_type IN (' . $action_type . ')';
		}

		if ($action_id)
		{
			$where[] = 'associate_action IN (' . $action_id . ')';
		}
		else
		{
			$where[] = 'associate_action NOT IN (' . implode(',', array(
				self::ADD_REQUESTION_FOCUS,
				self::ADD_AGREE,
				self::ADD_USEFUL,
				self::ADD_UNUSEFUL,
			)) . ')';
		}

		if (isset($associate_attached))
		{
			$where[] = "associate_attached  = '" . AWS_APP::model()->quote($associate_attached) . "'";
		}

		if ($user_action_history = AWS_APP::model()->fetch_all('user_action_history', implode(' AND ', $where), 'add_time DESC', $limit))
		{
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
		}

		return $user_action_history;
	}

	public static function get_action_by_history_id($history_id)
	{
		$action_history = AWS_APP::model()->fetch_row('user_action_history', 'history_id = ' . intval($history_id));

		return $action_history;
	}

	public static function update_action_time_by_history_id($history_id)
	{
		return AWS_APP::model()->update('user_action_history', array(
			'add_time' => fake_time()
		), 'history_id = ' . intval($history_id));
	}

	public static function get_action_by_where($where = null, $limit = 20, $ignore = false, $order = 'add_time DESC')
	{
		if (! $where)
		{
			return false;
		}

		$where = '(' . $where . ') AND fold_status = 0';

		if ($user_action_history = AWS_APP::model()->fetch_all('user_action_history', $where, $order, $limit))
		{
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}

		}

		return $user_action_history;
	}

	public static function get_actions_fresh_by_where($where = null, $limit = 20, $ignore = false)
	{
		if (!$where)
		{
			return false;
		}

		if ($action_history = AWS_APP::model()->query_all("SELECT history_id FROM " . get_table('user_action_history_fresh') . " WHERE " . $where . " ORDER BY add_time DESC", $limit))
		{
			foreach ($action_history as $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}

			if ($action_history = self::get_action_by_where('history_id IN(' . implode(',', $history_ids) . ')', null, null, null))
			{
				foreach ($action_history as $key => $val)
				{
					$last_history[$val['history_id']] = $action_history[$key];
				}

				krsort($last_history);

				return $last_history;
			}
		}
	}

	public static function format_action_data($action, $uid = 0, $user_name = null, $associate_question_info = null, $associate_topic_info = null)
	{
		$user_link_attr = 'class="aw-user-name" data-id="' . $uid . '"';
		$user_profile_url = 'people/' . $uid;

		if ($associate_topic_info)
		{
			$topic_link_attr = 'class="aw-topic-name" data-id="' . $associate_topic_info['topic_id'] . '"';

			if ($associate_topic_info['url_token'])
			{
				$topic_url = 'topic/' . $associate_topic_info['url_token'];
			}
			else
			{
				$topic_url = 'topic/' . $associate_topic_info['topic_id'];
			}
		}

		switch ($action)
		{
			case self::ADD_QUESTION:
				if ($associate_topic_info)
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> 在 <a href="' . $topic_url . '" ' . $topic_link_attr . '>' . $associate_topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题发起了一个问题');
				}
				else
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('发起了问题');
				}
				break;

			case self::ADD_REQUESTION_FOCUS:
				$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('关注了该问题');;
				break;

			case self::ANSWER_QUESTION:
				if ($associate_topic_info)
				{
					$action_string = '<a href="' . $topic_url . '" ' . $topic_link_attr . '>' . $associate_topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题新增了一个回答');
				}
				else
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('回答了问题');
				}
				break;

			case self::ADD_AGREE: // '增加赞同'
				if ($associate_topic_info)
				{
					$action_string = '<a href="' . $topic_url . '" ' . $topic_link_attr . '>' . $associate_topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题添加了一个回复赞同');
				}
				else
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('赞同了该回复');
				}
				break;

			case self::ADD_ARTICLE :
				if ($associate_topic_info)
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> 在 <a href="' . $topic_url . '" ' . $topic_link_attr . '>' . $associate_topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题发表了文章');
				}
				else
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('发表了文章');
				}
				break;

			case self::ADD_AGREE_ARTICLE :
				if ($associate_topic_info)
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> 在 <a href="' . $topic_url . '" ' . $topic_link_attr . '>' . $associate_topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题添加了一个文章赞同');
				}
				else
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('赞同了该文章');
				}
				break;

			case self::ADD_COMMENT_ARTICLE :
				if ($associate_topic_info)
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> 在 <a href="' . $topic_url . '" ' . $topic_link_attr . '>' . $associate_topic_info['topic_title'] . '</a> ' . AWS_APP::lang()->_t('话题添加了一个文章评论');
				}
				else
				{
					$action_string = '<a href="' . $user_profile_url . '" ' . $user_link_attr . '>' . $user_name . '</a> ' . AWS_APP::lang()->_t('评论了该文章');
				}
				break;
		}

		return $action_string;
	}

	public static function delete_action_history($where)
	{
		if ($action_history = AWS_APP::model()->fetch_all('user_action_history', $where))
		{
			foreach ($action_history AS $key => $val)
			{
				AWS_APP::model()->delete('user_action_history_fresh', 'history_id = ' . $val['history_id']);
			}

			$action_history = AWS_APP::model()->delete('user_action_history', $where);
		}
	}

	public static function set_fold_action_history($answer_id, $fold = 1)
	{
		AWS_APP::model()->update('user_action_history', array(
			'fold_status' => $fold
		), 'associate_type = ' . self::CATEGORY_ANSWER . ' AND associate_id = ' . intval($answer_id));

		AWS_APP::model()->update('user_action_history', array(
			'fold_status' => $fold
		), 'associate_type = ' . self::CATEGORY_QUESTION . ' AND associate_action = ' . self::ANSWER_QUESTION . ' AND associate_attached = ' . intval($answer_id));

		if ($fold == 1)
		{
			if ($action_history = AWS_APP::model()->fetch_all('user_action_history', 'associate_type IN(' . self::CATEGORY_QUESTION . ',' . self::CATEGORY_ANSWER . ') AND associate_action = ' . self::ANSWER_QUESTION . ' AND associate_attached = ' . intval($answer_id)))
			{
				foreach ($action_history AS $key => $val)
				{
					AWS_APP::model()->delete('user_action_history_fresh', 'history_id = ' . $val['history_id']);
				}
			}
		}

		return $fold;
	}

	public static function delete_expired_data()
	{
		$days = intval(get_setting('expiration_user_actions'));
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
		AWS_APP::model()->delete('user_action_history', 'add_time < ' . $time_before);
		AWS_APP::model()->delete('user_action_history_fresh', 'add_time < ' . $time_before);
	}

}
