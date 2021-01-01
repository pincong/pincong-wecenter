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

class pm_class extends AWS_MODEL
{
	private static $cached_conversations = array();

	public function test_permissions($this_user, $recipient_user, &$error)
	{
		if (!$recipient_user['public_key'])
		{
			$error = _t('%s 没有公钥, 无法发送私信', $recipient_user['user_name']);
			return false;
		}

		if ($this_user['uid'] == $recipient_user['uid'])
		{
			// 不在此处检查自己
			return true;
		}

		if ($this_user['permission']['dispatch_pm'])
		{
			// 自己可以给任何人发送例外私信
			return true;
		}

		if ($recipient_user['forbidden'])
		{
			$error = _t('%s 已经被禁止登录', $recipient_user['user_name']);
			return false;
		}

		$recipient_user_group = $this->model('usergroup')->get_user_group_by_user_info($recipient_user);
		// 例外情况 如果对方拥有['receive_pm']权限
		if ($recipient_user_group['permission']['receive_pm'])
		{
			// 对方可以接收例外私信
		}
		else
		{
			if (!$this_user['permission']['send_pm'])
			{
				// 自己不可以发送私信
				$error = _t('你的声望还不够, 不能给 %s 发送私信', $recipient_user['user_name']);
				return false;
			}
			if (!$recipient_user_group['permission']['send_pm'])
			{
				// 对方不可以发送私信
				$error = _t('%s 的声望还不够, 不能接收你的私信', $recipient_user['user_name']);
				return false;
			}
			// 双方都可以发送私信
		}

		$inbox_recv = $recipient_user['inbox_recv'];
		if ($inbox_recv != 1 AND $inbox_recv != 2 AND $inbox_recv != 3)
		{
			$inbox_recv = S::get_int('default_inbox_recv');
		}

		if ($inbox_recv == 2) // 2为拒绝任何人
		{
			$error = _t('%s 设置了拒绝接收任何人的私信', $recipient_user['user_name']);
			return false;
		}
		else if ($inbox_recv == 3) // 3为任何人
		{
			return true;
		}

		/*if (!$this->model('userfollow')->user_follow_check($recipient_user['uid'], $this->user_id))
		{
			$error = _t('%s 未关注你, 你无法给 Ta 发送私信', $recipient_user['user_name']);
			return false;
		}*/

		return true;
	}


	public function find_conversation_by_uids($uids)
	{
		if (!is_array($uids))
		{
			return false;
		}
		$count =  count($uids);
		if ($count < 1)
		{
			return false;
		}

		switch ($count)
		{
			case 5:
				$where = [
					['member_count', 'eq', 5],
					['uid_1', 'in', $uids],
					['uid_2', 'in', $uids],
					['uid_3', 'in', $uids],
					['uid_4', 'in', $uids],
					['uid_5', 'in', $uids],
				];
				break;
			case 4:
				$where = [
					['member_count', 'eq', 4],
					['uid_1', 'in', $uids],
					['uid_2', 'in', $uids],
					['uid_3', 'in', $uids],
					['uid_4', 'in', $uids],
				];
				break;
			case 3:
				$where = [
					['member_count', 'eq', 3],
					['uid_1', 'in', $uids],
					['uid_2', 'in', $uids],
					['uid_3', 'in', $uids],
				];
				break;
			case 2:
				$where = [
					['member_count', 'eq', 2],
					['uid_1', 'in', $uids],
					['uid_2', 'in', $uids],
				];
				break;
			default:
				$where = [
					['member_count', 'eq', 1],
					['uid_1', 'eq', $uids[0]],
				];
				break;
		}

		return $this->fetch_row('pm_conversation', $where);
	}

	private function count_conversation_unread_messages($conversation_id, $uids)
	{
		$condition = ['conversation_id', 'eq', $conversation_id];
		return array(
			'unread_1' => !$uids[0] ? 0 : $this->count('pm_message', [$condition, ['receipt_1', 'eq', 0]]),
			'unread_2' => !$uids[1] ? 0 : $this->count('pm_message', [$condition, ['receipt_2', 'eq', 0]]),
			'unread_3' => !$uids[2] ? 0 : $this->count('pm_message', [$condition, ['receipt_3', 'eq', 0]]),
			'unread_4' => !$uids[3] ? 0 : $this->count('pm_message', [$condition, ['receipt_4', 'eq', 0]]),
			'unread_5' => !$uids[4] ? 0 : $this->count('pm_message', [$condition, ['receipt_5', 'eq', 0]]),
		);
	}

