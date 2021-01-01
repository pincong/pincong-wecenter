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
		$this->crumb(AWS_APP::lang()->_t('私信'));
	}


	public function index_action()
	{
		$list = $this->model('pm')->get_conversations($this->user_id, H::GET('page'), S::get_int('contents_per_page'));

		TPL::import_js('js/openpgp.min.js');
		TPL::import_js('js/bcrypt.js');
		TPL::import_js('js/passwordutil.js');

		TPL::assign('list', $list);

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/pm/'),
			'total_rows' => $this->model('pm')->total_conversations(),
			'per_page' => S::get_int('contents_per_page')
		)));

		TPL::output('pm/index');
	}

	public function read_action()
	{
		$conversation_id = H::GET_I('id');
		if (!$conversation = $this->model('pm')->get_conversation($conversation_id, $this->user_id))
		{
			H::redirect_msg(AWS_APP::lang()->_t('会话不存在'), '/pm/');
		}

		$list = $this->model('pm')->read_conversation_messages($conversation_id, $this->user_id, H::GET('page'), S::get_int('replies_per_page'));

		$usernames = [];
		foreach($conversation['users'] as $user)
		{
			if ($user['uid'] != $this->user_id)
			{
				$usernames[] = $user['user_name'];
			}
		}
		$this->crumb(AWS_APP::lang()->_t('私信会话') . ': ' . implode(', ', $usernames));

		TPL::import_js('js/openpgp.min.js');
		TPL::import_js('js/bcrypt.js');
		TPL::import_js('js/passwordutil.js');

		TPL::assign('conversation', $conversation);
		TPL::assign('list', $list);

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/pm/read/id-') . $conversation['id'],
			'total_rows' => $this->model('pm')->count_conversation_messages($conversation_id),
			'per_page' => S::get_int('replies_per_page')
		)));

		TPL::output('pm/conversation');
	}

	public function new_action()
	{
		$uids = explode(',', H::GET_S('id'));
		$uids[] = $this->user_id;
		$users = $this->model('account')->get_user_info_by_uids($uids);
		if (!is_array($users) OR count($users) < 2 OR count($users) > 5)
		{
			H::redirect_msg(AWS_APP::lang()->_t('会话不存在'), '/pm/');
		}

		$conversation = [
			'id' => null,
			'users' => $users,
		];

		$usernames = [];
		foreach($conversation['users'] as $user)
		{
			if ($user['uid'] != $this->user_id)
			{
				$usernames[] = $user['user_name'];
			}
		}
		$this->crumb(AWS_APP::lang()->_t('私信会话') . ': ' . implode(', ', $usernames));

		TPL::import_js('js/openpgp.min.js');
		TPL::import_js('js/bcrypt.js');
		TPL::import_js('js/passwordutil.js');

		TPL::assign('conversation', $conversation);
		TPL::assign('list', null);

		TPL::assign('pagination', null);

		TPL::output('pm/conversation');
	}
}
