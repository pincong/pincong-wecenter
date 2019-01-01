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
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}

	public function index_action()
	{
		/*
		// 通知设为已读 暂不实现
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}
		*/

		if (! $video_info = $this->model('video')->get_video_info_by_id($_GET['id']))
		{
			HTTP::error_404();
		}

		$video_info['user_info'] = $this->model('account')->get_user_info_by_uid($video_info['uid'], true);

		$video_info['thumb_url'] = Services_VideoParser::get_thumb_url($video_info['source_type'], $video_info['source'], 'l');

		if ($this->user_id)
		{
			// 当前用户点赞状态 1赞同 -1反对
			$video_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('video', $video_info['id'], $this->user_id);
		}

		TPL::assign('video_info', $video_info);

		$video_topics = $this->model('topic')->get_topics_by_item_id($video_info['id'], 'video');

		if ($video_topics)
		{
			TPL::assign('video_topics', $video_topics);

			foreach ($video_topics AS $topic_info)
			{
				// 推荐相关 下文
				//$video_topic_ids[] = $topic_info['topic_id'];
			}
		}

		$this->crumb($video_info['title'], '/video/' . $video_info['id']);

		if ($_GET['item_id'])
		{
			// 显示单个评论
			$comments[] = $this->model('video')->get_comment_by_id($_GET['item_id']);
		}
		else
		{
			$comments = $this->model('video')->get_comments($video_info['id'], $_GET['page'], 100);
		}

		if ($comments AND $this->user_id)
		{
			$comment_ids = array();
			foreach ($comments as $comment)
			{
				$comment_ids[] = $comment['id'];
			}

			$comment_vote_values = $this->model('vote')->get_user_vote_values_by_ids('video_comment', $comment_ids, $this->user_id);

			foreach ($comments AS $key => $val)
			{
				// 当前用户评论点赞状态
				$comments[$key]['vote_value'] = $comment_vote_values[$val['id']];

				$comments[$key]['message'] = $this->model('question')->parse_at_user($val['message']);
			}
		}

		// 弹幕, 暂时直接在页面输出
		$danmaku = $this->model('danmaku')->get_danmaku_list_by_video_id($video_info['id'], 1, 5000);
		foreach ($danmaku as $key => $val)
		{
			unset($danmaku[$key]['uid']);
			unset($danmaku[$key]['video_id']);
			unset($danmaku[$key]['add_time']);
			unset($danmaku[$key]['agree_count']);
			$danmaku[$key]['stime'] = intval($val['stime']);
			$danmaku[$key]['mode'] = intval($val['mode']);
			$danmaku[$key]['size'] = intval($val['size']);
			$danmaku[$key]['color'] = intval($val['color']);
		}
		TPL::assign('danmaku_json', json_encode($danmaku));

		$this->model('content')->update_view_count('video', $video_info['id'], session_id());

		TPL::assign('comments', $comments);
		TPL::assign('comment_count', $video_info['comment_count']);

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/video/id-' . $video_info['id']),
			'total_rows' => $video_info['comment_count'],
			'per_page' => 100
		))->create_links());

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($video_info['title'])));

		TPL::set_meta('description', $video_info['title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($video_info['message'])), 0, 128, 'UTF-8', '...'));

		/*
		// 推荐相关 暂不实现
		$recommend_posts = $this->model('posts')->get_recommend_posts_by_topic_ids($video_topic_ids);

		if ($recommend_posts)
		{
			foreach ($recommend_posts as $key => $value)
			{
				if ($value['id'] AND $value['id'] == $video_info['id'])
				{
					unset($recommend_posts[$key]);

					break;
				}
			}

			TPL::assign('recommend_posts', $recommend_posts);
		}
		*/

		TPL::output('video/index');
	}

	public function index_square_action()
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
