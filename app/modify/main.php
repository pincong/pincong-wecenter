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
		$this->crumb(AWS_APP::lang()->_t('编辑'));
	}

	public function index_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('question', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
		}

		if (!can_edit_post($thread_info['uid'], $this->user_info))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个问题'), '/question/' . $thread_info['id']);
		}

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::import_js('js/app/publish.js');

		if (S::get('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('thread_info', $thread_info);

		TPL::assign('recent_topics', unserialize_array($this->user_info['recent_topics']));

		TPL::output('publish/index');
	}

	public function article_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('article', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定文章不存在'));
		}

		if (!can_edit_post($thread_info['uid'], $this->user_info))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个文章'), '/article/' . $thread_info['id']);
		}

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::import_js('js/app/publish.js');

		if (S::get('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('recent_topics', unserialize_array($this->user_info['recent_topics']));

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/article');
	}

	public function video_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('video', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定影片不存在'));
		}

		if (!can_edit_post($thread_info['uid'], $this->user_info))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个影片'), '/video/' . $thread_info['id']);
		}

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::import_js('js/app/publish.js');

		TPL::assign('recent_topics', unserialize_array($this->user_info['recent_topics']));

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/video');
	}
    
    public function voting_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('voting', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定文章不存在'));
		}

		if (!can_edit_post($thread_info['uid'], $this->user_info))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个文章'), '/voting/' . $thread_info['id']);
		}

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::import_js('js/app/publish.js');

		if (S::get('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('recent_topics', unserialize_array($this->user_info['recent_topics']));

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/voting');
	}

}
