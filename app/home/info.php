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

class info extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['redirect'] = false;
		return $rule_action;
	}

	// 邀请我回答的问题
	public function invites_action()
	{
		$per_page = S::get_int('contents_per_page');

		if ($list = $this->model('invite')->get_invite_question_list($this->user_id, H::GET('page'), $per_page));
		{
			foreach($list as $key => $val)
			{
				$uids[] = $val['sender_uid'];
			}

			if ($uids)
			{
				$users_info = $this->model('account')->get_user_info_by_uids($uids);
			}

			foreach($list as $key => $val)
			{
				$list[$key]['user_info'] = $users_info[$val['sender_uid']];
			}
		}

		if ($this->user_info['invite_count'] != count($list))
		{
			$this->model('account')->update_question_invite_count($this->user_id);
		}

		TPL::assign('list', $list);

		TPL::output('home/invites_template');
	}

	// 我关注的主题
	public function following_posts_action()
	{
		$per_page = S::get_int('contents_per_page');

		$list = $this->model('postfollow')->get_following_posts($this->user_id, H::GET('type'), H::GET('page'), $per_page);

		TPL::assign('list', $list);

		TPL::output('home/following_posts_template');
	}


	public function welcome_action()
	{
		TPL::output('home/welcome_template');
	}

}