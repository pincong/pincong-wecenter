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

class topic_class extends AWS_MODEL
{
	public function get_topic_list($where = null, $order = 'topic_id DESC', $limit = 10, $page = null)
	{
		$topic_list = $this->fetch_page('topic', $where, $order, $page, $limit);

		return $topic_list;
	}

	public function get_focus_topic_list($uid, $limit = 20)
	{
		if (!$uid)
		{
			return false;
		}

		if (!$focus_topics = $this->fetch_all('topic_focus', 'uid = ' . intval($uid)))
		{
			return false;
		}

		foreach ($focus_topics AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		$topic_list = $this->fetch_all('topic', 'topic_id IN(' . implode(',', $topic_ids) . ')', 'discuss_count DESC', $limit);

		return $topic_list;
	}

	public function get_focus_topic_ids_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}

		if (!$topic_focus = $this->fetch_all('topic_focus', "uid = " . intval($uid)))
		{
			return false;
		}

		foreach ($topic_focus as $key => $val)
		{
			$topic_ids[$val['topic_id']] = $val['topic_id'];
		}

		return $topic_ids;
	}

	/**
	 *
	 * 得到单条话题内容
	 * @param int $topic_id 话题ID
	 *
	 * @return array
	 */
	public function get_topic_by_id($topic_id)
	{
		static $topics;

		if (! $topic_id)
		{
			return false;
		}

		if (! $topics[$topic_id])
		{
			$topics[$topic_id] = $this->fetch_row('topic', 'topic_id = ' . intval($topic_id));
		}

		return $topics[$topic_id];
	}

	public function get_merged_topic_ids($topic_id)
	{
		return $this->fetch_all('topic_merge', 'target_id = ' . intval($topic_id));
	}

	public function merge_topic($source_id, $target_id, $uid)
	{
		if ($this->count('topic', 'topic_id = ' . intval($source_id) . ' AND merged_id = 0'))
		{
			$this->update('topic', array(
				'merged_id' => intval($target_id)
			), 'topic_id = ' . intval($source_id));

			return $this->insert('topic_merge', array(
				'source_id' => intval($source_id),
				'target_id' => intval($target_id),
				'uid' => intval($uid),
				'time' => fake_time()
			));
		}

		return false;
	}

	public function remove_merge_topic($source_id, $target_id)
	{
		$this->update('topic', array(
			'merged_id' => 0
		), 'topic_id = ' . intval($source_id));

		return $this->delete('topic_merge', 'source_id = ' . intval($source_id) . ' AND target_id = ' . intval($target_id));
	}

	public function get_topics_by_ids($topic_ids)
	{
		if (!$topic_ids OR !is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$topics = $this->fetch_all('topic', 'topic_id IN(' . implode(',', $topic_ids) . ')');

		foreach ($topics AS $key => $val)
		{
			$result[$val['topic_id']] = $val;
		}

		return $result;
	}

	public function get_topic_by_title($topic_title)
	{
		if ($topic_id = $this->fetch_one('topic', 'topic_id', "topic_title = '" . $this->quote(htmlspecialchars($topic_title)) . "'"))
		{
			return $this->get_topic_by_id($topic_id);
		}
	}

	public function get_topic_id_by_title($topic_title)
	{
		return $this->fetch_one('topic', 'topic_id', "topic_title = '" . $this->quote(htmlspecialchars($topic_title)) . "'");
	}

	public function save_topic($topic_title, $uid = null, $auto_create = true, $topic_description = null)
	{
		$topic_title = str_replace(array('-', '/'), '_', $topic_title);

		if (!$topic_id = $this->get_topic_id_by_title($topic_title) AND $auto_create)
		{
			$topic_id = $this->insert('topic', array(
				'topic_title' => htmlspecialchars($topic_title),
				'add_time' => fake_time(),
				'topic_description' => htmlspecialchars($topic_description),
				'topic_lock' => 0
			));

			// TODO: 在管理后台添加选项
			// 创建者不再自动关注话题
			//if ($uid)
			//{
			//	$this->add_focus_topic($uid, $topic_id);
			//}
		}
		else
		{
			$this->update_discuss_count($topic_id);
		}

		return $topic_id;
	}

	public function remove_topic_relation($uid, $topic_id, $item_id, $type)
	{
		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		return $this->delete('topic_relation', 'topic_id = ' . intval($topic_id) . ' AND item_id = ' . intval($item_id) . " AND `type` = '" . $this->quote($type) . "'");
	}

	public function update_topic($uid, $topic_id, $topic_title = null, $topic_description = null, $topic_pic = null)
	{
		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($topic_title)
		{
			$data['topic_title'] = htmlspecialchars(trim($topic_title));
		}

		if ($topic_description)
		{
			$data['topic_description'] = htmlspecialchars($topic_description);
		}
		else
		{
			$data['topic_description'] = null;
		}

		if ($topic_pic)
		{
			$data['topic_pic'] = htmlspecialchars($topic_pic);
		}

		if ($data)
		{
			$this->update('topic', $data, 'topic_id = ' . intval($topic_id));
		}

		return TRUE;
	}

	/**
	 *
	 * 锁定/解锁话题
	 * @param int $topic_id
	 * @param int $topic_lock
	 *
	 * @return boolean true|false
	 */
	public function lock_topic_by_ids($topic_ids, $topic_lock = 0)
	{
		if (!$topic_ids)
		{
			return false;
		}

		if (!is_array($topic_ids))
		{
			$topic_ids = array(
				$topic_ids,
			);
		}

		array_walk_recursive($topic_ids, 'intval_string');

		return $this->update('topic', array(
			'topic_lock' => $topic_lock
		), 'topic_id IN (' . implode(',', $topic_ids) . ')');

	}

	public function has_lock_topic($topic_id)
	{
		$topic_info = $this->get_topic_by_id($topic_id);

		return $topic_info['topic_lock'];
	}

	public function add_focus_topic($uid, $topic_id)
	{
		if (! $this->has_focus_topic($uid, $topic_id))
		{
			if ($this->insert('topic_focus', array(
				"topic_id" => intval($topic_id),
				"uid" => intval($uid),
				"add_time" => fake_time()
			)))
			{
				$this->query('UPDATE ' . $this->get_table('topic') . " SET focus_count = focus_count + 1 WHERE topic_id = " . intval($topic_id));
			}

			$result = 'add';

		}
		else
		{
			if ($this->delete_focus_topic($topic_id, $uid))
			{
				$this->query('UPDATE ' . $this->get_table('topic') . " SET focus_count = focus_count - 1 WHERE topic_id = " . intval($topic_id));
			}

			$result = 'remove';
		}

		// 更新个人计数
		$focus_count = $this->count('topic_focus', 'uid = ' . intval($uid));

		$this->model('account')->update_user_fields(array(
			'topic_focus_count' => $focus_count
		), $uid);

		return $result;
	}

	public function delete_focus_topic($topic_id, $uid)
	{
		return $this->delete('topic_focus', 'uid = ' . intval($uid) . ' AND topic_id = ' . intval($topic_id));
	}

	public function has_focus_topic($uid, $topic_id)
	{
		return $this->fetch_one('topic_focus', 'focus_id', "uid = " . intval($uid) . " AND topic_id = " . intval($topic_id));
	}

	public function has_focus_topics($uid, $topic_ids)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		if ($focus = $this->query_all('SELECT focus_id, topic_id FROM ' . $this->get_table('topic_focus') . " WHERE uid = " . intval($uid) . " AND topic_id IN(" . implode(',', $topic_ids) . ")"))
		{
			foreach ($focus as $key => $val)
			{
				$result[$val['topic_id']] = $val['focus_id'];
			}
		}

		return $result;
	}

	public function update_discuss_count($topic_id)
	{
		if (! $topic_id)
		{
			return false;
		}

		$this->update('topic', array(
			'discuss_count' => $this->count('topic_relation', 'topic_id = ' . intval($topic_id)),
			'discuss_count_last_week' => $this->count('topic_relation', 'add_time > ' . (time() - 604800) . ' AND topic_id = ' . intval($topic_id)),
			'discuss_count_last_month' => $this->count('topic_relation', 'add_time > ' . (time() - 2592000) . ' AND topic_id = ' . intval($topic_id)),
			'discuss_count_update' => intval($this->fetch_one('topic_relation', 'add_time', 'topic_id = ' . intval($topic_id), 'add_time DESC'))
		), 'topic_id = ' . intval($topic_id));
	}

	/**
	 * 物理删除话题及其关联的图片等
	 *
	 * @param  $topic_id
	 */
	public function remove_topic_by_ids($topic_id)
	{
		if (!$topic_id)
		{
			return false;
		}

		if (is_array($topic_id))
		{
			$topic_ids = $topic_id;
		}
		else
		{
			$topic_ids[] = $topic_id;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		foreach($topic_ids as $topic_id)
		{
			if (!$topic_info = $this->get_topic_by_id($topic_id))
			{
				continue;
			}

			if ($topic_info['topic_pic'])
			{
				foreach (AWS_APP::config()->get('image')->topic_thumbnail as $key => $val)
				{
					@unlink(get_setting('upload_dir') . '/topic/' . $this->get_image_path($topic_id, $key));
				}
			}

			$this->delete('topic_focus', 'topic_id = ' . intval($topic_id));
			$this->delete('topic_relation', 'topic_id = ' . intval($topic_id));
			$this->delete('related_topic', 'topic_id = ' . intval($topic_id) . ' OR related_id = ' . intval($topic_id));
			$this->delete('topic', 'topic_id = ' . intval($topic_id));
		}

		return true;
	}

	public function get_focus_users_by_topic($topic_id, $limit = 10)
	{
		$user_list = array();

		$uids = $this->query_all("SELECT DISTINCT uid FROM " . $this->get_table('topic_focus') . " WHERE topic_id = " . intval($topic_id), $limit);

		if ($uids)
		{
			$user_list_query = $this->model('account')->get_user_info_by_uids(fetch_array_value($uids, 'uid'));

			if ($user_list_query)
			{
				foreach ($user_list_query AS $user_info)
				{
					$user_list[$user_info['uid']]['uid'] = $user_info['uid'];

					$user_list[$user_info['uid']]['user_name'] = $user_info['user_name'];

					$user_list[$user_info['uid']]['avatar_file'] = UF::avatar($user_info, 'mid');

					$user_list[$user_info['uid']]['url'] = url_rewrite('/people/' . $user_info['url_token']);
				}
			}
		}

		return $user_list;
	}

	public function get_item_ids_by_topics_id($topic_id, $type = null, $limit = null)
	{
		return $this->get_item_ids_by_topics_ids(array(
			$topic_id
		), $type, $limit);
	}

	public function get_item_ids_by_topics_ids($topic_ids, $type = null, $limit = null)
	{
		if (!is_array($topic_ids))
		{
			return false;
		}

		array_walk_recursive($topic_ids, 'intval_string');

		$where[] = 'topic_id IN(' . implode(',', $topic_ids) . ')';

		if ($type)
		{
			$where[] = "`type` = '" . $this->quote($type) . "'";
		}

		if ($result = $this->query_all("SELECT item_id FROM " . $this->get_table('topic_relation') . " WHERE " . implode(' AND ', $where), $limit))
		{
			foreach ($result AS $key => $val)
			{
				$item_ids[] = $val['item_id'];
			}
		}

		return $item_ids;
	}

	/**
	 * 获取热门话题
	 * @param  $category
	 * @param  $limit
	 */
	public function get_hot_topics($category_id = 0, $limit = 5, $section = null)
	{
		$where = array();

		if ($category_id)
		{
			if ($questions = $this->query_all("SELECT id FROM " . get_table('question') . " WHERE category_id =" . intval($category_id) . ' ORDER BY add_time DESC LIMIT 200'))
			{
				foreach ($questions AS $key => $val)
				{
					$question_ids[] = $val['id'];
				}
			}

			if (!$question_ids)
			{
				return false;
			}

			if (!$topic_relation = $this->fetch_all('topic_relation', 'item_id IN(' . implode(',', $question_ids) . ") AND `type` = 'question'"))
			{
				return false;
			}

			foreach ($topic_relation AS $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}

			$where[] = 'topic_id IN(' . implode(',', $topic_ids) . ')';
		}

		switch ($section)
		{
			default:
				return $this->fetch_all('topic', implode(' AND ', $where), 'discuss_count DESC', $limit);
			break;

			case 'week':
				$where[] = 'discuss_count_update > ' . (time() - 604801);

				return $this->fetch_all('topic', implode(' AND ', $where), 'discuss_count_last_week DESC', $limit);
			break;

			case 'month':
				$where[] = 'discuss_count_update > ' . (time() - 2592001);

				return $this->fetch_all('topic', implode(' AND ', $where), 'discuss_count_last_month DESC', $limit);
			break;
		}
	}

	public function save_related_topic($topic_id, $related_id)
	{
		$this->pre_save_auto_related_topics($topic_id);

		if (! $related_topic = $this->fetch_row('related_topic', 'topic_id = ' . intval($topic_id) . ' AND related_id = ' . intval($related_id)))
		{
			return $this->insert('related_topic', array(
				'topic_id' => intval($topic_id),
				'related_id' => intval($related_id)
			));
		}

		return false;
	}

	public function remove_related_topic($topic_id, $related_id)
	{
		$this->pre_save_auto_related_topics($topic_id);

		return $this->delete('related_topic', 'topic_id = ' . intval($topic_id) . ' AND related_id = ' . intval($related_id));
	}

	public function pre_save_auto_related_topics($topic_id)
	{
		if (! $this->is_user_related($topic_id))
		{
			if ($auto_related_topics = $this->get_auto_related_topics($topic_id))
			{
				foreach ($auto_related_topics as $key => $val)
				{
					$this->insert('related_topic', array(
						'topic_id' => intval($topic_id),
						'related_id' => $val['topic_id']
					));
				}
			}

			$this->update('topic', array(
				'user_related' => 1
			), 'topic_id = ' . intval($topic_id));
		}
	}

	public function get_related_topics($topic_id)
	{
		if ($related_topic = $this->fetch_all('related_topic', 'topic_id = ' . intval($topic_id)))
		{
			foreach ($related_topic as $key => $val)
			{
				$topic_ids[] = $val['related_id'];
			}
		}

		if ($topic_ids)
		{
			return $this->get_topics_by_ids($topic_ids);
		}
	}

	public function get_auto_related_topics($topic_id)
	{
		if (! $question_ids = $this->get_item_ids_by_topics_id($topic_id, 'question', 10))
		{
			return false;
		}

		if ($question_ids)
		{
			if ($topics = $this->model('question')->get_question_topic_by_questions($question_ids, 10))
			{
				foreach ($topics as $key => $val)
				{
					if ($val['topic_id'] == $topic_id)
					{
						unset($topics[$key]);
					}
				}

				return $topics;
			}
		}
	}

	public function related_topics($topic_id)
	{
		if ($this->is_user_related($topic_id))
		{
			$related_topic = $this->get_related_topics($topic_id);
		}
		else
		{
			$related_topic = $this->get_auto_related_topics($topic_id);
		}

		return $related_topic;
	}

	public function is_user_related($topic_id)
	{
		$topic = $this->get_topic_by_id($topic_id);

		return $topic['user_related'];
	}

	public function save_topic_relation($uid, $topic_id, $item_id, $type)
	{
		if (!$topic_id OR !$item_id OR !$type)
		{
			return false;
		}

		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($flag = $this->check_topic_relation($topic_id, $item_id, $type))
		{
			return $flag;
		}

		$this->model('account')->save_recent_topics($uid, $topic_info['topic_title']);

		$insert_id = $this->insert('topic_relation', array(
			'topic_id' => intval($topic_id),
			'item_id' => intval($item_id),
			'add_time' => fake_time(),
			'uid' => intval($uid),
			'type' => $type
		));

		$this->model('topic')->update_discuss_count($topic_id);

		return $insert_id;
	}

	public function check_topic_relation($topic_id, $item_id, $type)
	{
		$where[] = 'topic_id = ' . intval($topic_id);
		$where[] = "`type` = '" . $this->quote($type) . "'";

		if ($item_id)
		{
			$where[] = 'item_id = ' . intval($item_id);
		}

		return $this->fetch_one('topic_relation', 'id', implode(' AND ', $where));
	}

	public function get_topics_by_item_id($item_id, $type)
	{
		$result = $this->get_topics_by_item_ids(array(
			$item_id
		), $type);

		return $result[$item_id];
	}

	public function get_topics_by_item_ids($item_ids, $type)
	{
		if (!is_array($item_ids) OR sizeof($item_ids) == 0)
		{
			return false;
		}

		array_walk_recursive($item_ids, 'intval_string');

		if (!$item_topics = $this->fetch_all('topic_relation', "item_id IN(" . implode(',', $item_ids) . ") AND `type` = '" . $this->quote($type) . "'"))
		{
			foreach ($item_ids AS $item_id)
			{
				if (!$result[$item_id])
				{
					$result[$item_id] = array();
				}
			}

			return $result;
		}

		foreach ($item_topics AS $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}

		$topics_info = $this->model('topic')->get_topics_by_ids($topic_ids);

		foreach ($item_topics AS $key => $val)
		{
			$result[$val['item_id']][] = $topics_info[$val['topic_id']];
		}

		foreach ($item_ids AS $item_id)
		{
			if (!$result[$item_id])
			{
				$result[$item_id] = array();
			}
		}

		return $result;
	}

	public function get_related_topic_ids_by_id($topic_id)
	{
		if (!$topic_info = $this->model('topic')->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
		{
			$merged_topic_info = $this->model('topic')->get_topic_by_id($topic_info['merged_id']);

			if ($merged_topic_info)
			{
				$topic_info = $merged_topic_info;
			}
		}

		$related_topics_ids = array();

		$related_topics = $this->model('topic')->related_topics($topic_info['topic_id']);

		if ($related_topics)
		{
			foreach ($related_topics AS $related_topic)
			{
				$related_topics_ids[$related_topic['topic_id']] = $related_topic['topic_id'];
			}
		}

		$contents_topic_id = $topic_info['topic_id'];

		$merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']);

		if ($merged_topics)
		{
			foreach ($merged_topics AS $merged_topic)
			{
				$merged_topic_ids[] = $merged_topic['source_id'];
			}

			$contents_topic_id .= ',' . implode(',', $merged_topic_ids);
		}

		return array_merge($related_topics_ids, explode(',', $contents_topic_id));
	}


	// e.g. $id=12345, return '000/01/23/'
    public function get_image_dir($id)
    {
        $id = abs(intval($id));
        $id = sprintf('%\'09d', $id);
        $dir1 = substr($id, 0, 3);
        $dir2 = substr($id, 3, 2);
        $dir3 = substr($id, 5, 2);

        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
    }

	// e.g. $id=12345, return '45_topic_min.jpg'
    public function get_image_filename($id, $size = 'min')
    {
        $id = abs(intval($id));
        $id = sprintf('%\'09d', $id);

        return substr($id, -2) . '_topic_' . $size . '.jpg';
    }

	// e.g. $id=12345, return '000/01/23/45_topic_min.jpg'
    public function get_image_path($id, $size = 'min')
    {
        $id = abs(intval($id));
        $id = sprintf('%\'09d', $id);
        $dir1 = substr($id, 0, 3);
        $dir2 = substr($id, 3, 2);
        $dir3 = substr($id, 5, 2);

        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($id, -2) . '_topic_' . $size . '.jpg';
    }


	public function upload_image($field, $id, &$error)
	{
		$id = intval($id);
		if ($id <= 0)
		{
			return false;
		}

		$local_upload_dir = get_setting('upload_dir');
		$save_dir = $local_upload_dir . '/topic/' . $this->get_image_dir($id);
		$filename = $this->get_image_filename($id, 'real');

		AWS_APP::upload()->initialize(array(
			'allowed_types' => get_setting('allowed_upload_types'),
			'upload_path' => $save_dir,
			'is_image' => TRUE,
			'max_size' => get_setting('upload_size_limit'),
			'file_name' => $filename,
			'encrypt_name' => FALSE
		))->do_upload($field);

		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				case 'upload_invalid_filetype':
					$error = AWS_APP::lang()->_t('文件类型无效');
					return false;

				case 'upload_invalid_filesize':
					$error = AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'));
					return false;

				default:
					$error = AWS_APP::lang()->_t('错误代码: %s', AWS_APP::upload()->get_error());
					return false;
			}
		}

		if (! $upload_data = AWS_APP::upload()->data())
		{
			$error = AWS_APP::lang()->_t('上传失败');
			return false;
		}

		if ($upload_data['is_image'] != 1)
		{
			$error = AWS_APP::lang()->_t('文件类型错误');
			return false;
		}

		foreach(AWS_APP::config()->get('image')->topic_thumbnail AS $key => $val)
		{
			$result = AWS_APP::image()->initialize(array(
				'local_upload_dir' => $local_upload_dir,
				'quality' => 90,
				'source_image' => $save_dir . $filename,
				'new_image' => $save_dir . $this->get_image_filename($id, $key),
				'width' => $val['w'],
				'height' => $val['h']
			))->resize();

			if ($result == false)
			{
				$error = AWS_APP::lang()->_t('保存失败');
				return false;
			}
		}

		$this->update_topic(null, $id, null, null, fetch_salt(4)); // 生成随机字符串

		return true;
	}


}
