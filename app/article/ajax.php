<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['actions'] = array(
			'list',
			'log'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}


	public function lock_action()
	{
		if (!$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (! $article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文章不存在')));
		}

		$this->model('article')->lock_article($_POST['article_id'], !$article_info['lock'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_article_action()
	{
		if (!$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除文章的权限')));
		}

		if ($article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			if ($this->user_id != $article_info['uid'])
			{
				$this->model('account')->send_delete_message($article_info['uid'], $article_info['title'], $article_info['message']);
			}

			$this->model('article')->remove_article($article_info['id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
	}

	public function remove_comment_action()
	{
		$comment_info = $this->model('article')->get_comment_by_id($_POST['comment_id']);
		if (!$comment_info || !$comment_info['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('评论不存在')));
		}
		
		if ($this->user_id != $comment_info['uid'] AND!$this->user_info['permission']['edit_article'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除评论的权限')));
		}

		// 只清空不删除
		// TODO: implement update_comment_action()
		$this->model('article')->remove_article_comment($comment_info, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/article/' . $comment_info['article_id'])
		), 1, null));
	}

	public function log_action()
	{
		if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定文章不存在')));
		}

		$log_list = $this->model('content')->list_logs('article', $_GET['id'], $_GET['page'], get_setting('contents_per_page'));

		TPL::assign('article_info', $article_info);

		TPL::assign('list', $log_list);

		TPL::output('article/ajax/log');
	}

	public function set_recommend_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		switch ($_POST['action'])
		{
			case 'set':
				$this->model('article')->set_recommend($_POST['article_id']);
			break;

			case 'unset':
				$this->model('article')->unset_recommend($_POST['article_id']);
			break;
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function bump_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('posts')->bump_post($this->user_id, $_POST['article_id'], 'article'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文章不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function sink_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('posts')->sink_post($this->user_id, $_POST['article_id'], 'article'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文章不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}