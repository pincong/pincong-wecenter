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

class posts_class extends AWS_MODEL
{
	// 得到最后一次发帖/回复时间
	public function get_last_update_time()
	{
		$result = $this->fetch_row('posts_index', null, 'update_time DESC');
		if (!$result)
		{
			return 0;
		}
		return intval($result['update_time']);
	}

	// set_posts_index 现在仅在发帖/回复时调用
	public function set_posts_index($post_id, $post_type, $post_data = null)
	{
		if ($post_data)
		{
			$result = $post_data;
		}
		else
		{
			switch ($post_type)
			{
				case 'question':
					$result = $this->fetch_row('question', 'question_id = ' . intval($post_id));
					break;

				case 'article':
					$result = $this->fetch_row('article', 'id = ' . intval($post_id));
					break;

				case 'video':
					$result = $this->fetch_row('video', 'id = ' . intval($post_id));
					break;
			}

			if (!$result)
			{
				return false;
			}
		}

		switch ($post_type)
		{
			// TODO: 统一字段名称
			case 'question':
				$data = array(
					'add_time' => $result['add_time'],
					'update_time' => $result['update_time'],
					'category_id' => $result['category_id'],
					'view_count' => $result['view_count'],
					'uid' => $result['uid'],
					'agree_count' => $result['agree_count'],
					'answer_count' => $result['answer_count'],
					'lock' => $result['lock'],
					'recommend' => $result['recommend'],
				);
				break;

			case 'article':
				$data = array(
					'add_time' => $result['add_time'],
					'update_time' => $result['update_time'],
					'category_id' => $result['category_id'],
					'view_count' => $result['views'],
					'uid' => $result['uid'],
					'agree_count' => $result['agree_count'],
					'answer_count' => $result['comments'],
					'lock' => $result['lock'],
					'recommend' => $result['recommend'],
				);
				break;

			case 'video':
				$data = array(
					'add_time' => $result['add_time'],
					'update_time' => $result['update_time'],
					'category_id' => $result['category_id'],
					'view_count' => $result['view_count'],
					'uid' => $result['uid'],
					'agree_count' => $result['agree_count'],
					'answer_count' => $result['comment_count'],
					'lock' => $result['lock'],
					'recommend' => $result['recommend'],
				);
				break;

			default:
				return false;
		}

		if (!$post_data AND get_setting('time_blurring') != 'N')
		{
			// 用于模糊时间的排序
			$last_update_time = $this->get_last_update_time();
			$update_time = intval($data['update_time']);
			if ($last_update_time >= $update_time AND $last_update_time < $update_time + 36 * 3600) // 如果模糊的 $last_update_time 超出36小时则放弃
			{
				$data['update_time'] = $last_update_time + 1;
			}
		}

		if ($posts_index = $this->fetch_all('posts_index', "post_id = " . intval($post_id) . " AND post_type = '" . $this->quote($post_type) . "'"))
		{
			$post_index = end($posts_index);

			$this->update('posts_index', $data, 'id = ' . intval($post_index['id']));

			if (sizeof($posts_index) > 1)
			{
				$this->delete('posts_index', "post_id = " . intval($post_id) . " AND post_type = '" . $this->quote($post_type) . "' AND id != " . intval($post_index['id']));
			}
		}
		else
		{
			$data = array_merge($data, array(
				'post_id' => intval($post_id),
				'post_type' => $post_type
			));

			$this->remove_posts_index($post_id, $post_type);

			$this->insert('posts_index', $data);
		}
	}

	public function remove_posts_index($post_id, $post_type)
	{
		return $this->delete('posts_index', "post_id = " . intval($post_id) . " AND post_type = '" . $this->quote($post_type) . "'");
	}

	// 得到在首页显示的分类
	public function get_default_category_ids()
	{
		static $ids;
		if ($ids)
		{
			return $ids;
		}

		$categories = $this->model('category')->get_category_list();
		foreach ($categories AS $key => $val)
		{
			if (!$val['skip'])
			{
				$ids[] = $val['id'];
			}
		}

		if (!$ids)
		{
			$ids = array(0);
		}
		return $ids;
	}

