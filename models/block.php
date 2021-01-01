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

class block_class extends AWS_MODEL
{
	public function block_user($uid, $target_uid, $force = false)
	{
		$uid = intval($uid);
		$target_uid = intval($target_uid);
		if ($uid <= 0 OR $target_uid <= 0 OR $uid == $target_uid)
		{
			return false;
		}
		$where = [
			['uid', 'eq', $uid],
			['target_uid', 'eq', $target_uid]
		];

		$value = $force ? -2 : -1;

		if (!$this->fetch_one('user_relation', 'id', $where))
		{
			$this->insert('user_relation', array(
				'uid' => $uid,
				'target_uid' => $target_uid,
				'value' => $value,
				'time' => fake_time()
			));
		}
		else
		{
			$where[] = ['value', 'notEq', $value];
			$this->update('users', array(
				'value' => $value,
				'time' => fake_time()
			), $where);
		}
	}

	public function unblock_user($uid, $target_uid, $force = false)
	{
		$uid = intval($uid);
		$target_uid = intval($target_uid);
		if ($uid <= 0 OR $target_uid <= 0 OR $uid == $target_uid)
		{
			return false;
		}
		$where = [
			['uid', 'eq', $uid],
			['target_uid', 'eq', $target_uid]
		];

		if ($force)
		{
			$where[] = ['value', 'lt', 0];
		}
		else
		{
			$where[] = ['value', 'eq', -1];
		}
		$this->delete('user_relation', $where);
	}

	public function has_user_been_blocked($uid, $by_uid)
	{
		$where = [
			['uid', 'eq', $by_uid, 'i'],
			['target_uid', 'eq', $uid, 'i'],
			['value', 'lt', 0]
		];
		return !!$this->fetch_one('user_relation', 'id', $where);
	}

	public function get_user_blocked_uids($uid)
	{
		$where = [
			['uid', 'eq', $uid, 'i'],
			['value', 'lt', 0]
		];
		return $this->fetch_column('user_relation', 'target_uid', $where, 'id DESC');
	}

	public function get_users_blocked_uids($uids)
	{
		if (!is_array($uids) OR !count($uids))
		{
			return [];
		}
		$where = [
			['uid', 'in', $uids, 'i'],
			['value', 'lt', 0]
		];
		$items = $this->fetch_all('user_relation', $where, 'id DESC');
		$blocklists = array_fill_keys($uids, []);
		foreach ($items as $item)
		{
			$blocklists[$item['uid']][] = $item['target_uid'];
		}
		return $blocklists;
	}

	public function get_user_blocklist($uid)
	{
		return $this->model('account')->get_user_info_by_uids($this->get_user_blocked_uids($uid));
	}

}
