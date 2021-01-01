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

class message_class extends AWS_MODEL
{
	private $key;
	public function get_key()
	{
		if (!$this->key)
		{
			$this->key = AWS_APP::crypt()->new_key(G_SECUKEY);
		}
		return $this->key;
	}

	public function encrypt(&$message)
	{
		if (!$message)
		{
			return '';
		}
		return AWS_APP::crypt()->encode($message, $this->get_key());
	}

	public function decrypt(&$message)
	{
		if (!$message)
		{
			return '';
		}
		return AWS_APP::crypt()->decode($message, $this->get_key());
	}

	public function test_permission(&$this_user, &$recipient_user)
	{
		if ($this_user['permission']['dispatch_pm'])
		{
			return 1; // 自己可以给任何人发送例外私信
		}

		$recipient_user_group = $this->model('usergroup')->get_user_group_by_user_info($recipient_user);
		// 例外情况 如果对方拥有['receive_pm']权限
		if ($recipient_user_group['permission']['receive_pm'])
		{
			return 2; // 对方可以接收例外私信
		}

		if (!$this_user['permission']['send_pm'])
		{
			return false; // 自己不可以发送私信
		}
		if (!$recipient_user_group['permission']['send_pm'])
		{
			return 0; // 对方不可以发送私信
		}

		return true; // 双方都可以发送私信
	}

	public function send_message($sender_uid, $recipient_uid, $message)
	{
		if (!$sender_uid OR !$recipient_uid OR !$message)
		{
			return false;
		}

        $now = fake_time();

		if (! $inbox_dialog = $this->get_dialog_by_user($sender_uid, $recipient_uid))
		{
			$inbox_dialog_id = $this->insert('inbox_dialog', array(
				'sender_uid' => $sender_uid,
				'sender_unread' => 0,
				'recipient_uid' => $recipient_uid,
				'recipient_unread' => 0,
				'add_time' => $now,
				'update_time' => $now,
				'sender_count' => 0,
				'recipient_count' => 0
			));
		}
		else
		{
			$inbox_dialog_id = $inbox_dialog['id'];
		}

		$message_id = $this->insert('inbox', array(
			'dialog_id' => $inbox_dialog_id,
			'message' => $this->encrypt(htmlspecialchars($message)),
			'add_time' => $now,
			'uid' => $sender_uid
		));

		$this->update_dialog_count($inbox_dialog_id, $sender_uid);

		$this->model('account')->update_inbox_unread($recipient_uid);
		//$this->model('account')->update_inbox_unread($sender_uid);

		return $message_id;
	}

	public function set_message_read($dialog_id, $uid, $receipt = true)
	{
		if (! $inbox_dialog = $this->get_dialog_by_id($dialog_id))
		{
			return false;
		}

        $now = fake_time();

		if ($inbox_dialog['sender_uid'] == $uid)
		{
			$this->update('inbox_dialog', array(
				'sender_unread' => 0
			), 'sender_uid = ' . intval($uid) . ' AND id = ' . intval($dialog_id));

			if ($receipt)
			{
				$this->update('inbox', array(
				'receipt' => $now
				), 'receipt = 0 AND uid = ' . $inbox_dialog['recipient_uid'] . ' AND dialog_id = ' . intval($dialog_id));
			}

		}

		if ($inbox_dialog['recipient_uid'] == $uid)
		{
			$this->update('inbox_dialog', array(
				'recipient_unread' => 0
			), "recipient_uid = " . intval($uid) . " AND id = " . intval($dialog_id));

			if ($receipt)
			{
				$this->update('inbox', array(
					'receipt' => $now
				), 'receipt = 0 AND uid = ' . $inbox_dialog['sender_uid'] . ' AND dialog_id = ' . intval($dialog_id));
			}
		}

		$this->model('account')->update_inbox_unread($uid);

		return true;
	}

