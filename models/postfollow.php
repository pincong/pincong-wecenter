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

class postfollow_class extends AWS_MODEL
{
	public function get_followers($post_type, $post_id)
	{
		if (!$this->model('content')->check_thread_type($post_type))
		{
			return false;
		}
		$where = "post_id = " . intval($post_id) . " AND post_type = '" . $post_type . "'";

		return $this->query_all('SELECT uid FROM ' . $this->get_table('post_follow') . ' WHERE ' . $where) ;
	}

	public function follow($post_type, $post_id, $uid)
	{
		if (!$this->model('content')->check_thread_type($post_type))
		{
			return false;
		}
		$where = "uid = " . intval($uid) . " AND post_id = " . intval($post_id) . " AND post_type = '" . $post_type . "'";

		if (!$this->fetch_one('post_follow', 'id', $where))
		{
			$this->insert('post_follow', array(
				'post_type' => $post_type,
				'post_id' => intval($post_id),
				'uid' => intval($uid),
				'add_time' => fake_time()
			));
		}
	}

	public function unfollow($post_type, $post_id, $uid)
	{
		if (!$this->model('content')->check_thread_type($post_type))
		{
			return false;
		}
		$where = "uid = " . intval($uid) . " AND post_id = " . intval($post_id) . " AND post_type = '" . $post_type . "'";

		$this->delete('post_follow', $where);
	}

	public function is_following($post_type, $post_id, $uid)
	{
		if (!$this->model('content')->check_thread_type($post_type))
		{
			return false;
		}
		$where = "uid = " . intval($uid) . " AND post_id = " . intval($post_id) . " AND post_type = '" . $post_type . "'";

		return $this->fetch_one('post_follow', 'id', $where);
	}

}