	private function insert_message($conversation_id, $sender_uid, $uids, $messages, $ts)
	{
		$message_id = $this->insert('pm_message', array(
			'conversation_id' => $conversation_id,
			'sender_uid' => $sender_uid,
			'add_time' => $ts,
			'plaintext' => null,
			'receipt_1' => $uids[0] == $sender_uid ? $ts : 0,
			'receipt_2' => $uids[1] == $sender_uid ? $ts : 0,
			'receipt_3' => $uids[2] == $sender_uid ? $ts : 0,
			'receipt_4' => $uids[3] == $sender_uid ? $ts : 0,
			'receipt_5' => $uids[4] == $sender_uid ? $ts : 0,
			'message_1' => $messages[$uids[0]] ?? null,
			'message_2' => $messages[$uids[1]] ?? null,
			'message_3' => $messages[$uids[2]] ?? null,
			'message_4' => $messages[$uids[3]] ?? null,
			'message_5' => $messages[$uids[4]] ?? null,
		));
		if (!$message_id)
		{
			return false;
		}

		$data = $this->count_conversation_unread_messages($conversation_id, $uids);
		$data['last_message_id'] = $message_id;
		$data['update_time'] = $ts;

		$this->update('pm_conversation', $data, ['id', 'eq', $conversation_id]);

		foreach ($uids as $uid)
		{
			if ($uid == $sender_uid)
			{
				continue;
			}
			$this->update_inbox_unread($uid);
		}

		return $message_id;
	}

	public function send_message($conversation_id, $sender_uid, $messages)
	{
		if (!$val = $this->get_conversation_by_id_and_uid($conversation_id, $sender_uid))
		{
			return false;
		}
		$uids = [
			$val['uid_1'],
			$val['uid_2'],
			$val['uid_3'],
			$val['uid_4'],
			$val['uid_5'],
		];
		$ts = fake_time();
		return $this->insert_message($conversation_id, $sender_uid, $uids, $messages, $ts);
	}

	public function new_conversation($sender_uid, $messages)
	{
		$uids = [];
		foreach ($messages as $uid => $message)
		{
			$uids[] = intval($uid);
		}
		$member_count = count($uids);
		if ($member_count < 5)
		{
			$uids = array_pad($uids, 5, 0);
		}
		$ts = fake_time();

		$conversation_id = $this->insert('pm_conversation', array(
			'last_message_id' => 0,
			'add_time' => $ts,
			'update_time' => $ts,
			'member_count' => $member_count,
			'uid_1' => $uids[0],
			'uid_2' => $uids[1],
			'uid_3' => $uids[2],
			'uid_4' => $uids[3],
			'uid_5' => $uids[4],
		));

		if (!$conversation_id)
		{
			return false;
		}

		if (!$this->insert_message($conversation_id, $sender_uid, $uids, $messages, $ts))
		{
			return false;
		}
		return $conversation_id;
	}

	public function notify($recipient_uid, $message)
	{
		$recipient_uid = intval($recipient_uid);
		if ($recipient_uid <= 0)
		{
			return false;
		}

		$ts = fake_time();

		if (!$conversation = $this->find_conversation_by_uids([$recipient_uid]))
		{
			$conversation_id = $this->insert('pm_conversation', array(
				'last_message_id' => 0,
				'add_time' => $ts,
				'update_time' => $ts,
				'member_count' => 1,
				'uid_1' => $recipient_uid,
				'uid_2' => 0,
				'uid_3' => 0,
				'uid_4' => 0,
				'uid_5' => 0,
			));
		}
		else
		{
			$conversation_id = $conversation['id'];
		}

		if (!$conversation_id)
		{
			return false;
		}

		$message_id = $this->insert('pm_message', array(
			'conversation_id' => $conversation_id,
			'sender_uid' => $recipient_uid,
			'add_time' => $ts,
			'plaintext' => $message,
			'receipt_1' => 0,
			'receipt_2' => 0,
			'receipt_3' => 0,
			'receipt_4' => 0,
			'receipt_5' => 0,
			'message_1' => null,
			'message_2' => null,
			'message_3' => null,
			'message_4' => null,
			'message_5' => null,
		));
		if (!$message_id)
		{
			return false;
		}

		$data = $this->count_conversation_unread_messages($conversation_id, [$recipient_uid, 0, 0, 0, 0]);
		$data['last_message_id'] = $message_id;
		$data['update_time'] = $ts;

		$this->update('pm_conversation', $data, ['id', 'eq', $conversation_id]);

		$this->update_inbox_unread($recipient_uid);

		return $conversation_id;
	}