	public function update_dialog_count($dialog_id, $uid)
	{
		if (! $inbox_dialog = $this->get_dialog_by_id($dialog_id))
		{
			return false;
		}

		$this->update('inbox_dialog', array(
			'sender_count' => $this->count('inbox', 'uid IN(' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND sender_remove = 0 AND dialog_id = ' . intval($dialog_id)),
			'recipient_count' => $this->count('inbox', 'uid IN(' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND recipient_remove = 0 AND dialog_id = ' . intval($dialog_id)),
			'update_time' => fake_time()
		), 'id = ' . intval($dialog_id));

		if ($inbox_dialog['sender_uid'] == $uid)
		{
			$this->query("UPDATE " . get_table('inbox_dialog') . " SET recipient_unread = recipient_unread + 1 WHERE id = " . intval($dialog_id));
		}
		else
		{
			$this->query("UPDATE " . get_table('inbox_dialog') . " SET sender_unread = sender_unread + 1 WHERE id = " . intval($dialog_id));
		}
	}

	public function get_dialog_by_id($dialog_id)
	{
		return $this->fetch_row('inbox_dialog', 'id = ' . intval($dialog_id));
	}

	public function get_message_by_dialog_id($dialog_id)
	{
		if ($inbox = $this->fetch_all('inbox', 'dialog_id = ' . intval($dialog_id), 'id DESC'))
		{
			foreach ($inbox AS $key => $val)
			{
				$val['message'] = $this->decrypt($val['message']);
				$message[$val['id']] = $val;
			}
		}

		return $message;
	}

	public function delete_dialog($dialog_id, $uid)
	{
		if (! $inbox_dialog = $this->get_dialog_by_id($dialog_id))
		{
			return false;
		}

		if ($inbox_dialog['sender_uid'] == $uid)
		{
			$this->set_message_read($dialog_id, $uid, false);

			$this->update('inbox_dialog', array(
				'sender_count' => 0
			), 'sender_uid = ' . intval($uid) . ' AND id = ' . intval($dialog_id));

			$this->update('inbox', array(
				'sender_remove' => 1
			), 'uid IN (' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND dialog_id = ' . intval($dialog_id));
		}

		if ($inbox_dialog['recipient_uid'] == $uid)
		{
			$this->set_message_read($dialog_id, $inbox_dialog['recipient_uid'], false);

			$this->update('inbox_dialog', array(
				'recipient_count' => 0
			), 'recipient_uid = ' . intval($uid) . ' AND id = ' . intval($dialog_id));

			$this->update('inbox', array(
				'recipient_remove' => 1
			), 'uid IN (' . $inbox_dialog['sender_uid'] . ', ' . $inbox_dialog['recipient_uid'] . ') AND dialog_id = ' . intval($dialog_id));
		}

		$this->model('account')->update_inbox_unread($uid);

		return true;
	}

	public function get_inbox_message($page = 1, $limit = 10, $uid = null)
	{
		return $this->fetch_page('inbox_dialog', '(sender_uid = ' . intval($uid) . ' AND sender_count > 0) OR (recipient_uid = ' . intval($uid) . ' AND recipient_count > 0)', 'update_time DESC', $page, $limit);
	}

	public function get_last_messages($dialog_ids)
	{
		if (!is_array($dialog_ids))
		{
			return false;
		}

		foreach ($dialog_ids as $dialog_id)
		{
			$dialog_message = $this->fetch_row('inbox', 'dialog_id = ' . intval($dialog_id), 'id DESC');

			$last_message[$dialog_id] = truncate_text($this->decrypt($dialog_message['message']), 60);
		}

		return $last_message;
	}

	public function get_dialog_by_user($sender_uid, $recipient_uid)
	{
		return $this->fetch_row('inbox_dialog', "(`sender_uid` = " . intval($sender_uid) . " AND `recipient_uid` = " . intval($recipient_uid) . ") OR (`recipient_uid` = " . intval($sender_uid) . " AND `sender_uid` = " . intval($recipient_uid) . ")");
	}

	public function removed_message_clean()
	{
		$this->delete('inbox', 'sender_remove = 1 AND recipient_remove = 1');
		$this->delete('inbox_dialog', 'sender_count = 0 AND recipient_count = 0');
	}

	public function delete_expired_messages()
	{
		$days = S::get_int('expiration_private_messages');
		if (!$days)
		{
			return;
		}
		$seconds = $days * 24 * 3600;
		$time_before = real_time() - $seconds;
		if ($time_before < 0)
		{
			$time_before = 0;
		}
		$this->delete('inbox', 'add_time < ' . $time_before);
	}

}