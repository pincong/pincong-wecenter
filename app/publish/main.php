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
		$this->crumb(_t('发布'));
	}

	public function index_action()
	{
		if (!$this->model('publish')->check_user_permission('question', $this->user_info))
		{
			H::redirect_msg(_t('你的声望还不够'));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_question'))
		{
			H::redirect_msg(_t('你的剩余%s已经不足以进行此操作', S::get('currency_name')), '/currency/rule/');
		}

		if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::redirect_msg(_t('今日发帖数量已经达到上限'));
		}

		$thread_info = array(
			'title' => '',
			'message' => '',
			'category_id' => H::GET_I('category_id')
		);

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/index');
	}

	public function article_action()
	{
		if (!$this->model('publish')->check_user_permission('article', $this->user_info))
		{
			H::redirect_msg(_t('你的声望还不够'));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_article'))
		{
			H::redirect_msg(_t('你的剩余%s已经不足以进行此操作', S::get('currency_name')), '/currency/rule/');
		}

		if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::redirect_msg(_t('今日发帖数量已经达到上限'));
		}

		$thread_info = array(
			'title' => '',
			'message' => '',
			'category_id' => H::GET_I('category_id')
		);

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/article');
	}

	public function video_action()
	{
		if (!$this->model('publish')->check_user_permission('video', $this->user_info))
		{
			H::redirect_msg(_t('你的声望还不够'));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_video'))
		{
			H::redirect_msg(_t('你的剩余%s已经不足以进行此操作', S::get('currency_name')), '/currency/rule/');
		}

		if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::redirect_msg(_t('今日发帖数量已经达到上限'));
		}

		$thread_info = array(
			'title' => '',
			'message' => '',
			'category_id' => H::GET_I('category_id')
		);

		if (S::get('category_enable') != 'N')
		{
			TPL::assign('category_current_id', $thread_info['category_id']);
			TPL::assign('category_list', $this->model('category')->get_allowed_categories($this->user_info));
		}

		TPL::assign('thread_info', $thread_info);

		TPL::output('publish/video');
	}

	public function delay_action()
	{
		$url = '/';

		H::redirect_msg(_t('发布成功, 内容将会延迟显示, 请稍后再来查看...'), $url);
	}

}