	public function get_conversations($uid, $page, $per_page)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		$this->_total_conversations = 0;

		$conversations = $this->fetch_page('pm_conversation', [
			['uid_1', 'eq', $uid],
			'or',
			['uid_2', 'eq', $uid],
			'or',
			['uid_3', 'eq', $uid],
			'or',
			['uid_4', 'eq', $uid],
			'or',
			['uid_5', 'eq', $uid],
		], 'last_message_id DESC', $page, $per_page);

		if ($conversations)
		{
			$this->_total_conversations = $this->total_rows();

			$uids = [];
			$last_message_ids = [];
			foreach ($conversations as $val)
			{
				if ($val['uid_1']) $uids[$val['uid_1']] = $val['uid_1'];
				if ($val['uid_2']) $uids[$val['uid_2']] = $val['uid_2'];
				if ($val['uid_3']) $uids[$val['uid_3']] = $val['uid_3'];
				if ($val['uid_4']) $uids[$val['uid_4']] = $val['uid_4'];
				if ($val['uid_5']) $uids[$val['uid_5']] = $val['uid_5'];

				if ($val['last_message_id'])
				{
					$last_message_ids[] = intval($val['last_message_id']);
				}
			}

			$users = $this->model('account')->get_user_info_by_uids($uids);
			$last_messages = $this->get_user_messages($last_message_ids, $uid);

			foreach ($conversations as &$val)
			{
				$val['users'] = [];
				if ($val['uid_1'] AND isset($users[$val['uid_1']])) $val['users'][$val['uid_1']] = $users[$val['uid_1']];
				if ($val['uid_2'] AND isset($users[$val['uid_2']])) $val['users'][$val['uid_2']] = $users[$val['uid_2']];
				if ($val['uid_3'] AND isset($users[$val['uid_3']])) $val['users'][$val['uid_3']] = $users[$val['uid_3']];
				if ($val['uid_4'] AND isset($users[$val['uid_4']])) $val['users'][$val['uid_4']] = $users[$val['uid_4']];
				if ($val['uid_5'] AND isset($users[$val['uid_5']])) $val['users'][$val['uid_5']] = $users[$val['uid_5']];

				if ($val['uid_1'] == $uid) $val['unread'] = $val['unread_1'];
				elseif ($val['uid_2'] == $uid) $val['unread'] = $val['unread_2'];
				elseif ($val['uid_3'] == $uid) $val['unread'] = $val['unread_3'];
				elseif ($val['uid_4'] == $uid) $val['unread'] = $val['unread_4'];
				elseif ($val['uid_5'] == $uid) $val['unread'] = $val['unread_5'];
				else $val['unread'] = null;

				$last_message = $last_messages[$val['last_message_id']] ?? null;
				$this->process_message($last_message, $uid, $val['uid_1'], $val['uid_2'], $val['uid_3'], $val['uid_4'], $val['uid_5']);
				$val['last_message'] = $last_message;
			}
			unset($val);
		}

