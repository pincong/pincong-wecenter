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
		$rule_action['actions'] = array(
			'explore'
		);

		return $rule_action;
	}

	public function setup()
	{
		if (! $this->user_id)
		{
			HTTP::redirect('/explore/');
		}
	}

	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('动态'), '/home/');

		// 边栏可能感兴趣的人或话题
		TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));

		TPL::output('home/index');
	}

	public function invite_action()
	{
		$this->crumb(AWS_APP::lang()->_t('邀请我回复的问题'), '/home/invite/');

		// 边栏可能感兴趣的人或话题
		TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));

		TPL::output('home/invite');
	}

	public function question_action()
	{
		$this->crumb(AWS_APP::lang()->_t('我关注的问题'), '/home/question/');

		// 边栏可能感兴趣的人或话题
		TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));

		TPL::output('home/question');
	}

	public function explore_action()
	{
		HTTP::redirect('/explore/');
	}
}