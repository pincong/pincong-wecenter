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

class module_class extends AWS_MODEL
{
	public function recommend_users($uid, $limit = 10)
    {
        if ($users_list = AWS_APP::cache()->get('user_recommend_' . $uid))
        {
            return $users_list;
        }

        if (!$friends = $this->model('follow')->get_user_friends($uid, 100))
        {
            return $this->model('account')->get_user_list('uid <> ' . intval($uid), $limit);
        }

        foreach ($friends as $key => $val)
        {
            $follow_uids[] = $val['uid'];
            $follow_users_info[$val['uid']] = $val;
        }

        if ($users_focus = $this->query_all("SELECT DISTINCT friend_uid, fans_uid FROM " . $this->get_table('user_follow') . " WHERE fans_uid IN (" . implode(',', $follow_uids) . ") ORDER BY follow_id DESC", $limit))
        {
            foreach ($users_focus as $key => $val)
            {
                $friend_uids[$val['friend_uid']] = $val['friend_uid'];

                $users_ids_recommend[$val['friend_uid']] = array(
                    'type' => 'friend',
                    'fans_uid' => $val['fans_uid']
                );
            }
        }

        // 取我关注的话题
        if ($my_focus_topics = $this->model('topic')->get_focus_topic_list($uid, null))
        {
            foreach ($my_focus_topics as $key => $val)
            {
                $my_focus_topics_ids[] = $val['topic_id'];
                $my_focus_topics_info[$val['topic_id']] = $val;
            }

            if (sizeof($my_focus_topics_ids) > 0)
            {
                array_walk_recursive($my_focus_topics_ids, 'intval_string');

                if ($topic_focus_uids = $this->query_all("SELECT DISTINCT uid, topic_id FROM " . $this->get_table('topic_focus') . " WHERE topic_id IN(" . implode(',', $my_focus_topics_ids) . ")"))
                {
                    foreach ($topic_focus_uids as $key => $val)
                    {
                        if ($friend_uids[$val['uid']])
                        {
                            continue;
                        }

                        $friend_uids[$val['uid']] = $val['uid'];

                        $users_ids_recommend[$val['uid']] = array(
                            'type' => 'topic',
                            'topic_id' => $val['topic_id']
                        );
                    }
                }
            }
        }

        if (! $friend_uids)
        {
			$where = 'uid NOT IN (' . implode($follow_uids, ',') . ')';
			$where = '(' . $where . ') AND uid <> ' . intval($uid);
            return $this->model('account')->get_user_list($where, $limit);
        }

		$where = 'uid IN(' . implode($friend_uids, ',') . ') AND uid NOT IN (' . implode($follow_uids, ',') . ')';
        if ($users_list = $this->model('account')->get_user_list($where, $limit))
        {
            foreach ($users_list as $key => $val)
            {
                $users_list[$key]['type'] = $users_ids_recommend[$val['uid']]['type'];

                if ($users_ids_recommend[$val['uid']]['type'] == 'friend')
                {
					// TODO: 何处用到?
                    $users_list[$key]['friend_users'] = $follow_users_info[$users_ids_recommend[$val['uid']]['fans_uid']];
                }
                else if ($users_ids_recommend[$val['uid']]['type'] == 'topic')
                {
                    $users_list[$key]['topic_info'] = $my_focus_topics_info[$users_ids_recommend[$val['uid']]['topic_id']];
                }
            }

            AWS_APP::cache()->set('user_recommend_' . $uid, $users_list, get_setting('cache_level_normal'));
        }

        return $users_list;
    }

