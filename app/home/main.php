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
		if (! $this->user_id)
		{
			HTTP::redirect('/explore/');
		}
	}

	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('动态'));

		TPL::output('home/index');
	}

	public function invite_action()
	{
		$this->crumb(AWS_APP::lang()->_t('邀请我回复的问题'));

		TPL::output('home/invite');
	}

	// TODO: 删除
	public function question_action()
	{
		$this->crumb(AWS_APP::lang()->_t('我关注的问题'));

		TPL::output('home/question');
	}

	public function following_action()
	{
		$this->crumb(AWS_APP::lang()->_t('我关注的主题'));

		TPL::output('home/following');
	}

	public function explore_action()
	{
		HTTP::redirect('/explore/');
	}
}