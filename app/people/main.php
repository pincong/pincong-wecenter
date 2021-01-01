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

		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}

		return $rule_action;
	}

	private function index_square()
	{
		$this->crumb(_t('用户列表'));

		$is_admin = $this->user_info['permission']['is_administrator'];
		$is_mod = $this->user_info['permission']['is_moderator'];

		$all_groups = $this->model('usergroup')->get_all_groups();
		foreach ($all_groups as $key => $val)
		{
			if ($val['type'] == 2)
			{
				$custom_group[] = $val;
			}

			if ($is_admin AND $val['type'] == 0 AND $val['group_id'] > 0)
			{
				$custom_group[] = $val;
			}
		}

		$url_param = [];

		if (H::GET('sort') == 'ASC')
		{
			$sort = 'ASC';
			$url_param[] = 'sort-' . $sort;
		}
		else
		{
			$sort = 'DESC';
		}

		$sort_key = H::GET('sort_key');
		if ($sort_key == 'uid')
		{
			$order = $sort_key . ' ' . $sort;
			$url_param[] = 'sort_key-' . $sort_key;
		}
		else if ($is_mod AND $sort_key == 'user_update_time')
		{
			$order = $sort_key . ' ' . $sort;
			$url_param[] = 'sort_key-' . $sort_key;
		}
		else if ($is_mod AND $sort_key == 'mod_time')
		{
			$order = $sort_key . ' ' . $sort;
			$url_param[] = 'sort_key-' . $sort_key;
		}
		else
		{
			$order = 'reputation ' . $sort . ', uid ' . $sort;
		}

		$group_id = H::GET_I('group_id');
		if ($group_id > 0)
		{
			if ($all_groups[$group_id]['type'] == 2 OR ($is_admin AND $all_groups[$group_id]['type'] == 0))
			{
				$where[] = [['group_id', 'eq', $group_id], 'or', ['flagged', 'eq', $group_id]];
				$url_param[] = 'group_id-' . $group_id;
			}
		}

		$forbidden = H::GET_I('forbidden');
		$flagged = H::GET_I('flagged');
		if ($forbidden OR $flagged)
		{
			if ($forbidden)
			{
				$where[] = ['forbidden', 'notEq', 0];
				$url_param[] = 'forbidden-1';
			}

			if ($flagged)
			{
				$where[] = ['flagged', 'notEq', 0];
				$url_param[] = 'flagged-1';
			}
		}
		else
		{
			$where[] = ['forbidden', 'eq', 0];
		}

		$users_list = $this->model('account')->get_user_list($where, $order, H::GET('page'), S::get_int('contents_per_page'));

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/people/') . implode('__', $url_param),
			'total_rows' => $this->model('account')->total_rows(),
			'per_page' => S::get_int('contents_per_page')
		)));

		if ($users_list)
		{
			TPL::assign('users_list', array_values($users_list));
		}

		TPL::assign('custom_group', $custom_group);

		TPL::output('people/square');
	}

	public function index_action()
	{
		if (!H::GET('id'))
		{
			$this->index_square();
			return;
		}

		if (is_numeric(H::GET('id')))
		{
			$user = $this->model('account')->get_user_info_by_uid(H::GET('id'));
			if (!$user)
			{
				H::error_404();
			}
			H::redirect('/people/' . safe_url_encode($user['user_name']));
		}

		$user = $this->model('account')->get_user_info_by_username(H::GET('id'));
		if (!$user)
		{
			H::error_404();
		}

		$user['reputation_group_name'] = $this->model('usergroup')->get_user_group_name_by_user_info($user);

		$user['data'] = unserialize_array($user['extra_data']);

		TPL::assign('user', $user);

		$this->crumb(_t('%s 的个人主页', $user['user_name']));

		TPL::output('people/index');
	}

}
