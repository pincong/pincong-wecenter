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

class menu_class extends AWS_MODEL
{
	public function add_nav_menu($title, $description, $type = 'custom', $type_id = 0, $link = null)
	{
		AWS_APP::cache()->cleanGroup('nav_menu');

		return $this->insert('nav_menu', array(
			'title' => $title,
			'description' => $description,
			'type' => $type,
			'type_id' => $type_id,
			'link' => $link,
			'icon' => '',
			'sort' => 0
		));
	}

	public function get_nav_menu_list($app = null)
	{
		if (!$nav_menu_data = AWS_APP::cache()->get('nav_menu_list'))
		{
			$nav_menu_data = $this->fetch_all('nav_menu', null, 'sort DESC');

			AWS_APP::cache()->set('nav_menu_list', $nav_menu_data, S::get('cache_level_low'), 'nav_menu');
		}

		if ($nav_menu_data)
		{
			$category_info = $this->model('category')->get_category_list();

			if ($app)
			{
				$url_prefix = url_rewrite('/') . $app . '/';
			}

			foreach ($nav_menu_data as $key => $val)
			{
				switch ($val['type'])
				{
					case 'category':
					{
						$nav_menu_data[$key]['link'] = $url_prefix . 'category-' . $category_info[$val['type_id']]['id'];
					}
					break;
				}

				$nav_menu_data['category_ids'][] = $val['type_id'];
			}

			{
				$nav_menu_data['base']['link'] = $url_prefix;
			}
		}

		return $nav_menu_data;
	}

	public function update_nav_menu($nav_menu_id, $data)
	{
		AWS_APP::cache()->cleanGroup('nav_menu');

		return $this->update('nav_menu', $data, ['id', 'eq', $nav_menu_id, 'i']);
	}

	public function remove_nav_menu($nav_menu_id)
	{
		AWS_APP::cache()->cleanGroup('nav_menu');

		return $this->delete('nav_menu', ['id', 'eq', $nav_menu_id, 'i']);
	}
}