	public function get_posts_list($post_type, $page = 1, $per_page = 10, $sort = null, $topic_ids = null, $category_id = null, $answer_count = null, $day = 30, $recommend = false)
	{
		$order_key = 'sort DESC, add_time DESC';

		switch ($sort)
		{
			case 'responsed':
				$answer_count = 1;

				break;

			case 'unresponsive':
				$answer_count = 0;

				break;

			case 'new' :
				$order_key = 'sort DESC, update_time DESC';

				break;
		}

		if (is_array($topic_ids))
		{
			foreach ($topic_ids AS $key => $val)
			{
				if (!$val)
				{
					unset($topic_ids[$key]);
				}
			}
		}

		if ($topic_ids)
		{
			$posts_index = $this->get_posts_list_by_topic_ids($post_type, $post_type, $topic_ids, $category_id, $answer_count, $order_key, $recommend, $page, $per_page);
		}
		else
		{
			$where = array();

			if (isset($answer_count))
			{
				$answer_count = intval($answer_count);

				if ($answer_count == 0)
				{
					$where[] = "answer_count = " . $answer_count;
				}
				else if ($answer_count > 0)
				{
					$where[] = "answer_count >= " . $answer_count;
				}
			}

			if ($recommend)
			{
				$where[] = 'recommend = 1';
			}

			if ($category_id)
			{
				$where[] = 'category_id=' . intval($category_id);
			}
			else
			{
				$where[] = '`category_id` IN(' . implode(',', $this->get_default_category_ids()) . ')';
			}

			if ($post_type)
			{
				$where[] = "post_type = '" . $this->quote($post_type) . "'";
			}

			$posts_index = $this->fetch_page('posts_index', implode(' AND ', $where), $order_key, $page, $per_page);

			$this->posts_list_total = $this->found_rows();
		}

		return $this->process_explore_list_data($posts_index);
	}

	public function get_hot_posts($post_type, $category_id = 0, $topic_ids = null, $day = 30, $page = 1, $per_page = 10)
	{
		if ($day)
		{
			$add_time = strtotime('-' . $day . ' Day');
		}

		$where[] = 'add_time > ' . intval($add_time);

		if ($post_type)
		{
			$where[] = "post_type = '" . $this->quote($post_type) . "'";
		}

		if ($category_id)
		{
			$where[] = 'category_id=' . intval($category_id);
		}

		$posts_index = $this->fetch_page('posts_index', implode(' AND ', $where), 'reputation DESC', $page, $per_page);

		$this->posts_list_total = $this->found_rows();

		return $this->process_explore_list_data($posts_index);
	}

	public function get_posts_list_total()
	{
		return $this->posts_list_total;
	}

	public function process_explore_list_data($posts_index)
	{
		if (!$posts_index)
		{
			return false;
		}

		foreach ($posts_index as $key => $data)
		{
			switch ($data['post_type'])
			{
				case 'question':
					$question_ids[] = $data['post_id'];
					break;

				case 'article':
					$article_ids[] = $data['post_id'];
					break;

				case 'video':
					$video_ids[] = $data['post_id'];
					break;

			}

			$data_list_uids[$data['uid']] = $data['uid'];
		}

		if ($question_ids)
		{
			$topic_infos['question'] = $this->model('topic')->get_topics_by_item_ids($question_ids, 'question');

			$question_infos = $this->model('question')->get_question_info_by_ids($question_ids);
			foreach ($question_infos as $key => $val)
			{
				$data_list_uids[$val['last_uid']] = $val['last_uid'];
			}
		}

		if ($article_ids)
		{
			$topic_infos['article'] = $this->model('topic')->get_topics_by_item_ids($article_ids, 'article');

			$article_infos = $this->model('article')->get_article_info_by_ids($article_ids);
			foreach ($article_infos as $key => $val)
			{
				$data_list_uids[$val['last_uid']] = $val['last_uid'];
			}
		}

		if ($video_ids)
		{
			$topic_infos['video'] = $this->model('topic')->get_topics_by_item_ids($video_ids, 'video');

			$video_infos = $this->model('video')->get_video_info_by_ids($video_ids);
			foreach ($video_infos as $key => $val)
			{
				$data_list_uids[$val['last_uid']] = $val['last_uid'];
			}
		}

		$users_info = $this->model('account')->get_user_info_by_uids($data_list_uids);

		foreach ($posts_index as $key => $data)
		{
			switch ($data['post_type'])
			{
				case 'question':
					$explore_list_data[$key] = $question_infos[$data['post_id']];
					break;

				case 'article':
					$explore_list_data[$key] = $article_infos[$data['post_id']];
					break;

				case 'video':
					$explore_list_data[$key] = $video_infos[$data['post_id']];
					break;
			}
			$explore_list_data[$key]['last_user_info'] = $users_info[$explore_list_data[$key]['last_uid']];

			$explore_list_data[$key]['user_info'] = $users_info[$data['uid']];

			$explore_list_data[$key]['post_type'] = $data['post_type'];

			if (get_setting('category_enable') != 'N')
			{
				$explore_list_data[$key]['category_info'] = $this->model('category')->get_category_info($data['category_id']);
			}

			$explore_list_data[$key]['topics'] = $topic_infos[$data['post_type']][$data['post_id']];
		}

		return $explore_list_data;
	}

