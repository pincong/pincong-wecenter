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

	private function check_permission($post_uid)
	{
		if ($post_uid != $this->user_id AND !$this->user_info['permission']['edit_any_post'])
		{
			if (!$this->user_info['permission']['edit_specific_post'] OR !in_array($post_uid, get_setting_array('specific_post_uids')))
			{
				return false;
			}
		}
		return true;
	}

	public function index_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('question', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定问题不存在'));
		}

		if (!$this->check_permission($thread_info['uid']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个问题'), '/question/' . $thread_info['id']);
		}

		if (get_setting('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_category_list_by_user_permission($this->user_info['permission']));
		}

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('thread_info', $thread_info);

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::output('publish/index');
	}

	public function article_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('article', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定文章不存在'));
		}

		if (!$this->check_permission($thread_info['uid']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个文章'), '/article/' . $thread_info['id']);
		}

		if (get_setting('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_category_list_by_user_permission($this->user_info['permission']));
		}

		TPL::import_js('js/app/publish.js');

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/article');
	}

	public function video_action()
	{
		if (!$thread_info = $this->model('content')->get_thread_info_by_id('video', $_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定影片不存在'));
		}

		if (!$this->check_permission($thread_info['uid']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑这个影片'), '/video/' . $thread_info['id']);
		}

		if (get_setting('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_category_list_by_user_permission($this->user_info['permission']));
		}

		TPL::import_js('js/app/publish.js');

		TPL::assign('recent_topics', @unserialize($this->user_info['recent_topics']));

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/video');
	}

}