		return $conversations;
	}

	public function total_conversations()
	{
		return $this->_total_conversations;
	}

	private function process_message(&$val, $recipient_uid, $uid_1, $uid_2, $uid_3, $uid_4, $uid_5)
	{
		if (!$val)
		{
			return;
		}

		if (!!$val['plaintext']) $val['message'] = null;
		elseif ($uid_1 == $recipient_uid) $val['message'] = $val['message_1'];
		elseif ($uid_2 == $recipient_uid) $val['message'] = $val['message_2'];
		elseif ($uid_3 == $recipient_uid) $val['message'] = $val['message_3'];
		elseif ($uid_4 == $recipient_uid) $val['message'] = $val['message_4'];
		elseif ($uid_5 == $recipient_uid) $val['message'] = $val['message_5'];
		else $val['message'] = null;

		unset($val['message_1'], $val['message_2'], $val['message_3'], $val['message_4'], $val['message_5']);

		$val['receipts'] = [];
		if ($val['receipt_1'] AND $uid_1) $val['receipts'][$uid_1] = $val['receipt_1'];
		if ($val['receipt_2'] AND $uid_2) $val['receipts'][$uid_2] = $val['receipt_2'];
		if ($val['receipt_3'] AND $uid_3) $val['receipts'][$uid_3] = $val['receipt_3'];
		if ($val['receipt_4'] AND $uid_4) $val['receipts'][$uid_4] = $val['receipt_4'];
		if ($val['receipt_5'] AND $uid_5) $val['receipts'][$uid_5] = $val['receipt_5'];
	}

	private function get_user_messages($message_ids, $uid)
	{
		$result = [];
		if ($message_ids)
		{
			$messages = $this->fetch_all('pm_message', ['id', 'in', $message_ids]);
			if ($messages)
			{
				foreach ($messages as $val)
				{
					$result[$val['id']] = $val;
				}
			}
		}
		return $result;
	}

	public function count_conversation_messages($conversation_id)
	{
		return $this->count('pm_message', ['conversation_id', 'eq', $conversation_id, 'i']);
	}

	public function read_conversation_messages($conversation_id, $uid, $page, $per_page)
	{
		$conversation_id = intval($conversation_id);

		$conversation = $this->get_conversation($conversation_id, $uid, true);
		if (!$conversation)
		{
			return false;
		}

		if ($conversation['uid_1'] == $uid)
		{
			$key_receipt = 'receipt_1';
			$key_unread = 'unread_1';
		}
		elseif ($conversation['uid_2'] == $uid)
		{
			$key_receipt = 'receipt_2';
			$key_unread = 'unread_2';
		}
		elseif ($conversation['uid_3'] == $uid)
		{
			$key_receipt = 'receipt_3';
			$key_unread = 'unread_3';
		}
		elseif ($conversation['uid_4'] == $uid)
		{
			$key_receipt = 'receipt_4';
			$key_unread = 'unread_4';
		}
		elseif ($conversation['uid_5'] == $uid)
		{
			$key_receipt = 'receipt_5';
			$key_unread = 'unread_5';
		}
		else
		{
			return false;
		}

		$unread_message_ids = [];
		$messages = $this->fetch_all('pm_message', ['conversation_id', 'eq', $conversation_id], 'id DESC', $page, $per_page);
		if ($messages)
		{
			foreach ($messages as &$val)
			{
				if (!$val[$key_receipt])
				{
					$unread_message_ids[] = intval($val['id']);
				}
				$this->process_message($val, $uid, $conversation['uid_1'], $conversation['uid_2'], $conversation['uid_3'], $conversation['uid_4'], $conversation['uid_5']);
			}
			unset($val);

			if ($unread_message_ids)
			{
				$this->update('pm_message', array(
					$key_receipt => fake_time()
				), ['id', 'in', $unread_message_ids]);
			}
		}

		if ($unread_message_ids OR intval($page) <= 1)
		{
			$this->update('pm_conversation', array(
				$key_unread => $this->count('pm_message', [['conversation_id', 'eq', $conversation_id], [$key_receipt, 'eq', 0]])
			), ['id', 'eq', $conversation_id]);

			$this->update_inbox_unread($uid);
		}

		return $messages;
	}

	private function get_conversation_by_id_and_uid($id, $uid)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		$id = intval($id);
		$val = self::$cached_conversations[$id] ?? null;
		if (isset($val))
		{
			if (!is_array($val) OR (
				$val['uid_1'] != $uid AND
				$val['uid_2'] != $uid AND
				$val['uid_3'] != $uid AND
				$val['uid_4'] != $uid AND
				$val['uid_5'] != $uid
			)) return false;

			return $val;
		}
		$val = $this->fetch_row('pm_conversation', [['id', 'eq', $id], [
			['uid_1', 'eq', $uid],
			'or',
			['uid_2', 'eq', $uid],
			'or',
			['uid_3', 'eq', $uid],
			'or',
			['uid_4', 'eq', $uid],
			'or',
			['uid_5', 'eq', $uid],
		]]);

		self::$cached_conversations[$id] = $val;
		return $val;
	}

	public function get_conversation($id, $uid, $without_users = false)
	{
		if ($val = $this->get_conversation_by_id_and_uid($id, $uid))
		{
			$val['uids'] = [];
			if ($val['uid_1']) $val['uids'][$val['uid_1']] = $val['uid_1'];
			if ($val['uid_2']) $val['uids'][$val['uid_2']] = $val['uid_2'];
			if ($val['uid_3']) $val['uids'][$val['uid_3']] = $val['uid_3'];
			if ($val['uid_4']) $val['uids'][$val['uid_4']] = $val['uid_4'];
			if ($val['uid_5']) $val['uids'][$val['uid_5']] = $val['uid_5'];

			if (!$without_users)
			{
				$users = $this->model('account')->get_user_info_by_uids($val['uids']);
				$val['users'] = [];
				if ($val['uid_1'] AND isset($users[$val['uid_1']])) $val['users'][$val['uid_1']] = $users[$val['uid_1']];
				if ($val['uid_2'] AND isset($users[$val['uid_2']])) $val['users'][$val['uid_2']] = $users[$val['uid_2']];
				if ($val['uid_3'] AND isset($users[$val['uid_3']])) $val['users'][$val['uid_3']] = $users[$val['uid_3']];
				if ($val['uid_4'] AND isset($users[$val['uid_4']])) $val['users'][$val['uid_4']] = $users[$val['uid_4']];
				if ($val['uid_5'] AND isset($users[$val['uid_5']])) $val['users'][$val['uid_5']] = $users[$val['uid_5']];
			}
		}
		return $val;
	}

	public function delete_message($id, $uid)
	{
		$id = intval($id);
		$uid = intval($uid);

		$this->update('pm_message', array(
			'plaintext' => null,
			'message_1' => null,
			'message_2' => null,
			'message_3' => null,
			'message_4' => null,
			'message_5' => null,
		), [['id', 'eq', $id], ['sender_uid', 'eq', $uid]]);
	}

	public function exit_conversation($id, $uid)
	{
		$id = intval($id);
		$uid = intval($uid);

		if (!$val = $this->get_conversation($id, $uid, true))
		{
			return false;
		}

		$this->delete('pm_message', [['conversation_id', 'eq', $id], ['sender_uid', 'eq', $uid]]);

		if (count($val['uids']) < 2)
		{
			$this->delete('pm_conversation', ['id', 'eq', $id]);
			return true;
		}

		$uids = [
			$val['uid_1'] == $uid ? 0 : $val['uid_1'],
			$val['uid_2'] == $uid ? 0 : $val['uid_2'],
			$val['uid_3'] == $uid ? 0 : $val['uid_3'],
			$val['uid_4'] == $uid ? 0 : $val['uid_4'],
			$val['uid_5'] == $uid ? 0 : $val['uid_5'],
		];

		$data = $this->count_conversation_unread_messages($id, $uids);
		if ($val['uid_1'] == $uid) $data['uid_1'] = 0;
		if ($val['uid_2'] == $uid) $data['uid_2'] = 0;
		if ($val['uid_3'] == $uid) $data['uid_3'] = 0;
		if ($val['uid_4'] == $uid) $data['uid_4'] = 0;
		if ($val['uid_5'] == $uid) $data['uid_5'] = 0;
		$this->update('pm_conversation', $data, ['id', 'eq', $id]);

		foreach ($val['uids'] as $uid)
		{
			$this->update_inbox_unread($uid);
		}

		return true;
	}

	private function update_inbox_unread($uid)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return;
		}

		$sum_1 = $this->sum('pm_conversation', 'unread_1', ['uid_1', 'eq', $uid]);
		$sum_2 = $this->sum('pm_conversation', 'unread_2', ['uid_2', 'eq', $uid]);
		$sum_3 = $this->sum('pm_conversation', 'unread_3', ['uid_3', 'eq', $uid]);
		$sum_4 = $this->sum('pm_conversation', 'unread_4', ['uid_4', 'eq', $uid]);
		$sum_5 = $this->sum('pm_conversation', 'unread_5', ['uid_5', 'eq', $uid]);

		$this->update('users', array(
			'inbox_unread' => $sum_1 + $sum_2 + $sum_3 + $sum_4 + $sum_5
		), ['uid', 'eq', $uid]);
	}

}