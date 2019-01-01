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

class ratelimit_class extends AWS_MODEL
{
	public function check_question($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('question', $where);
		if ($count >= $limit)
		{
			return false;
		}

		$where = "type = 'question' AND time > " . $time_after . " AND uid =" . $uid;
		$count += $this->count('scheduled_posts', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

	public function check_article($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('article', $where);
		if ($count >= $limit)
		{
			return false;
		}

		$where = "type = 'article' AND time > " . $time_after . " AND uid =" . $uid;
		$count += $this->count('scheduled_posts', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

	public function check_video($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('video', $where);
		if ($count >= $limit)
		{
			return false;
		}

		$where = "type = 'video' AND time > " . $time_after . " AND uid =" . $uid;
		$count += $this->count('scheduled_posts', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}


	public function check_answer($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('answer', $where);
		if ($count >= $limit)
		{
			return false;
		}

		$where = "type = 'answer' AND time > " . $time_after . " AND uid =" . $uid;
		$count += $this->count('scheduled_posts', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

	public function check_article_comment($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('article_comment', $where);
		if ($count >= $limit)
		{
			return false;
		}

		$where = "type = 'article_comment' AND time > " . $time_after . " AND uid =" . $uid;
		$count += $this->count('scheduled_posts', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

	public function check_video_comment($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('video_comment', $where);
		if ($count >= $limit)
		{
			return false;
		}

		$where = "type = 'video_comment' AND time > " . $time_after . " AND uid =" . $uid;
		$count += $this->count('scheduled_posts', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}


	public function check_question_discussion($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('question_discussion', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

	public function check_answer_discussion($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('answer_discussion', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

	public function check_video_danmaku($uid, $limit)
	{
		$limit = intval($limit);
		if (!$limit)
		{
			return true;
		}

		$uid = intval($uid);
		$time_after = real_time() - 24 * 3600;

		$where = "add_time > " . $time_after . " AND uid =" . $uid;
		$count = $this->count('video_danmaku', $where);
		if ($count >= $limit)
		{
			return false;
		}

		return true;
	}

}