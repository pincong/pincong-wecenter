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
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}

	public function index_action()
	{

		$this->crumb(AWS_APP::lang()->_t('影片'), '/video/');

		if ($_GET['category'])
		{
			$category_info = $this->model('category')->get_category_info($_GET['category']);
		}

		$video_list = $this->model('video')->get_video_list($category_info['id'], $_GET['page'], get_setting('contents_per_page'), 'add_time DESC');
		$video_list_total = $this->model('video')->found_rows();

		if ($video_list)
		{
			foreach ($video_list AS $key => $val)
			{
				$video_ids[] = $val['id'];
				$video_uids[$val['uid']] = $val['uid'];
			}

			$video_topics = $this->model('topic')->get_topics_by_item_ids($video_ids, 'video');
			$video_users_info = $this->model('account')->get_user_info_by_uids($video_uids);

			foreach ($video_list AS $key => $val)
			{
				$video_list[$key]['user_info'] = $video_users_info[$val['uid']];
				// 缩略图
				$video_list[$key]['thumb_url'] = Services_VideoParser::get_thumb_url($val['source_type'], $val['source']);
			}
		}

		// 导航
		if (TPL::is_output('block/content_nav_menu.tpl.htm', 'video/square'))
		{
			TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('video'));
		}

		/*
		//边栏热门话题 暂不实现
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'video/square'))
		{
			TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($category_info['id']));
		}
		*/

		if ($category_info)
		{
			TPL::assign('category_info', $category_info);

			$this->crumb($category_info['title'], '/video/category-' . $category_info['id']);

			$meta_description = $category_info['title'];

			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}

			TPL::set_meta('description', $meta_description);
		}

		TPL::assign('video_list', $video_list);
		TPL::assign('video_topics', $video_topics);

		/*
		// 热门内容 暂不实现
		TPL::assign('hot_videos', $this->model('video')->get_video_list(null, 1, 10, 'agree_count DESC', 30));
		*/

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/article/category_id-' . $_GET['category_id']),
			'total_rows' => $video_list_total,
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::output('video/square');
	}

}
