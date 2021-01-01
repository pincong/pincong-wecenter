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

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}

		return $rule_action;
	}

	public function index_action()
	{
		if ($this->user_id AND $_GET['notification_id'])
		{
			$this->model('notification')->mark_as_read($_GET['notification_id'], $this->user_id);
		}

		$item_id = intval($_GET['item_id']);
		if ($item_id)
		{
			if (!$reply = $this->model('video')->get_video_comment_by_id($item_id))
			{
				HTTP::error_404();
			}
			$_GET['id'] = $reply['video_id'];
		}

		if (! $video_info = $this->model('video')->get_video_by_id($_GET['id']))
		{
			HTTP::error_404();
		}

		$replies_per_page = intval(get_setting('replies_per_page'));
		if (!$replies_per_page)
		{
			$replies_per_page = 100;
		}

		$url_param[] = 'id-' . $video_info['id'];

		if ($_GET['sort'] == 'DESC')
		{
			$sort = 'DESC';

			$url_param[] = 'sort-DESC';
		}
		else
		{
			$sort = 'ASC';
		}

		if ($_GET['fold'])
		{
			$order_by[] = "fold ASC";

			$url_param[] = 'fold-1';
		}

		if ($_GET['sort_key'] == 'agree_count')
		{
			$order_by[] = "reputation " . $sort;
			$order_by[] = "agree_count " . $sort;

			$url_param[] = 'sort_key-agree_count';
		}
		else
		{
			$order_by[] = "id " . $sort;
		}

		$video_info['iframe_url'] = Services_VideoParser::get_iframe_url($video_info['source_type'], $video_info['source']);

		if ($this->user_id)
		{
			// 当前用户点赞状态 1赞同 -1反对
			$video_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('video', $video_info['id'], $this->user_id);
		}

		TPL::assign('video_info', $video_info);
		if ($video_info['redirect_id'])
		{
			TPL::assign('redirect_info', $this->model('content')->get_post_by_id('video', $video_info['redirect_id']));
		}
		if ($_GET['rf'])
		{
			TPL::assign('redirected_from', $this->model('content')->get_post_by_id('video', $_GET['rf']));
		}

		$video_topics = $this->model('topic')->get_topics_by_item_id($video_info['id'], 'video');

		if ($video_topics)
		{
			TPL::assign('video_topics', $video_topics);

			foreach ($video_topics AS $topic_info)
			{
				// 推荐相关 下文
				$video_topic_ids[] = $topic_info['topic_id'];
			}
		}

		$page_title = CF::page_title($video_info['user_info'], 'video_' . $video_info['id'], $video_info['title']);
		$this->crumb($page_title);

		$reply_count = $video_info['comment_count'];
		// 判断是否已合并
		if ($redirect_posts = $this->model('content')->get_redirect_posts('video', $video_info['id']))
		{
			foreach ($redirect_posts AS $key => $val)
			{
				$post_ids[] = $val['id'];
				// 修复合并后回复数
				$reply_count += $val['comment_count'];
			}
		}
		$post_ids[] = $video_info['id'];

		if ($item_id)
		{
			// 显示单个评论
			$comments[] = $reply;
		}
		else
		{
			$comments = $this->model('video')->get_video_comments($post_ids, $_GET['page'], $replies_per_page, implode(', ', $order_by));
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

				//$comments[$key]['message'] = $this->model('mention')->parse_at_user($val['message']);
			}
		}

		TPL::assign('question_related_list', $this->model('question')->get_related_question_list(null, $video_info['title']));

		$this->model('content')->update_view_count('video', $video_info['id'], session_id());

		TPL::assign('comments', $comments);
		TPL::assign('comment_count', $reply_count);

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => url_rewrite('/video/') . implode('__', $url_param),
			'total_rows' => $reply_count,
			'per_page' => $replies_per_page
		))->create_links());

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($video_info['title'])));

		TPL::set_meta('description', $video_info['title'] . ' - ' . truncate_text(str_replace("\r\n", ' ', $video_info['message']), 128));

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		// 推荐相关
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

		if ($this->user_id)
		{
			TPL::assign('following', $this->model('postfollow')->is_following('video', $video_info['id'], $this->user_id));
		}

		TPL::output('video/index');
	}

}