	public function get_posts_list_by_topic_ids($post_type, $topic_type, $topic_ids, $category_id = null, $answer_count = null, $order_by = 'post_id DESC', $recommend = false, $page = 1, $per_page = 10)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$result_cache_key = 'posts_list_by_topic_ids_' .  md5(implode(',', $topic_ids) . $answer_count . $category_id . $order_by . $recommend . $page . $per_page . $post_type . $topic_type);

		$found_rows_cache_key = 'posts_list_by_topic_ids_found_rows_' . md5(implode(',', $topic_ids) . $answer_count . $category_id . $recommend . $per_page . $post_type . $topic_type);

		$topic_relation_where[] = '`topic_id` IN(' . implode(',', $topic_ids) . ')';

		if ($topic_type)
		{
			$topic_relation_where[] = "`type` = '" . $this->quote($topic_type) . "'";
		}

		if ($topic_relation_query = $this->query_all("SELECT `item_id`, `type` FROM " . get_table('topic_relation') . " WHERE " . implode(' AND ', $topic_relation_where)))
		{
			foreach ($topic_relation_query AS $key => $val)
			{
				$post_ids[$val['type']][$val['item_id']] = $val['item_id'];
			}
		}

		if (!$post_ids)
		{
			return false;
		}

		foreach ($post_ids AS $key => $val)
		{
			$post_id_where[] = "(post_id IN (" . implode(',', $val) . ") AND post_type = '" . $this->quote($key) . "')";
		}

		if ($post_id_where)
		{
			$where[] = '(' . implode(' OR ', $post_id_where) . ')';
		}

		if (is_digits($answer_count))
		{
			if ($answer_count == 0)
			{
				$where[] = "answer_count = " . $answer_count;
			}
			else if ($answer_count > 0)
			{
				$where[] = "answer_count >= " . $answer_count;
			}
		}

		if ($recommend)
		{
			$where[] = 'recommend = 1';
		}

		if ($post_type)
		{
			$where[] = "post_type = '" . $this->quote($post_type) . "'";
		}

		if ($category_id)
		{
			$where[] = 'category_id=' . intval($category_id);
		}

		if (!$result = AWS_APP::cache()->get($result_cache_key))
		{
			if ($result = $this->fetch_page('posts_index', implode(' AND ', $where), $order_by, $page, $per_page))
			{
				AWS_APP::cache()->set($result_cache_key, $result, get_setting('cache_level_high'));
			}
		}

		if (!$found_rows = AWS_APP::cache()->get($found_rows_cache_key))
		{
			if ($found_rows = $this->found_rows())
			{
				AWS_APP::cache()->set($found_rows_cache_key, $found_rows, get_setting('cache_level_high'));
			}
		}

		$this->posts_list_total = $found_rows;

		return $result;
	}

	public function get_recommend_posts_by_topic_ids($topic_ids)
	{
		if (!$topic_ids OR !is_array($topic_ids))
		{
			return false;
		}

		$related_topic_ids = array();

		foreach ($topic_ids AS $topic_id)
		{
			$related_topic_ids = array_merge($related_topic_ids, $this->model('topic')->get_related_topic_ids_by_id($topic_id));
		}

		if ($related_topic_ids)
		{
			$recommend_posts = $this->model('posts')->get_posts_list(null, 1, 10, null, $related_topic_ids, null, null, 30, true);
		}

		return $recommend_posts;
	}

	public function bring_to_top($uid, $post_id, $post_type)
	{
		$post_id = intval($post_id);

		$where = "post_id = " . ($post_id) . " AND post_type = '" . $this->quote($post_type) . "'";

		$this->update('posts_index', array(
			'update_time' => $this->get_last_update_time() + 1
		), $where);

		return true;
	}

}