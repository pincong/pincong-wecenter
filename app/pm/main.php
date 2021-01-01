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
		$this->crumb(_t('私信'));
	}


	public function index_action()
	{
		$list = $this->model('pm')->get_conversations($this->user_id, H::GET('page'), S::get_int('contents_per_page'));

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
			H::redirect_msg(_t('会话不存在'), '/pm/');
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
		$this->crumb(_t('私信会话') . ': ' . implode(', ', $usernames));

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
		$usernames = H::POSTS_S('usernames');
		if (count($usernames) > 4)
		{
			H::redirect_msg(_t('内容错误'));
		}
		$usernames = array_unique($usernames);

		$names = [];
		$sender_name = htmlspecialchars_decode($this->user_info['user_name']);
		foreach ($usernames as $username)
		{
			if (!$username)
			{
				continue;
			}
			if ($username == $sender_name)
			{
				H::redirect_msg(_t('不能给自己发私信'));
			}
			$names[] = $username;
		}
		$names[] = $sender_name;
		$count = count($names);
		if ($count < 2)
		{
			H::redirect_msg(_t('内容错误'));
		}

		$users = $this->model('account')->get_user_info_by_usernames($names);
		if (!is_array($users) OR count($users) != $count)
		{
			H::redirect_msg(_t('接收私信的用户不存在'));
		}

		foreach ($users as $user)
		{
			if (!$this->model('pm')->test_permissions($this->user_info, $user, $error))
			{
				H::redirect_msg($error);
				break;
			}
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
		$this->crumb(_t('私信会话') . ': ' . implode(', ', $usernames));

		TPL::assign('conversation', $conversation);
		TPL::assign('list', null);

		TPL::assign('pagination', null);

		TPL::output('pm/conversation');
	}

}
