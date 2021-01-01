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
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}

	public function index_action()
	{
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		$item_id = intval($_GET['item_id']);
		if ($item_id)
		{
			if (!$reply = $this->model('article')->get_comment_by_id($item_id))
			{
				HTTP::error_404();
			}
			$_GET['id'] = $reply['article_id'];
		}

		if (!$article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			HTTP::error_404();
		}

		$replies_per_page = intval(get_setting('replies_per_page'));
		if (!$replies_per_page)
		{
			$replies_per_page = 100;
		}

		if ($this->user_id)
		{
			$article_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('article', $article_info['id'], $this->user_id);
		}

		TPL::assign('article_info', $article_info);

		$article_topics = $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article');

		if ($article_topics)
		{
			TPL::assign('article_topics', $article_topics);

			foreach ($article_topics AS $topic_info)
			{
				$article_topic_ids[] = $topic_info['topic_id'];
			}
		}

		$page_title = CF::page_title($article_info['user_info'], 'article_' . $article_info['id'], $article_info['title']);
		$this->crumb($page_title);

		if ($_GET['fold'])
		{
			$order_by = "fold ASC, ";
		}
		else
		{
			$order_by = "";
		}

		$order_by .= "id ASC";

		if ($item_id)
		{
			$comments[] = $reply;
		}
		else
		{
			$comments = $this->model('article')->get_comments($article_info['id'], $_GET['page'], $replies_per_page, $order_by);
		}

		if ($comments AND $this->user_id)
		{
			$comment_ids = array();
			foreach ($comments as $comment)
			{
				$comment_ids[] = $comment['id'];
			}

			$comment_vote_values = $this->model('vote')->get_user_vote_values_by_ids('article_comment', $comment_ids, $this->user_id);

			foreach ($comments AS $key => $val)
			{
				// 当前用户评论点赞状态
				$comments[$key]['vote_value'] = $comment_vote_values[$val['id']];

				$comments[$key]['message'] = $this->model('mention')->parse_at_user($val['message']);

			}
		}

		if ($this->user_id)
		{
			TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $article_info['uid']));
		}

		TPL::assign('question_related_list', $this->model('question')->get_related_question_list(null, $article_info['title']));

		$this->model('content')->update_view_count('article', $article_info['id'], session_id());

		TPL::assign('comments', $comments);
		TPL::assign('comments_count', $article_info['comments']);

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/article/id-' . $article_info['id']),
			'total_rows' => $article_info['comments'],
			'per_page' => $replies_per_page
		))->create_links());

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($article_info['title'])));

		TPL::set_meta('description', $article_info['title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($article_info['message'])), 0, 128, 'UTF-8', '...'));

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		$recommend_posts = $this->model('posts')->get_recommend_posts_by_topic_ids($article_topic_ids);

		if ($recommend_posts)
		{
			foreach ($recommend_posts as $key => $value)
			{
				if ($value['id'] AND $value['id'] == $article_info['id'])
				{
					unset($recommend_posts[$key]);

					break;
				}
			}

			TPL::assign('recommend_posts', $recommend_posts);
		}

		TPL::output('article/index');
	}

}
