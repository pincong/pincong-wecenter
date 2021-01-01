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

class follow_class extends AWS_MODEL
{
	public function user_follow_add($fans_uid, $friend_uid)
	{
		if ($fans_uid == $friend_uid)
		{
			return false;
		}

		if (! $this->model('account')->uid_exists($fans_uid) OR ! $this->model('account')->uid_exists($friend_uid))
		{
			return false;
		}

		if ($this->user_follow_check($fans_uid, $friend_uid))
		{
			return false;
		}
		else
		{
			$result = $this->insert('user_follow', array(
				'fans_uid' => intval($fans_uid),
				'friend_uid' => intval($friend_uid),
				'add_time' => fake_time()
			));

			$this->update_user_count($friend_uid);
			$this->update_user_count($fans_uid);

			return $result;
		}

	}

	public function user_follow_check($fans_uid, $friend_uid)
	{
		if (! $fans_uid OR ! $friend_uid)
		{
			return false;
		}

		if ($fans_uid == $friend_uid)
		{
			return false;
		}

		$where = [['fans_uid', 'eq', $fans_uid, 'i'], ['friend_uid', 'eq', $friend_uid, 'i']];
		return $this->fetch_one('user_follow', 'follow_id', $where);
	}

	public function users_follow_check($fans_uid, $friend_uids)
	{
		if (! $fans_uid OR ! is_array($friend_uids))
		{
			return false;
		}

		$where = [['fans_uid', 'eq', $fans_uid, 'i'], ['friend_uid', 'in', $friend_uids, 'i']];
		$user_follow = $this->fetch_all('user_follow', $where);

		foreach ($user_follow AS $key => $val)
		{
			$result[$val['friend_uid']] = TRUE;
		}

		return $result;
	}

	public function user_follow_del($fans_uid, $friend_uid)
	{
		if (! $fans_uid OR ! $friend_uid)
		{
			return false;
		}

		if (! $this->user_follow_check($fans_uid, $friend_uid))
		{
			return false;
		}
		else
		{
			$where = [['fans_uid', 'eq', $fans_uid, 'i'], ['friend_uid', 'eq', $friend_uid, 'i']];
			$result = $this->delete('user_follow', $where);

			$this->update_user_count($friend_uid);
			$this->update_user_count($fans_uid);

			return $result;
		}
	}

	/**
	 * 获取单个用户的粉丝列表
	 *
	 * @param  $friend_uid
	 * @param  $limit
	 */
	public function get_user_fans($friend_uid, $limit = 20)
	{
		if (!$user_fans = $this->fetch_all('user_follow', ['friend_uid', 'eq', $friend_uid, 'i'], 'add_time DESC', $limit))
		{
			return false;
		}

		foreach ($user_fans AS $key => $val)
		{
			$fans_uids[$val['fans_uid']] = $val['fans_uid'];
		}

		return $this->model('account')->get_user_info_by_uids($fans_uids);
	}

	/**
	 * 获取单个用户的关注列表(我关注的人)
	 *
	 * @param  $friend_uid
	 * @param  $limit
	 */
	public function get_user_friends($fans_uid, $limit = 20)
	{
		if (!$user_follow = $this->fetch_all('user_follow', ['fans_uid', 'eq', $fans_uid, 'i'], 'add_time DESC', $limit))
		{
			return false;
		}

		foreach ($user_follow AS $key => $val)
		{
			$friend_uids[$val['friend_uid']] = $val['friend_uid'];
		}

		return $this->model('account')->get_user_info_by_uids($friend_uids);
	}

	// 得到我关注的人 uid
	public function get_user_friends_ids($fans_uid, $limit = 20)
	{
		if (!$user_follow = $this->fetch_all('user_follow', ['fans_uid', 'eq', $fans_uid, 'i'], 'add_time DESC', $limit))
		{
			return false;
		}

		foreach ($user_follow AS $key => $val)
		{
			$friend_uids[$val['friend_uid']] = $val['friend_uid'];
		}

		return $friend_uids;
	}

	public function update_user_count($uid)
	{
		$uid = intval($uid);
		return $this->update('users', array(
			'fans_count' => $this->count('user_follow', ['friend_uid', 'eq', $uid]),
			'friend_count' => $this->count('user_follow', ['fans_uid', 'eq', $uid])
		), ['uid', 'eq', $uid]);
	}
}