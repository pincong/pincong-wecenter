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
	public function get_follower_uids($post_type, $post_id)
	{
		if (!$this->model('post')->check_thread_type($post_type))
		{
			return false;
		}
		$where = [['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]];
		return $this->fetch_column('post_follow', 'uid', $where, 'add_time DESC', 200);
	}

	public function get_following_posts($uid, $post_type, $page, $per_page)
	{
		$where[] = ['uid', 'eq', $uid, 'i'];

		if ($post_type AND $this->model('post')->check_thread_type($post_type))
		{
			$where[] = ['post_type', 'eq', $post_type];
		}

		$result = array();

		$posts = $this->fetch_page('post_follow', $where, 'id DESC', $page, $per_page);
		if (!$posts)
		{
			return $result;
		}

		foreach ($posts as $key => $val)
		{
			switch ($val['post_type'])
			{
				case 'question':
					$question_ids[] = $val['post_id'];
					break;
				case 'article':
					$article_ids[] = $val['post_id'];
					break;
				case 'video':
					$video_ids[] = $val['post_id'];
					break;
			}
		}

		if ($question_ids)
		{
			$question_infos = $this->model('post')->get_posts_by_ids('question', $question_ids);
			foreach ($question_infos as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}
		if ($article_ids)
		{
			$article_infos = $this->model('post')->get_posts_by_ids('article', $article_ids);
			foreach ($article_infos as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}
		if ($video_ids)
		{
			$video_infos = $this->model('post')->get_posts_by_ids('video', $video_ids);
			foreach ($video_infos as $key => $val)
			{
				$uids[] = $val['uid'];
			}
		}

		if ($uids)
		{
			$user_infos = $this->model('account')->get_user_info_by_uids($uids);
		}

		foreach ($posts as $key => $val)
		{
			switch ($val['post_type'])
			{
				case 'question':
					$result[$key] = $question_infos[$val['post_id']];
					break;
				case 'article':
					$result[$key] = $article_infos[$val['post_id']];
					break;
				case 'video':
					$result[$key] = $video_infos[$val['post_id']];
					break;
			}
			$result[$key]['post_type'] = $val['post_type'];
			$result[$key]['user_info'] = $user_infos[$result[$key]['uid']];
		}

		return $result;
	}

	public function follow($post_type, $post_id, $uid)
	{
		if (!$this->model('post')->check_thread_type($post_type))
		{
			return false;
		}
		$where = [['uid', 'eq', $uid, 'i'], ['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]];

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
		if (!$this->model('post')->check_thread_type($post_type))
		{
			return false;
		}
		$where = [['uid', 'eq', $uid, 'i'], ['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]];

		$this->delete('post_follow', $where);
	}

	public function is_following($post_type, $post_id, $uid)
	{
		if (!$this->model('post')->check_thread_type($post_type))
		{
			return false;
		}
		$where = [['uid', 'eq', $uid, 'i'], ['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]];

		return $this->fetch_one('post_follow', 'id', $where);
	}

}
