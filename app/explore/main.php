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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white";

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}

	public function index_action()
	{
		if ($this->user_id)
		{
			$this->crumb(AWS_APP::lang()->_t('发现'));
		}

		if ($_GET['category'])
		{
			$category_info = $this->model('category')->get_category_info($_GET['category']);
		}

		if ($category_info)
		{
			TPL::assign('category_info', $category_info);

			$this->crumb($category_info['title']);

			$meta_description = $category_info['title'];

			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}

			TPL::set_meta('description', $meta_description);
		}

		// 导航
		TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('explore'));

		// 边栏热门话题
		TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($category_info['id']));

		// 边栏功能
		TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());

		if ($_GET['type'] == 'question')
		{
			$type = 'question';
		}
		else if ($_GET['type'] == 'article')
		{
			$type = 'article';
		}
		else if ($_GET['type'] == 'video')
		{
			$type = 'video';
		}

		if ($_GET['recommend'])
		{
			$recommend = true;
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$sort_type = 'hot';
			$day = intval($_GET['day']);
		}
		else if ($_GET['sort_type'] == 'unresponsive')
		{
			$sort_type = 'unresponsive';
			$answer_count = 0;
		}

		if ($sort_type == 'hot')
		{
			$posts_list = $this->model('posts')->get_hot_posts($type, $category_info['id'], $day, $_GET['page'], get_setting('contents_per_page'));
		}
		else
		{
			$posts_list = $this->model('posts')->get_posts_list($type, $_GET['page'], get_setting('contents_per_page'), null, $category_info['id'], $answer_count, $recommend);
		}

		if ($posts_list)
		{
			foreach ($posts_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['id'], 2, $val['uid']);
				}
			}
		}

		$base_url = '';
		if ($type)
		{
			if ($base_url)
			{
				$base_url .= '__';
			}
			$base_url .= 'type-' . $type;
		}
		if ($category_info['id'])
		{
			if ($base_url)
			{
				$base_url .= '__';
			}
			$base_url .= 'category-' . $category_info['id'];
		}
		if ($sort_type)
		{
			if ($base_url)
			{
				$base_url .= '__';
			}
			$base_url .= 'sort_type-' . $sort_type;
		}
		if ($day)
		{
			if ($base_url)
			{
				$base_url .= '__';
			}
			$base_url .= 'day-' . $day;
		}
		if ($recommend)
		{
			if ($base_url)
			{
				$base_url .= '__';
			}
			$base_url .= 'recommend-1';
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => url_rewrite('/') . $base_url,
			'total_rows' => $this->model('posts')->get_posts_list_total(),
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::assign('posts_list', $posts_list);
		TPL::assign('posts_list_bit', TPL::render('explore/list_template'));

		TPL::output('explore/index');
	}
}