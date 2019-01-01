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

class approval extends AWS_ADMIN_CONTROLLER
{
	public function list_action()
	{
		$this->crumb(AWS_APP::lang()->_t('内容审核'), 'admin/approval/list/');

		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(300));

		if (!$_GET['type'])
		{
			$_GET['type'] = 'question';
		}

		TPL::assign('answer_count', $this->model('publish')->count('approval', "type = 'answer'"));

		TPL::assign('question_count', $this->model('publish')->count('approval', "type = 'question'"));

		TPL::assign('article_count', $this->model('publish')->count('approval', "type = 'article'"));

		TPL::assign('article_comment_count', $this->model('publish')->count('approval', "type = 'article_comment'"));

		$approval_list = $this->model('publish')->get_approval_list($_GET['type'], $_GET['page'], $this->per_page);

		$found_rows = $this->model('publish')->found_rows();

		if ($approval_list)
		{
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/admin/approval/list/type-' . $_GET['type']),
				'total_rows' => $found_rows,
				'per_page' => $this->per_page
			))->create_links());

			foreach ($approval_list AS $approval_info)
			{
				if (!$approval_uids[$approval_info['uid']])
				{
					$approval_uids[$approval_info['uid']] = $approval_info['uid'];
				}
			}

			TPL::assign('users_info', $this->model('account')->get_user_info_by_uids($approval_uids));
		}

		TPL::assign($_GET['type'] . '_count', $found_rows);

		TPL::assign('approval_list', $approval_list);

		TPL::output('admin/approval/list');
	}

	public function preview_action()
	{
		if (!$_GET['action'] OR $_GET['action'] != 'edit')
		{
			$_GET['action'] = 'preview';
		}
		else
		{
			$this->crumb(AWS_APP::lang()->_t('待审项修改'), 'admin/approval/edit/');

			TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(300));
		}

		switch ($_GET['type'])
		{
			default:
				$approval_item = $this->model('publish')->get_approval_item($_GET['id']);

				break;
		}

		if (!$approval_item)
		{
			exit();
		}

		switch ($approval_item['type'])
		{
			case 'question':
				$approval_item['title'] = htmlspecialchars($approval_item['data']['question_content']);

				$approval_item['content'] = htmlspecialchars($approval_item['data']['question_detail']);

				$approval_item['topics'] = htmlspecialchars(implode(',', $approval_item['data']['topics']));

				break;

			case 'answer':
				$approval_item['content'] = htmlspecialchars($approval_item['data']['answer_content']);

				break;

			case 'article':
				$approval_item['title'] = htmlspecialchars($approval_item['data']['title']);

				$approval_item['content'] = htmlspecialchars($approval_item['data']['message']);

				break;

			case 'article_comment':
				$approval_item['content'] = htmlspecialchars($approval_item['data']['message']);

				break;

		}


		if ($_GET['action'] != 'edit')
		{
			$approval_item['content'] = nl2br(FORMAT::parse_bbcode($approval_item['content']));
		}

		TPL::assign('approval_item', $approval_item);

		TPL::output('admin/approval/' . $_GET['action']);
	}
}