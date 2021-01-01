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

class category_class extends AWS_MODEL
{
	public function fetch_category()
	{
		$category_list = array();

		$order = 'sort DESC, id ASC';

		if ($category_all = $this->fetch_all('category', '', $order))
		{
			foreach ($category_all AS $key => $val)
			{
				$category_list[$val['id']] = $val;
			}
		}

		return $category_list;
	}

	public function get_category_list()
	{
		static $category_list;

		if (!$category_list)
		{
			$category_list = AWS_APP::cache()->get('category_list');

			if (!$category_list)
			{
				$category_list = $this->fetch_category();

				AWS_APP::cache()->set('category_list', $category_list, S::get('cache_level_low'), 'category');
			}
		}

		return $category_list;
	}

	public function get_category_info($category_id)
	{
		static $category_list;

		if (!$category_list)
		{
			$category_list = $this->get_category_list();
		}

		return $category_list[$category_id];
	}

	private function is_restricted($category_id, $restricted_ids)
	{
		if (!$restricted_ids)
		{
			return false;
		}
		$restricted_ids = array_map('intval', explode(',', $restricted_ids));
		if (in_array(intval($category_id), $restricted_ids))
		{
			return true;
		}
		return false;
	}

	public function check_change_category_permission($new_category_id, $old_category_id, $user_info)
	{
		if (!is_array($user_info) OR !is_array($user_info['permission']))
		{
			return false;
		}
		if ($this->is_restricted($new_category_id, $user_info['permission']['restricted_categories']))
		{
			return false;
		}
		if ($this->is_restricted($new_category_id, $user_info['permission']['restricted_categories_move_to']))
		{
			return false;
		}
		if ($this->is_restricted($old_category_id, $user_info['permission']['restricted_categories_move_from']))
		{
			return false;
		}
		return true;
	}

	public function check_user_permission($category_id, $user_info)
	{
		if (!is_array($user_info) OR !is_array($user_info['permission']))
		{
			return false;
		}
		return !$this->is_restricted($category_id, $user_info['permission']['restricted_categories']);
	}

	public function check_user_permission_reply($category_id, $user_info)
	{
		if (!is_array($user_info) OR !is_array($user_info['permission']))
		{
			return false;
		}
		return !$this->is_restricted($category_id, $user_info['permission']['restricted_categories_reply']);
	}


	private function unset_categories(&$category_list, $restricted_ids)
	{
		if (!$restricted_ids)
		{
			return;
		}
		$restricted_ids = explode(',', $restricted_ids);
		foreach ($restricted_ids AS $id)
		{
			$id = intval($id);
			if (!$id)
			{
				continue;
			}
			foreach ($category_list AS $key => $val)
			{
				if ($key == $id)
				{
					unset($category_list[$key]);
				}
			}
		}
	}

	public function get_allowed_categories($user_info)
	{
		if (!is_array($user_info) OR !is_array($user_info['permission']))
		{
			return array();
		}
		$category_list = $this->get_category_list();
		$this->unset_categories($category_list, $user_info['permission']['restricted_categories']);
		return $category_list;
	}

	public function get_allowed_categories_change($user_info)
	{
		if (!is_array($user_info) OR !is_array($user_info['permission']))
		{
			return array();
		}
		$category_list = $this->get_category_list();
		$this->unset_categories($category_list, $user_info['permission']['restricted_categories']);
		$this->unset_categories($category_list, $user_info['permission']['restricted_categories_move_to']);
		return $category_list;
	}

	public function update_category_info($category_id, $title, $group_id, $description = null, $skip = 0)
	{
		AWS_APP::cache()->cleanGroup('category');

		return $this->update('category', array(
			'title' => htmlspecialchars($title),
			'group_id' => intval($group_id),
			'description' => $description,
			'skip' => intval($skip)
		), ['id', 'eq', $category_id, 'i']);
	}

	public function set_category_sort($category_id, $sort)
	{
		AWS_APP::cache()->cleanGroup('category');

		return $this->update('category', array(
			'sort' => intval($sort)
		), ['id', 'eq', $category_id, 'i']);
	}

	public function add_category($title, $group_id, $description = null, $skip = 0)
	{
		AWS_APP::cache()->cleanGroup('category');

		return $this->insert('category', array(
			'title' => htmlspecialchars($title),
			'group_id' => intval($group_id),
			'description' => $description,
			'skip' => intval($skip)
		));
	}

	public function delete_category($category_id)
	{
		AWS_APP::cache()->cleanGroup('category');

		return $this->delete('category', ['id', 'eq', $category_id, 'i']);
	}

	public function category_exists($category_id)
	{
		return $this->count('category', ['id', 'eq', $category_id, 'i']);
	}

	public function contents_exists($category_id)
	{
		if ($this->fetch_one('question', 'id', ['category_id', 'eq', $category_id, 'i']))
		{
			return true;
		}
		if ($this->fetch_one('article', 'id', ['category_id', 'eq', $category_id, 'i']))
		{
			return true;
		}
		if ($this->fetch_one('video', 'id', ['category_id', 'eq', $category_id, 'i']))
		{
			return true;
		}
	}

	public function move_contents($from_id, $target_id)
	{
		if (!$from_id OR !$target_id)
		{
			return false;
		}

		$this->update('question', array(
			'category_id' => intval($target_id)
		), ['category_id', 'eq', $from_id, 'i']);

		$this->update('article', array(
			'category_id' => intval($target_id)
		), ['category_id', 'eq', $from_id, 'i']);

		$this->update('video', array(
			'category_id' => intval($target_id)
		), ['category_id', 'eq', $from_id, 'i']);

		$this->update('posts_index', array(
			'category_id' => intval($target_id)
		), ['category_id', 'eq', $from_id, 'i']);
	}
}
