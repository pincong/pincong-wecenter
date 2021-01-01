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

	public function setup()
	{
		$_GET['page'] = intval($_GET['page']);
		if ($_GET['page'] < 1)
		{
			$_GET['page'] = 0;
		}
	}

	public function activities_action()
	{
		$per_page = intval(get_setting('index_per_page'));

		TPL::assign('list', $this->model('activity')->list_activities($this->user_id, $_GET['page'], $per_page));

		TPL::output('home/activities_template');
	}

	// 邀请我回答的问题
	public function invites_action()
	{
		$per_page = intval(get_setting('contents_per_page'));
		// 注意: $limit分页的第一页是从0开始
		$limit = intval($_GET['page']) * $per_page .', '. $per_page;

		if ($list = $this->model('invite')->get_invite_question_list($this->user_id, $limit));
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

	// 我关注的问题
	public function questions_action()
	{
		$per_page = intval(get_setting('contents_per_page'));
		// 注意: $limit分页的第一页是从0开始
		$limit = intval($_GET['page']) * $per_page .', '. $per_page;

		if ($list = $this->model('focus')->get_user_focus($this->user_id, $limit));
		{
			foreach($list as $key => $val)
			{
				$uids[] = $val['uid'];
			}

			if ($uids)
			{
				$users_info = $this->model('account')->get_user_info_by_uids($uids);
			}

			foreach($list as $key => $val)
			{
				$list[$key]['user_info'] = $users_info[$val['uid']];
			}
		}

		TPL::assign('list', $list);

		TPL::output('home/questions_template');
	}

	// 我关注的主题
/*	public function following_posts_action()
	{
		$per_page = intval(get_setting('contents_per_page'));

		if ($list = $this->model('postfollow')->get_following_posts($this->user_id, intval($_GET['page']), $per_page))
		{
			foreach($list as $key => $val)
			{
				$uids[] = $val['uid'];
			}

			if ($uids)
			{
				$users_info = $this->model('account')->get_user_info_by_uids($uids);
			}

			foreach($list as $key => $val)
			{
				$list[$key]['user_info'] = $users_info[$val['uid']];
			}
		}

		TPL::assign('list', $list);

		TPL::output('home/following_posts_template');
	}*/


	public function welcome_action()
	{
		TPL::output('home/welcome_template');
	}

}