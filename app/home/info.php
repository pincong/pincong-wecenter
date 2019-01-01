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
		$rule_action['actions'] = array();
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

		if ($list = $this->model('question')->get_invite_question_list($this->user_id, $limit));
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

		if ($list = $this->model('question')->get_user_focus($this->user_id, $limit));
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


	public function welcome_action()
	{
		TPL::output('home/welcome_template');
	}

	public function welcome_get_topics_action()
	{
		if ($topics_list = $this->model('topic')->get_topic_list("discuss_count > 5", 'RAND()', 8))
		{
			foreach ($topics_list as $key => $topic)
			{
				$topics_list[$key]['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic['topic_id']);
			}
		}
		TPL::assign('topics_list', $topics_list);

		TPL::output('home/welcome_get_topics_template');
	}

	public function welcome_get_users_action()
	{
		if ($welcome_recommend_users = trim(rtrim(get_setting('welcome_recommend_users'), ',')))
		{
			$welcome_recommend_users = explode(',', $welcome_recommend_users);

			$users_list = $this->model('account')->get_users_list("user_name IN('" . implode("','", $welcome_recommend_users) . "')", 6, true, true, 'RAND()');
		}

		if (!$users_list)
		{
			//$users_list = $this->model('account')->get_users_list("reputation > 5 AND last_login > " . (time() - (60 * 60 * 24 * 7)), 6, true, true, 'RAND()');
			$users_list = $this->model('account')->get_users_list("reputation > 5 AND forbidden = 0", 6, true, true, 'RAND()');
		}

		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				$users_list[$key]['follow_check'] = $this->model('follow')->user_follow_check($this->user_id, $val['uid']);
			}
		}

		TPL::assign('users_list', $users_list);

		TPL::output('home/welcome_get_users_template');
	}

}