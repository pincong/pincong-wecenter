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
	public function get_topic_list($where, $order, $page, $per_page)
	{
		$topic_list = $this->fetch_page('topic', $where, $order, $page, $per_page);

		return $topic_list;
	}

	public function get_focus_topic_list($uid, $page, $per_page)
	{
		if (!$uid)
		{
			return false;
		}

		if (!$topic_ids = $this->fetch_column('topic_focus', 'topic_id', ['uid', 'eq', $uid, 'i'], 'add_time DESC', $page, $per_page))
		{
			return false;
		}

		$topic_list = $this->fetch_all('topic', ['topic_id', 'in', $topic_ids, 'i'], 'discuss_count DESC');

		return $topic_list;
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
		$topic_id = intval($topic_id);

		if (!$topic_id)
		{
			return false;
		}

		if (!$topics[$topic_id])
		{
			$topics[$topic_id] = $this->fetch_row('topic', ['topic_id', 'eq', $topic_id, 'i']);
		}

		return $topics[$topic_id];
	}

	public function get_merged_topic_ids_by_id($topic_id)
	{
		return $this->fetch_column('topic_merge', 'source_id', ['target_id', 'eq', $topic_id, 'i']);
	}

	public function merge_topic($source_id, $target_id, $uid)
	{
		if ($this->count('topic', [['topic_id', 'eq', $source_id, 'i'], ['merged_id', 'eq', 0]]))
		{
			$this->update('topic', array(
				'merged_id' => intval($target_id)
			), ['topic_id', 'eq', $source_id, 'i']);

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
		), ['topic_id', 'eq', $source_id, 'i']);

		return $this->delete('topic_merge', [['source_id', 'eq', $source_id, 'i'], ['target_id', 'eq', $target_id, 'i']]);
	}

	public function get_topics_by_ids($topic_ids)
	{
		if (!is_array($topic_ids) OR count($topic_ids) < 1)
		{
			return false;
		}

		$topics = $this->fetch_all('topic', ['topic_id', 'in', $topic_ids, 'i']);

		foreach ($topics AS $key => $val)
		{
			$result[$val['topic_id']] = $val;
		}

		return $result;
	}

	public function get_topic_by_title($topic_title)
	{
		if ($topic_id = $this->fetch_one('topic', 'topic_id', ['topic_title', 'eq', htmlspecialchars($topic_title), 's']))
		{
			return $this->get_topic_by_id($topic_id);
		}
	}

	public function get_topic_id_by_title($topic_title)
	{
		return $this->fetch_one('topic', 'topic_id', ['topic_title', 'eq', htmlspecialchars($topic_title), 's']);
	}

	public function save_topic($topic_title, $uid = null, $auto_create = true, $topic_description = null)
	{
		if (!$topic_id = $this->get_topic_id_by_title($topic_title) AND $auto_create)
		{
			$topic_id = $this->insert('topic', array(
				'topic_title' => htmlspecialchars($topic_title),
				'add_time' => fake_time(),
				'topic_description' => htmlspecialchars($topic_description),
				'topic_lock' => 0
			));
		}
		else
		{
			$this->update_discuss_count($topic_id);
		}

		return $topic_id;
	}

	public function update_topic($uid, $topic_id, $topic_title = null, $topic_description = null, $topic_pic = null)
	{
		if (!$topic_info = $this->get_topic_by_id($topic_id))
		{
			return false;
		}

		if ($topic_title)
		{
			$data['topic_title'] = htmlspecialchars($topic_title);
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
			$this->update('topic', $data, ['topic_id', 'eq', $topic_id, 'i']);
		}

		return TRUE;
	}

	/**
	 *
	 * 锁定/解锁话题
	 * @param int $topic_id
	 * @param int $status
	 *
	 * @return boolean true|false
	 */
	public function lock_topic_by_id($topic_id, $status)
	{
		return $this->update('topic', array(
			'topic_lock' => intval(!!$status)
		), ['topic_id', 'eq', $topic_id, 'i']);

	}

	public function has_lock_topic($topic_id)
	{
		$topic_info = $this->get_topic_by_id($topic_id);

		return $topic_info['topic_lock'];
	}

	public function add_focus_topic($uid, $topic_id)
	{
		$where = ['topic_id', 'eq', $topic_id, 'i'];

		if (! $this->has_focus_topic($uid, $topic_id))
		{
			if ($this->insert('topic_focus', array(
				"topic_id" => intval($topic_id),
				"uid" => intval($uid),
				"add_time" => fake_time()
			)))
			{
				$this->update('topic', '`focus_count` = `focus_count` + 1', $where);
			}

			$result = 'add';

		}
		else
		{
			if ($this->delete_focus_topic($topic_id, $uid))
			{
				$this->update('topic', '`focus_count` = `focus_count` - 1', $where);
			}

			$result = 'remove';
		}

		return $result;
	}

	public function delete_focus_topic($topic_id, $uid)
	{
		return $this->delete('topic_focus', [['uid', 'eq', $uid, 'i'], ['topic_id', 'eq', $topic_id, 'i']]);
	}

	public function has_focus_topic($uid, $topic_id)
	{
		return $this->fetch_one('topic_focus', 'focus_id', [['uid', 'eq', $uid, 'i'], ['topic_id', 'eq', $topic_id, 'i']]);
	}

	public function has_focus_topics($uid, $topic_ids)
	{
		if (!is_array($topic_ids) OR count($topic_ids) < 1)
		{
			return false;
		}

		$where = [
			['uid', 'eq', $uid, 'i'],
			['topic_id', 'in', $topic_ids, 'i']
		];

		if ($focus = $this->fetch_all('topic_focus', $where))
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
			'discuss_count' => $this->count('topic_relation', ['topic_id', 'eq', $topic_id, 'i']),
			'discuss_count_last_week' => $this->count('topic_relation', [['add_time', 'gt', time() - 604800], ['topic_id', 'eq', $topic_id, 'i']]),
			'discuss_count_last_month' => $this->count('topic_relation', [['add_time', 'gt', time() - 2592000], ['topic_id', 'eq', $topic_id, 'i']]),
			'discuss_count_update' => intval($this->fetch_one('topic_relation', 'add_time', ['topic_id', 'eq', $topic_id, 'i'], 'add_time DESC'))
		), ['topic_id', 'eq', $topic_id, 'i']);
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
					@unlink(S::get('upload_dir') . '/topic/' . $this->get_image_path($topic_id, $key));
				}
			}

			$this->delete('topic_focus', ['topic_id', 'eq', $topic_id, 'i']);
			$this->delete('topic_relation', ['topic_id', 'eq', $topic_id, 'i']);
			$this->delete('related_topic', [['topic_id', 'eq', $topic_id, 'i'], 'or', ['related_id', 'eq', $topic_id, 'i']]);
			$this->delete('topic', ['topic_id', 'eq', $topic_id, 'i']);
		}

		return true;
	}


	/**
	 * 获取热门话题
	 * @param  $category
	 * @param  $limit
	 */
	public function get_hot_topics($category_id = 0, $limit = 5, $section = null)
	{
		if ($category_id)
		{
			$question_ids = $this->fetch_column('question', 'id', ['category_id', 'eq', $category_id, 'i'], 'add_time DESC', 200);

			if (!$question_ids)
			{
				return false;
			}

			if (!$topic_relation = $this->fetch_all('topic_relation', [['item_id', 'in', $question_ids, 'i'], ['type', 'eq', 'question']]))
			{
				return false;
			}

			foreach ($topic_relation AS $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}

			$where[] = ['topic_id', 'in', $topic_ids, 'i'];
		}

		switch ($section)
		{
			default:
				return $this->fetch_all('topic', $where, 'discuss_count DESC', $limit);
			break;

			case 'week':
				$where[] = ['discuss_count_update', 'gt', time() - 604801];

				return $this->fetch_all('topic', $where, 'discuss_count_last_week DESC', $limit);
			break;

			case 'month':
				$where[] = ['discuss_count_update', 'gt', time() - 2592001];

				return $this->fetch_all('topic', $where, 'discuss_count_last_month DESC', $limit);
			break;
		}
	}

	public function save_related_topic($topic_id, $related_id)
	{
		if (!$related_topic = $this->fetch_row('related_topic', [['topic_id', 'eq', $topic_id, 'i'], ['related_id', 'eq', $related_id, 'i']]))
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
		return $this->delete('related_topic', [['topic_id', 'eq', $topic_id, 'i'], ['related_id', 'eq', $related_id, 'i']]);
	}

	public function get_related_topics($topic_id)
	{
		$topic_ids = $this->fetch_column('related_topic', 'related_id', ['topic_id', 'eq', $topic_id, 'i']);

		if ($topic_ids)
		{
			return $this->get_topics_by_ids($topic_ids);
		}
	}

	public function get_topic_ids_by_item_id($item_id, $type)
	{
		return $this->fetch_column('topic_relation', 'topic_id', [['item_id', 'eq', $item_id, 'i'], ['type', 'eq', $type, 's']]);
	}


	public function has_thread_topic($item_type, $item_id, $topic_id)
	{
		$where = [
			['type', 'eq', $item_type, 's'],
			['item_id', 'eq', $item_id, 'i'],
			['topic_id', 'eq', $topic_id, 'i'],
		];

		return !!$this->fetch_one('topic_relation', 'id', $where);
	}

	public function add_thread_topic($item_type, $item_id, $topic_id, $log_uid)
	{
		if (!$this->model('post')->check_thread_type($item_type))
		{
			return false;
		}

		if ($this->has_thread_topic($item_type, $item_id, $topic_id))
		{
			return;
		}

		$insert_id = $this->insert('topic_relation', array(
			'type' => $item_type,
			'item_id' => intval($item_id),
			'topic_id' => intval($topic_id),
			'add_time' => fake_time(),
		));

		if ($insert_id)
		{
			$this->model('topic')->update_discuss_count($topic_id);
			$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '添加话题', $log_uid, 'topic', $topic_id);
		}

		return $insert_id;
	}

	public function remove_thread_topic($item_type, $item_id, $topic_id, $log_uid)
	{
		if (!$this->model('post')->check_thread_type($item_type))
		{
			return false;
		}

		if (!$this->has_thread_topic($item_type, $item_id, $topic_id))
		{
			return;
		}

		$this->delete('topic_relation', [
			['type', 'eq', $item_type, 's'],
			['item_id', 'eq', $item_id, 'i'],
			['topic_id', 'eq', $topic_id, 'i'],
		]);

		$this->model('content')->log($item_type, $item_id, $item_type, $item_id, '移除话题', $log_uid, 'topic', $topic_id);
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

		$local_upload_dir = S::get('upload_dir');
		$save_dir = $local_upload_dir . '/topic/' . $this->get_image_dir($id);
		$filename = $this->get_image_filename($id, 'real');

		AWS_APP::upload()->initialize(array(
			'allowed_types' => S::get('allowed_upload_types'),
			'upload_path' => $save_dir,
			'is_image' => TRUE,
			'max_size' => S::get('upload_size_limit'),
			'file_name' => $filename,
			'encrypt_name' => FALSE
		))->do_upload($field);

		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				case 'upload_invalid_filetype':
					$error = _t('文件类型无效');
					return false;

				case 'upload_invalid_filesize':
					$error = _t('文件尺寸过大, 最大允许尺寸为 %s KB', S::get('upload_size_limit'));
					return false;

				default:
					$error = _t('错误代码: %s', AWS_APP::upload()->get_error());
					return false;
			}
		}

		if (! $upload_data = AWS_APP::upload()->data())
		{
			$error = _t('上传失败');
			return false;
		}

		if ($upload_data['is_image'] != 1)
		{
			$error = _t('文件类型错误');
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
				$error = _t('保存失败');
				return false;
			}
		}

		$this->update_topic(null, $id, null, null, random_string(4)); // 生成随机字符串

		return true;
	}


}
