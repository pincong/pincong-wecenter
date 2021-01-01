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

class threadindex_class extends AWS_MODEL
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
					$result = $this->fetch_row('question', ['id', 'eq', $post_id, 'i']);
					break;

				case 'article':
					$result = $this->fetch_row('article', ['id', 'eq', $post_id, 'i']);
					break;

				case 'video':
					$result = $this->fetch_row('video', ['id', 'eq', $post_id, 'i']);
					break;
			}

			if (!$result)
			{
				return false;
			}
		}

		switch ($post_type)
		{
			case 'question':
			case 'article':
			case 'video':
				break;

			default:
				return false;
		}

		$data = array(
			'add_time' => $result['add_time'],
			'update_time' => $result['update_time'],
			'uid' => $result['uid'],
			'category_id' => $result['category_id'],
			'view_count' => $result['view_count'],
			'reply_count' => $result['reply_count'],
			'agree_count' => $result['agree_count'],
			'lock' => $result['lock'],
			'recommend' => $result['recommend'],
		);

		if (!$post_data AND S::get('time_blurring') != 'N')
		{
			// 用于模糊时间的排序
			$last_update_time = $this->get_last_update_time();
			$update_time = intval($data['update_time']);
			if ($last_update_time >= $update_time AND $last_update_time < $update_time + 36 * 3600) // 如果模糊的 $last_update_time 超出36小时则放弃
			{
				$data['update_time'] = $last_update_time + 1;
			}
		}

		if ($posts_index = $this->fetch_all('posts_index', [['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]]))
		{
			$post_index = end($posts_index);

			$this->update('posts_index', $data, ['id', 'eq', $post_index['id'], 'i']);

			if (sizeof($posts_index) > 1)
			{
				$this->delete('posts_index', [['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type], ['id', 'notEq', $post_index['id'], 'i']]);
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
		return $this->delete('posts_index', [['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]]);
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

		foreach ($posts_index as $data)
		{
			$info[$data['post_type']][$data['post_id']] = $data['post_id'];
			$data_list_uids[$data['uid']] = $data['uid'];
		}

		if (!$info)
		{
			return false;
		}

		foreach ($info as $type => $ids)
		{
			$threads = $this->model('post')->get_threads_by_ids($type, $ids);
			if (!$threads)
			{
				$threads = array();
			}
			foreach ($threads as $val)
			{
				$data_list_uids[$val['last_uid']] = $val['last_uid'];
			}
			$info[$type] = $threads;
		}

		$users = $this->model('account')->get_user_info_by_uids($data_list_uids);
		if (!$users)
		{
			$users = array();
		}

		$push_reputation = S::get('push_reputation');
		$category = (S::get('category_enable') != 'N');

		foreach ($posts_index as $key => $data)
		{
			$explore_list_data[$key] = $info[$data['post_type']][$data['post_id']];
			$explore_list_data[$key]['last_user_info'] = $users[$explore_list_data[$key]['last_uid']] ?? null;
			$explore_list_data[$key]['user_info'] = $users[$data['uid']] ?? null;
			$explore_list_data[$key]['post_type'] = $data['post_type'];
			$explore_list_data[$key]['children_reputation'] = $data['reputation'];

			$explore_list_data[$key]['hot'] = intval(is_numeric($push_reputation) AND $explore_list_data[$key]['reputation'] >= $push_reputation);

			if ($category)
			{
				$explore_list_data[$key]['category_info'] = $this->model('category')->get_category_info($data['category_id']);
			}
		}

		return $explore_list_data;
	}

	public function get_posts_list($post_type, $page = 1, $per_page = 10, $order_key = null, $category_id = null, $answer_count = null, $recommend = false)
	{
		if (!$order_key)
		{
			$order_key = 'sort DESC, update_time DESC';
		}

		if (isset($answer_count))
		{
			$answer_count = intval($answer_count);

			if ($answer_count == 0)
			{
				$where[] = ['reply_count', 'eq', 0];
			}
			else if ($answer_count > 0)
			{
				$where[] = ['reply_count', 'gte', $answer_count];
			}
		}

		if ($recommend)
		{
			$where[] = ['recommend', 'eq', 1];
		}

		if ($category_id)
		{
			$where[] = ['category_id', 'eq', $category_id, 'i'];
		}
		else
		{
			$where[] = ['category_id', 'in', $this->get_default_category_ids(), 'i'];
		}

		if ($post_type AND $this->model('post')->check_thread_type($post_type))
		{
			$where[] = ['post_type', 'eq', $post_type];
		}

		$posts_index = $this->fetch_page('posts_index', $where, $order_key, $page, $per_page);

		$this->posts_list_total = $this->total_rows();

		return $this->process_explore_list_data($posts_index);
	}

	public function get_hot_posts($post_type, $category_id = 0, $day = 30, $page = 1, $per_page = 10)
	{
		if ($day)
		{
			$add_time = strtotime('-' . $day . ' Day');
			$where[] = ['add_time', 'gt', $add_time, 'i'];
		}

		if ($post_type AND $this->model('post')->check_thread_type($post_type))
		{
			$where[] = ['post_type', 'eq', $post_type];
		}

		if ($category_id)
		{
			$where[] = ['category_id', 'eq', $category_id, 'i'];
		}

		$posts_index = $this->fetch_page('posts_index', $where, 'reputation DESC', $page, $per_page);

		$this->posts_list_total = $this->total_rows();

		return $this->process_explore_list_data($posts_index);
	}

	public function get_posts_list_by_topic_ids($post_type, $topic_ids, $page = 1, $per_page = 10)
	{
		if (!is_array($topic_ids) OR count($topic_ids) < 1)
		{
			return false;
		}

		$topic_relation_where[] = ['topic_id', 'in', $topic_ids, 'i'];

		if ($post_type AND $this->model('post')->check_thread_type($post_type))
		{
			$topic_relation_where[] = ['type', 'eq', $post_type];
		}

		$topic_relations = $this->fetch_page('topic_relation', $topic_relation_where, 'id DESC', $page, $per_page);
		if ($topic_relations)
		{
			foreach ($topic_relations AS $key => $val)
			{
				$info[$val['type']][$val['item_id']] = $val['item_id'];
			}
			$this->posts_list_total = $this->total_rows();
		}

		if (!$info)
		{
			return false;
		}

		foreach ($info AS $type => $ids)
		{
			$where[] = 'or';
			$where[] = [['post_id', 'in', $ids, 'i'], ['post_type', 'eq', $type]];
		}

		$result = $this->fetch_all('posts_index', $where, 'update_time DESC');

		return $this->process_explore_list_data($result);
	}

	public function get_related_posts_by_topic_ids($post_type, $topic_ids, $exclude_post_id = 0, $limit = 10)
	{
		if (!$this->model('post')->check_thread_type($post_type) OR !is_array($topic_ids) OR count($topic_ids) < 1)
		{
			return false;
		}

		$merged_topics = $this->fetch_rows('topic_merge', ['source_id', 'target_id'], [
			['target_id', 'in', $topic_ids, 'i'],
			'or',
			['source_id', 'in', $topic_ids, 'i']
		]);
		if ($merged_topics)
		{
			foreach ($merged_topics as $val)
			{
				$topic_ids[] = $val['source_id'];
				$topic_ids[] = $val['target_id'];
			}
			$topic_ids = array_unique($topic_ids);
		}

		$topic_relation_where[] = ['topic_id', 'in', $topic_ids, 'i'];
		$topic_relation_where[] = ['type', 'eq', $post_type];

		$post_ids = $this->fetch_column('topic_relation', 'item_id', $topic_relation_where, 'RAND()', 200);
		if (!$post_ids)
		{
			return false;
		}
		$post_ids = array_unique($post_ids);

		$where = [
			['post_id', 'notEq', $exclude_post_id, 'i'],
			['post_id', 'in', $post_ids, 'i'],
			['post_type', 'eq', $post_type],
			['category_id', 'in', $this->get_default_category_ids(), 'i'],
		];
		$result = $this->fetch_all('posts_index', $where, 'RAND()', $limit);

		return $this->process_explore_list_data($result);
	}

	public function get_recommended_posts($exclude_post_type, $exclude_post_id, $limit = 10)
	{
		$where = [
			['recommend', 'eq', 1],
			['category_id', 'in', $this->get_default_category_ids(), 'i'],
			[
				['post_type', 'notEq', $exclude_post_type, 's'],
				'or',
				['post_id', 'notEq', $exclude_post_id, 'i']
			]
		];
		$result = $this->fetch_all('posts_index', $where, 'RAND()', $limit);

		return $this->process_explore_list_data($result);
	}

	public function bring_to_top($post_id, $post_type)
	{
		$post_id = intval($post_id);

		$where = [['post_id', 'eq', $post_id, 'i'], ['post_type', 'eq', $post_type]];

		$this->update('posts_index', array(
			'update_time' => $this->get_last_update_time() + 1
		), $where);

		return true;
	}

}