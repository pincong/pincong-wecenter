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
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('发布'));
	}

	public function index_action()
	{
		$id = intval($_GET['id']);
		if ($id)
		{
			if (!$question_info = $this->model('content')->get_thread_info_by_id('question', $id))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
			}

			if (!$this->user_info['permission']['edit_any_post'] AND $question_info['uid'] != $this->user_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个问题'), '/question/' . $question_info['question_id']);
			}
		}
		else if (!$this->user_info['permission']['publish_question'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的声望还不够'));
		}
		else if ($this->is_post() AND $_POST['question_detail'])
		{
			$question_info = array(
				'question_content' => htmlspecialchars($_POST['question_content']),
				'question_detail' => htmlspecialchars($_POST['question_detail']),
				'category_id' => intval($_POST['category_id'])
			);
		}
		else
		{
			$question_info = array(
				'question_content' => htmlspecialchars($_POST['question_content']),
				'question_detail' => ''
			);
		}

		if (!$id)
		{
			if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_question'))
			{
				H::redirect_msg(AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name')), '/currency/rule/');
			}

			if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('今日发帖数量已经达到上限'));
			}
		}

		if (!$question_info['category_id'])
		{
			$question_info['category_id'] = ($_GET['category_id']) ? intval($_GET['category_id']) : 0;
		}

		if (get_setting('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $question_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_category_list_by_user_permission($this->user_info['permission']));
		}

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('question_info', $question_info);

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::output('publish/index');
	}

	public function article_action()
	{
		$id = intval($_GET['id']);
		if ($id)
		{
			if (!$article_info = $this->model('content')->get_thread_info_by_id('article', $id))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定文章不存在'));
			}

			if (!$this->user_info['permission']['edit_any_post'] AND $article_info['uid'] != $this->user_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个文章'), '/article/' . $article_info['id']);
			}
		}
		else if (!$this->user_info['permission']['publish_article'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的声望还不够'));
		}
		else if ($this->is_post() AND $_POST['message'])
		{
			$article_info = array(
				'title' => htmlspecialchars($_POST['title']),
				'message' => htmlspecialchars($_POST['message']),
				'category_id' => intval($_POST['category_id'])
			);
		}
		else
		{
			$article_info =  array(
				'title' => htmlspecialchars($_POST['title']),
				'message' => ''
			);
		}

		if (!$id)
		{
			if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_article'))
			{
				H::redirect_msg(AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name')), '/currency/rule/');
			}

			if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('今日发帖数量已经达到上限'));
			}
		}

		if (!$article_info['category_id'])
		{
			$article_info['category_id'] = ($_GET['category_id']) ? intval($_GET['category_id']) : 0;
		}

		if (get_setting('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $article_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_category_list_by_user_permission($this->user_info['permission']));
		}

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::assign('article_info', $article_info);

		TPL::output('publish/article');
	}

	public function video_action()
	{
		$id = intval($_GET['id']);
		if ($id)
		{
			if (!$video_info = $this->model('content')->get_thread_info_by_id('video', $id))
			{
				H::redirect_msg(AWS_APP::lang()->_t('指定影片不存在'));
			}

			if (!$this->user_info['permission']['edit_any_post'] AND $video_info['uid'] != $this->user_id)
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个影片'), '/video/' . $video_info['id']);
			}
		}
		else if (!$this->user_info['permission']['publish_video'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的声望还不够'));
		}
		else if ($this->is_post() AND $_POST['message'])
		{
			$video_info = array(
				'title' => htmlspecialchars($_POST['title']),
				'message' => htmlspecialchars($_POST['message']),
				'category_id' => intval($_POST['category_id'])
			);
		}
		else
		{
			$video_info =  array(
				'title' => htmlspecialchars($_POST['title']),
				'message' => ''
			);
		}

		if (!$id)
		{
			if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_video'))
			{
				H::redirect_msg(AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name')), '/currency/rule/');
			}

			if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('今日发帖数量已经达到上限'));
			}
		}

		if (!$video_info['category_id'])
		{
			$video_info['category_id'] = ($_GET['category_id']) ? intval($_GET['category_id']) : 0;
		}

		if (get_setting('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $video_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_category_list_by_user_permission($this->user_info['permission']));
		}

		TPL::import_js('js/app/publish.js');

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::assign('video_info', $video_info);

		TPL::output('publish/video');
	}

	public function delay_action()
	{
		$url = '/';

		H::redirect_msg(AWS_APP::lang()->_t('发布成功, 内容将会延迟显示, 请稍后再来查看...'), $url);
	}

}
