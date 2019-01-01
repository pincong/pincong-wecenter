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
	public function update_category_info($category_id, $title, $group_id, $description = null)
	{
		return $this->update('category', array(
			'title' => htmlspecialchars($title),
			//'group_id' => intval($group_id),
			'description' => $description
		), 'id = ' . intval($category_id));
	}

	public function set_category_sort($category_id, $sort)
	{
		return $this->update('category', array(
			'sort' => intval($sort)
		), 'id = ' . intval($category_id));
	}

	public function add_category($title, $group_id)
	{
		return $this->insert('category', array(
			'title' => $title,
			//'group_id' => intval($group_id),
		));
	}

	public function delete_category($category_id)
	{
		$this->delete('nav_menu', "type = 'category' AND type_id = " . intval($category_id));

		return $this->delete('category', 'id = ' . intval($category_id));
	}

	public function category_exists($category_id)
	{
		return $this->count('category', "id = " . intval($category_id));
	}

	public function contents_exists($category_id)
	{
		if ($this->fetch_one('question', 'question_id', 'category_id = ' . intval($category_id)))
		{
			return true;
		}
		if ($this->fetch_one('article', 'id', 'category_id = ' . intval($category_id)))
		{
			return true;
		}
		if ($this->fetch_one('video', 'id', 'category_id = ' . intval($category_id)))
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
		), 'category_id = ' . intval($from_id));

		$this->update('article', array(
			'category_id' => intval($target_id)
		), 'category_id = ' . intval($from_id));

		$this->update('video', array(
			'category_id' => intval($target_id)
		), 'category_id = ' . intval($from_id));

		$this->update('posts_index', array(
			'category_id' => intval($target_id)
		), 'category_id = ' . intval($from_id));
	}
}
