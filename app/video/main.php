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
		if ($this->user_id AND H::GET('notification_id'))
		{
			$this->model('notification')->mark_as_read(H::GET('notification_id'), $this->user_id);
		}

		$item_id = H::GET_I('item_id');
		if ($item_id)
		{
			if (!$reply = $this->model('video')->get_video_comment_by_id($item_id))
			{
				H::error_404();
			}
			$thread_id = $reply['parent_id'];
		}
		else
		{
			$thread_id = H::GET('id');
		}

		if (! $thread_info = $this->model('video')->get_video_by_id($thread_id))
		{
			H::error_404();
		}

		$replies_per_page = S::get_int('replies_per_page');
		if (!$replies_per_page)
		{
			$replies_per_page = 100;
		}

		$url_param[] = 'id-' . $thread_info['id'];

		if (H::GET('sort') == 'DESC')
		{
			$sort = 'DESC';

			$url_param[] = 'sort-DESC';
		}
		else
		{
			$sort = 'ASC';
		}

		if (H::GET('fold'))
		{
			$order_by[] = "fold ASC";

			$url_param[] = 'fold-1';
		}

		if (H::GET('sort_key') == 'agree_count')
		{
			$order_by[] = "reputation " . $sort;
			$order_by[] = "agree_count " . $sort;

			$url_param[] = 'sort_key-agree_count';
		}
		else
		{
			$order_by[] = "id " . $sort;
		}

		$thread_info['iframe_url'] = Services_VideoParser::get_iframe_url($thread_info['source_type'], $thread_info['source']);

		if ($this->user_id)
		{
			// 当前用户点赞状态 1赞同 -1反对
			$thread_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('video', $thread_info['id'], $this->user_id);
		}

		TPL::assign('video_info', $thread_info);
		if ($thread_info['redirect_id'])
		{
			TPL::assign('redirect_info', $this->model('post')->get_post_by_id('video', $thread_info['redirect_id']));
		}
		if (H::GET('rf'))
		{
			TPL::assign('redirected_from', $this->model('post')->get_post_by_id('video', H::GET('rf')));
		}

		$page_title = CF::page_title($thread_info);
		$this->crumb($page_title);

		$reply_count = $thread_info['reply_count'];
		// 判断是否已合并
		if ($redirect_posts = $this->model('post')->get_redirect_threads('video', $thread_info['id']))
		{
			foreach ($redirect_posts AS $key => $val)
			{
				$post_ids[] = $val['id'];
				// 修复合并后回复数
				$reply_count += $val['reply_count'];
			}
		}
		$post_ids[] = $thread_info['id'];

		if ($item_id)
		{
			// 显示单个评论
			$comments[] = $reply;
		}
		else
		{
			$comments = $this->model('video')->get_video_comments($post_ids, H::GET('page'), $replies_per_page, implode(', ', $order_by));
		}

		if ($comments AND $this->user_id)
		{
			$comment_ids = array();
			foreach ($comments as $comment)
			{
				$comment_ids[] = $comment['id'];
			}

			$comment_vote_values = $this->model('vote')->get_user_vote_values_by_ids('video_reply', $comment_ids, $this->user_id);

			foreach ($comments AS $key => $val)
			{
				// 当前用户评论点赞状态
				$comments[$key]['vote_value'] = $comment_vote_values[$val['id']];
			}
		}

		$this->model('post')->update_view_count('video', $thread_info['id']);

		TPL::assign('comments', $comments);
		TPL::assign('comment_count', $reply_count);

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/video/') . implode('__', $url_param),
			'total_rows' => $reply_count,
			'per_page' => $replies_per_page
		)));

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($thread_info['title'])));

		TPL::set_meta('description', $thread_info['title'] . ' - ' . truncate_text(str_replace("\r\n", ' ', $thread_info['message']), 128));

		$topic_ids = $this->model('topic')->get_topic_ids_by_item_id($thread_info['id'], 'video');
		if ($topic_ids)
		{
			TPL::assign('topics', $this->model('topic')->get_topics_by_ids($topic_ids));
		}

		TPL::assign('related_posts', $this->model('threadindex')->get_related_posts_by_topic_ids('video', $topic_ids, $thread_info['id']));
		TPL::assign('recommended_posts', $this->model('threadindex')->get_recommended_posts('video', $thread_info['id']));

		if ($this->user_id)
		{
			TPL::assign('following', $this->model('postfollow')->is_following('video', $thread_info['id'], $this->user_id));
		}

		TPL::output('video/index');
	}

}
