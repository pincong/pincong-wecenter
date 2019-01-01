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
		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['rule_type'] = 'black';
		}
		else
		{
			$rule_action['rule_type'] = 'white';
		}

		return $rule_action;
	}

	public function index_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		if (is_digits($_GET['id']))
		{
			if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
			{
				$user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
			}
		}
		else
		{
			$user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
		}

		if (!$user)
		{
			HTTP::error_404();
		}

		/*if ($user['forbidden'] AND !$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			header('HTTP/1.1 404 Not Found');

			H::redirect_msg(AWS_APP::lang()->_t('该用户已被封禁'), '/');
		}*/

		if (urldecode($user['url_token']) != $_GET['id'])
		{
			HTTP::redirect('/people/' . $user['url_token']);
		}

		$user['reputation_group_name'] = $this->model('reputation')->get_reputation_group_name_by_reputation($user['reputation']);

		$this->model('people')->update_view_count($user['uid'], session_id());

		TPL::assign('user', $user);

		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));

		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), 'people/' . $user['url_token']);

		TPL::import_css('css/user.css');

		TPL::assign('fans_list', $this->model('follow')->get_user_fans($user['uid'], 5));
		TPL::assign('friends_list', $this->model('follow')->get_user_friends($user['uid'], 5));
		TPL::assign('focus_topics', $this->model('topic')->get_focus_topic_list($user['uid'], 10));

		TPL::output('people/index');
	}

	public function index_square_action()
	{

		if (!$_GET['page'])
		{
			$_GET['page'] = 1;
		}

		$this->crumb(AWS_APP::lang()->_t('用户列表'), '/people/');

		if ($_GET['topic_id'])
		{

		}
		else
		{
			$where = array();

			$group_id = intval($_GET['group_id']);
			if ($group_id > 99)
			{
				$where[] = 'group_id = ' . $group_id;
			}

			$users_list = $this->model('account')->get_users_list(implode('', $where), calc_page_limit($_GET['page'], get_setting('contents_per_page')), true, false, 'forbidden ASC, reputation DESC');

			$where[] = 'group_id <> 3';

			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/people/group_id-' . $_GET['group_id']),
				'total_rows' => $this->model('account')->get_user_count(implode(' AND ', $where)),
				'per_page' => get_setting('contents_per_page')
			))->create_links());
		}

		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				if ($val['reputation'])
				{
					$reputation_users_ids[] = $val['uid'];
					$users_reputations[$val['uid']] = $val['reputation'];
				}

				$uids[] = $val['uid'];
			}

			if ($uids AND $this->user_id)
			{
				$users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $uids);
			}

			foreach ($users_list as $key => $val)
			{
				$users_list[$key]['focus'] = $users_follow_check[$val['uid']];
			}

			TPL::assign('users_list', array_values($users_list));
		}

		if (!$_GET['group_id'])
		{
			TPL::assign('parent_topics', $this->model('topic')->get_parent_topics());
		}

		TPL::assign('custom_group', $this->model('account')->get_user_group_list(0, 1));

		TPL::output('people/square');
	}
}