	// 我关注的人关注的话题
	public function recommend_topics($uid, $limit = 10)
	{
		$topic_focus_ids = array(0);

		$follow_uids = array(0);

		if ($topic_focus = $this->query_all("SELECT topic_id FROM " . $this->get_table("topic_focus") . " WHERE uid = " . (int)$uid))
		{
			foreach ($topic_focus as $key => $val)
			{
				$topic_focus_ids[] = $val['topic_id'];
			}
		}

		if ($friends = $this->model('follow')->get_user_friends($uid, 100))
		{
			foreach ($friends as $key => $val)
			{
				$follow_uids[] = $val['uid'];
				$follow_users_array[$val['uid']] = $val;
			}
		}

		if (! $follow_uids)
		{
			return $this->model('topic')->get_topic_list("topic_id NOT IN(" . implode($topic_focus_ids, ',') . ")", 'topic_id DESC', $limit);
		}

		if ($topic_focus = $this->query_all("SELECT DISTINCT topic_id, uid FROM " . $this->get_table("topic_focus") . " WHERE uid IN(" . implode($follow_uids, ',') . ") AND topic_id NOT IN (" . implode($topic_focus_ids, ',') . ") ORDER BY focus_id DESC LIMIT " . $limit))
		{
			foreach ($topic_focus as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
				$topic_id_focus_uid[$val['topic_id']] = $val['uid'];
			}
		}
		if (! $topic_ids)
		{
			if ($topic_focus_ids)
			{
				return $this->model('topic')->get_topic_list("topic_id NOT IN (" . implode($topic_focus_ids, ',') . ")", 'topic_id DESC', $limit);
			}
			else
			{
				return $this->model('topic')->get_topic_list(null, 'topic_id DESC', $limit);
			}
		}

		if ($topic_focus_ids)
		{
			$topics = $this->fetch_all('topic', 'topic_id IN(' . implode($topic_ids, ',') . ') AND topic_id NOT IN(' . implode($topic_focus_ids, ',') . ')', 'topic_id DESC', $limit);
		}
		else
		{
			$topics = $this->fetch_all('topic', 'topic_id IN(' . implode($topic_ids, ',') . ')', 'topic_id DESC', $limit);
		}

		foreach ($topics as $key => $val)
		{
			// TODO: 何处用到?
			$topics[$key]['focus_users'] = $follow_users_array[$topic_id_focus_uid[$val['topic_id']]];

			if (!$val['url_token'])
			{
				$topics[$key]['url_token'] = urlencode($val['topic_title']);
			}
		}

		return $topics;
	}

	public function recommend_users_topics($uid)
	{
		if (!$recommend_users = $this->recommend_users($uid, 20))
		{
			return false;
		}

		if (! $recommend_topics = $this->recommend_topics($uid, 20))
		{
			return array_slice($recommend_users, 0, get_setting('recommend_users_number'));
		}

		if ($recommend_topics)
		{
			shuffle($recommend_topics);

			$recommend_topics = array_slice($recommend_topics, 0, intval(get_setting('recommend_users_number') / 2));
		}

		if ($recommend_users)
		{
			shuffle($recommend_users);

			$recommend_users = array_slice($recommend_users, 0, (get_setting('recommend_users_number') - count($recommend_topics)));
		}

		if (! is_array($recommend_users))
		{
			$recommend_users = array();
		}

		return array_merge($recommend_users, $recommend_topics);
	}

	public function sidebar_hot_topics($category_id = 0)
	{
		$num = intval(get_setting('recommend_users_number'));
		if ($num)
		{
			return $this->model('topic')->get_hot_topics($category_id, $num, 'week');
		}
	}

	public function sidebar_hot_users($uid = 0, $limit = 5)
	{
		//if ($users_list = $this->fetch_all('users', 'uid <> ' . intval($uid) . ' AND last_login > ' . (time() - (60 * 60 * 24 * 7)), 'RAND()', ($limit * 4)))
		if ($users_list = $this->fetch_all('users', 'uid <> ' . intval($uid) . ' AND reputation > 5 AND forbidden = 0', 'RAND()', ($limit * 4)))
		{
			foreach($users_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$users_list[$key]['url_token'] = urlencode($val['user_name']);
				}
			}
		}

		shuffle($users_list);

		return array_slice($users_list, 0, $limit);
	}

}