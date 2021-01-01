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
		if ($inbox_dialog = $this->model('message')->get_inbox_message(H::GET('page'), S::get_int('contents_per_page'), $this->user_id))
		{
			$inbox_total_rows = $this->model('message')->total_rows();

			foreach ($inbox_dialog as $key => $val)
			{
				$dialog_ids[] = $val['id'];

				if ($this->user_id == $val['recipient_uid'])
				{
					$inbox_dialog_uids[] = $val['sender_uid'];
				}
				else
				{
					$inbox_dialog_uids[] = $val['recipient_uid'];
				}
			}
		}

		if ($inbox_dialog_uids)
		{
			if ($users_info_query = $this->model('account')->get_user_info_by_uids($inbox_dialog_uids))
			{
				foreach ($users_info_query as $user)
				{
					$users_info[$user['uid']] = $user;
				}
			}
		}

		if ($dialog_ids)
		{
			$last_message = $this->model('message')->get_last_messages($dialog_ids);
		}

		if ($inbox_dialog)
		{
			foreach ($inbox_dialog as $key => $value)
			{
				if ($value['recipient_uid'] == $this->user_id AND $value['recipient_count']) // 当前处于接收用户
				{
					$data[$key]['user_info'] = $users_info[$value['sender_uid']];

					$data[$key]['unread'] = $value['recipient_unread'];
					$data[$key]['count'] = $value['recipient_count'];
				}
				else if ($value['sender_uid'] == $this->user_id AND $value['sender_count']) // 当前处于发送用户
				{
					$data[$key]['user_info'] = $users_info[$value['recipient_uid']];

					$data[$key]['unread'] = $value['sender_unread'];
					$data[$key]['count'] = $value['sender_count'];
				}

				$data[$key]['last_message'] = $last_message[$value['id']];
				$data[$key]['update_time'] = $value['update_time'];
				$data[$key]['id'] = $value['id'];
			}
		}

		TPL::assign('list', $data);

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/inbox/'),
			'total_rows' => $inbox_total_rows,
			'per_page' => S::get_int('contents_per_page')
		)));

		TPL::output('inbox/index');
	}

	public function delete_dialog_action()
	{
		$this->model('message')->delete_dialog(H::GET('dialog_id'), $this->user_id);

		if ($_SERVER['HTTP_REFERER'])
		{
			H::redirect($_SERVER['HTTP_REFERER']);
		}
		else
		{
			H::redirect('/inbox/');
		}
	}

	public function read_action()
	{
		if (!$dialog = $this->model('message')->get_dialog_by_id(H::GET('id')))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/inbox/');
		}

		if ($dialog['recipient_uid'] != $this->user_id AND $dialog['sender_uid'] != $this->user_id)
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/inbox/');
		}

		if ($dialog['sender_uid'] != $this->user_id)
		{
			$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['sender_uid']);
		}
		else
		{
			$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['recipient_uid']);
		}

		$this->model('message')->set_message_read(H::GET('id'), $this->user_id);

		if ($list = $this->model('message')->get_message_by_dialog_id(H::GET('id')))
		{
			foreach ($list as $key => $val)
			{
				if ($dialog['sender_uid'] == $this->user_id AND $val['sender_remove'])
				{
					unset($list[$key]);
				}
				else if ($dialog['sender_uid'] != $this->user_id AND $val['recipient_remove'])
				{
					unset($list[$key]);
				}
				else
				{
					$list[$key]['message'] = $val['message'];
				}
			}
		}

		$this->crumb(AWS_APP::lang()->_t('私信对话') . ': ' . $recipient_user['user_name']);

		// TODO: 把所有的 dialog 改为 dialogue
		TPL::assign('dialog_id', $dialog['id']);
		TPL::assign('list', $list);
		TPL::assign('recipient_user', $recipient_user);

		TPL::output('inbox/read');
	}
}
