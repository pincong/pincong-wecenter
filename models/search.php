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

class search_class extends AWS_MODEL
{
	public function get_mixed_result($types, $q, $topic_ids, $page, $limit = 20, $recommend = false)
	{
		$types = explode(',', $types);

		if (in_array('users', $types) AND !$recommend)
		{
			$result = array_merge((array)$result, (array)$this->search_users($q, $page, $limit));
		}

		if (in_array('topics', $types) AND !$recommend)
		{
			$result = array_merge((array)$result, (array)$this->search_topics($q, $page, $limit));
		}

		if (in_array('questions', $types))
		{
			$result = array_merge((array)$result, (array)$this->search_questions($q, $topic_ids, $page, $limit, $recommend));
		}

		if (in_array('articles', $types))
		{
			$result = array_merge((array)$result, (array)$this->search_articles($q, $topic_ids, $page, $limit, $recommend));
		}

		return $result;
	}

	public function search_users($q, $page, $limit = 20)
	{
		if (is_array($q) AND sizeof($q) > 1)
		{
			$where[] = "user_name = '" . $this->quote(implode(' ', $q)) . "' OR user_name = '" . $this->quote(implode('', $q)) . "'";
		}
		else
		{
			if (is_array($q))
			{
				$q = implode('', $q);
			}

			$where[] = "user_name LIKE '" . $this->quote($q) . "%'";
		}

		return $this->query_all('SELECT uid, user_name FROM ' . get_table('users') . ' WHERE ' . implode(' OR ', $where), calc_page_limit($page, $limit));
	}

	public function search_topics($q, $page, $limit = 20)
	{
		if (is_array($q))
		{
			$q = implode('', $q);
		}

		$result = $this->fetch_all('topic', "topic_title LIKE '" . $this->quote($q) . "%'", null, calc_page_limit($page, $limit));

		return $result;
	}

	public function search_questions($q, $topic_ids = null, $page = 1, $limit = 20, $recommend = false)
	{
		return $this->model('search_fulltext')->search_questions($q, $topic_ids, $page, $limit, $recommend);
	}

	public function search_articles($q, $topic_ids = null, $page = 1, $limit = 20, $recommend = false)
	{
		return $this->model('search_fulltext')->search_articles($q, $topic_ids, $page, $limit, $recommend);
	}

	public function search($q, $search_type, $page = 1, $limit = 20, $topic_ids = null, $recommend = false)
	{
		if (!$q)
		{
			return false;
		}

		$q = (array)explode(' ', str_replace('  ', ' ', trim($q)));

		foreach ($q AS $key => $val)
		{
			if (strlen($val) == 1)
			{
				unset($q[$key]);
			}
		}

		if (!$q)
		{
			return false;
		}

		if (!$search_type)
		{
			$search_type = 'users,topics,questions,articles';
		}

		$result_list = $this->get_mixed_result($search_type, $q, $topic_ids, $page, $limit, $recommend);

		if ($result_list)
		{
			foreach ($result_list as $result_info)
			{
				$result = $this->prase_result_info($result_info);

				if (is_array($result))
				{
					$data[] = $result;
				}
			}
		}

		return $data;
	}

	public function prase_result_info($result_info)
	{
		if (isset($result_info['user_name']))
		{
			$result_type = 'users';

			$search_id = $result_info['uid'];

			$user_info = $this->model('account')->get_user_info_by_uid($result_info['uid']);

			$name = $user_info['user_name'];

			$url = url_rewrite('/people/' . $user_info['url_token']);

			$detail = array(
				'avatar_file' => UF::avatar($user_info, 'mid'),	// 头像
				'signature' => $user_info['signature'],	// 签名
				'reputation' =>  intval($user_info['reputation']),	// 声望
				'agree_count' =>  $user_info['agree_count'],	// 赞同
				'fans_count' =>  $user_info['fans_count'],	// 关注数
			);
		}
		else if ($result_info['topic_id'])
		{
			$result_type = 'topics';

			$search_id = $result_info['topic_id'];

			$url = url_rewrite('/topic/' . urlencode($result_info['topic_title']));

			$name = $result_info['topic_title'];

			$detail = array(
				'topic_pic'=> get_topic_pic_url($result_info, 'mid'),
				'topic_id' => $result_info['topic_id'],	// 话题 ID
				'focus_count' => $result_info['focus_count'],
				'discuss_count' => $result_info['discuss_count'],	// 讨论数量
				'topic_description' => $result_info['topic_description']
			);
		}
		else if (isset($result_info['answer_count']))
		{
			$result_type = 'questions';

			$search_id = $result_info['id'];

			$url = url_rewrite('/question/' . $result_info['id']);

			$name = $result_info['title'];

			$detail = array(
				'answer_count' => $result_info['answer_count'],	// 回复数
				'view_count' => $result_info['view_count']
			);
		}
		else if ($result_info['id'])
		{
			$result_type = 'articles';

			$search_id = $result_info['id'];

			$url = url_rewrite('/article/' . $result_info['id']);

			$name = $result_info['title'];

			$detail = array(
				'comments' => $result_info['comments'],
				'view_count' => $result_info['view_count']
			);
		}

		if ($result_type)
		{
			return array(
				'uid' => $result_info['uid'],
				'score' => $result_info['score'],
				'type' => $result_type,
				'url' => $url,
				'search_id' => $search_id,
				'name' => $name,
				'detail' => $detail
			);
		}
	}
}