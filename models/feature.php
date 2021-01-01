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

class feature_class extends AWS_MODEL
{
	public function get_feature_list($page = null, $limit = null)
	{
		return $this->fetch_page('feature', null, 'sort DESC', $page, $limit);
	}

	public function get_enabled_feature_list()
	{
		if (!$list = AWS_APP::cache()->get('feature_list'))
		{
			$list = $this->fetch_all('feature', ['enabled', 'eq', 1], 'sort DESC');

			AWS_APP::cache()->set('feature_list', $list, S::get('cache_level_low'), 'feature_list');
		}
		return $list;
	}

	public function add_feature($title)
	{
		AWS_APP::cache()->cleanGroup('feature_list');

		return $this->insert('feature', array(
			'title' => $title
		));
	}

	public function update_feature($feature_id, $update_data)
	{
		AWS_APP::cache()->cleanGroup('feature_list');

		return $this->update('feature', $update_data, ['id', 'eq', $feature_id, 'i']);
	}

	public function get_feature_by_id($feature_id)
	{
		if (!intval($feature_id))
		{
			return false;
		}

		return $this->fetch_row('feature', ['id', 'eq', $feature_id, 'i']);
	}

	public function delete_feature($feature_id)
	{
		$this->delete('feature', ['id', 'eq', $feature_id, 'i']);

		AWS_APP::cache()->cleanGroup('feature_list');

		return true;
	}

	public function update_feature_enabled($id, $status)
	{
		$this->update('feature', array(
			'enabled' => intval($status)
		), ['id', 'eq', $id, 'i']);

		AWS_APP::cache()->cleanGroup('feature_list');

		return true;
	}
}
