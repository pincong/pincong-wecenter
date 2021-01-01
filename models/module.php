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
	public function recommend_users_topics($uid)
	{
		if (!$recommend_users = $this->recommend_users($uid, 20))
		{
			return false;
		}

		if (! $recommend_topics = $this->recommend_topics($uid, 20))
		{
			return array_slice($recommend_users, 0, S::get('recommend_users_number'));
		}

		if ($recommend_topics)
		{
			shuffle($recommend_topics);

			$recommend_topics = array_slice($recommend_topics, 0, S::get_int('recommend_users_number') / 2);
		}

		if ($recommend_users)
		{
			shuffle($recommend_users);

			$recommend_users = array_slice($recommend_users, 0, (S::get('recommend_users_number') - count($recommend_topics)));
		}

		if (! is_array($recommend_users))
		{
			$recommend_users = array();
		}

		return array_merge($recommend_users, $recommend_topics);
	}

	public function sidebar_hot_topics($category_id = 0)
	{
		$num = S::get_int('recommend_users_number');
		if ($num)
		{
			return $this->model('topic')->get_hot_topics($category_id, $num, 'week');
		}
	}

}