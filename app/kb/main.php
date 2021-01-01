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

		if ($this->user_info['permission']['kb_explore'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}

		return $rule_action;
	}

	private function index_square()
	{
		if (!$this->user_info['permission']['kb_explore'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的声望还不够'));
		}

		$this->crumb(AWS_APP::lang()->_t('知识库'));

		$per_page = S::get('contents_per_page');

		$item_list = $this->model('kb')->list($_GET['page'], $per_page);
		$count = $this->model('kb')->found_rows();

		if ($item_list)
		{
			foreach ($item_list AS $key => $val)
			{
				$ids[] = $val['id'];
				$uids[$val['uid']] = $val['uid'];
				$uids[$val['last_uid']] = $val['last_uid'];
			}

			$users_info = $this->model('account')->get_user_info_by_uids($uids);

			foreach ($item_list AS $key => $val)
			{
				$item_list[$key]['user_info'] = $users_info[$val['uid']];
				$item_list[$key]['last_user_info'] = $users_info[$val['last_uid']];
			}
		}

		TPL::assign('item_list', $item_list);

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/kb/'),
			'total_rows' => $count,
			'per_page' => $per_page
		)));

		if (S::get('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::output('kb/square');
	}

	public function index_action()
	{
		if (!$_GET['id'])
		{
			$this->index_square();
			return;
		}

		if (!$this->user_info['permission']['kb_explore'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('你的声望还不够'));
		}

		if (! $item_info = $this->model('kb')->get($_GET['id']))
		{
			HTTP::error_404();
		}

		$uids[] = $item_info['uid'];
		$uids[] = $item_info['last_uid'];
		$users_info = $this->model('account')->get_user_info_by_uids($uids);

		$item_info['user_info'] = $users_info['uid'];
		$item_info['last_user_info'] = $users_info['last_uid'];

		TPL::assign('item_info', $item_info);

		$this->crumb($item_info['title']);

		TPL::output('kb/index');
	}

}
