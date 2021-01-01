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

class invite_class extends AWS_MODEL
{

	public function add_invite($question_id, $sender_uid, $recipient_uid)
	{
		$sender_uid = intval($sender_uid);
		$recipient_uid = intval($recipient_uid);
		if ($recipient_uid <= 0 OR $sender_uid == $recipient_uid)
		{
			return false;
		}

		$this->insert('question_invite', array(
			'question_id' => intval($question_id),
			'sender_uid' => ($sender_uid),
			'recipients_uid' => ($recipient_uid),
			'add_time' => fake_time(),
		));

		$this->model('notification')->send(
			($sender_uid),
			($recipient_uid),
			'INVITE_USER',
			'question', $question_id);
	}

	/**
	 * 发起者取消邀请
	 * @param unknown_type $question_id
	 * @param unknown_type $sender_uid
	 * @param unknown_type $recipients_uid
	 */
	public function cancel_question_invite($question_id, $sender_uid, $recipients_uid)
	{
		return $this->delete('question_invite', [['question_id', 'eq', $question_id, 'i'], ['sender_uid', 'eq', $sender_uid, 'i'], ['recipients_uid', 'eq', $recipients_uid, 'i']]);
	}

	/**
	 * 接收者删除邀请
	 * @param unknown_type $question_invite_id
	 * @param unknown_type $recipients_uid
	 */
	public function delete_question_invite($question_invite_id, $recipients_uid)
	{
		return $this->delete('question_invite', [['question_invite_id', 'eq', $question_invite_id, 'i'], ['recipients_uid', 'eq', $recipients_uid, 'i']]);
	}

	/**
	 * 删除回复邀请
	 * @param unknown_type $question_invite_id
	 * @param unknown_type $recipients_uid
	 */
	public function answer_question_invite($question_id, $recipients_uid)
	{
		$this->delete('question_invite', [['question_id', 'eq', $question_id, 'i'], ['recipients_uid', 'eq', $recipients_uid, 'i']]);

		$this->model('account')->update_question_invite_count($recipients_uid);
	}

	public function has_question_invite($question_id, $recipients_uid)
	{
		$where = [['question_id', 'eq', $question_id, 'i'], ['recipients_uid', 'eq', $recipients_uid, 'i']];
		if ($this->fetch_one('question_invite', 'question_invite_id', $where))
		{
			return true;
		}

		$where = [['parent_id', 'eq', $question_id, 'i'], ['uid', 'eq', $recipients_uid, 'i']];
		if ($this->fetch_one('question_reply', 'id', $where))
		{
			return true;
		}

		return false;
	}

	public function get_invite_users($question_id, $limit = 10)
	{
		if ($invite_users_list = AWS_APP::cache()->get('question_invite_users_' . $question_id))
		{
			return $invite_users_list;
		}
		
		if ($invites = $this->fetch_all('question_invite', ['question_id', 'eq', $question_id, 'i'], 'question_invite_id DESC', $limit))
		{
			foreach ($invites as $key => $val)
			{
				$invite_users[$val['recipients_uid']] = $val['recipients_uid'];
			}

			$invite_users_list = $this->model('account')->get_user_info_by_uids($invite_users);
			
			AWS_APP::cache()->set('question_invite_users_' . $question_id, $invite_users_list, S::get('cache_level_normal'));
		}
		
		return $invite_users_list;
	}

	public function get_invite_question_list($uid, $page, $per_page)
	{
		if ($list = $this->fetch_page('question_invite', ['recipients_uid', 'eq', $uid, 'i'], 'question_invite_id DESC', $page, $per_page))
		{
			foreach ($list as $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}

			$question_infos = $this->model('post')->get_posts_by_ids('question', $question_ids);

			foreach ($list as $key => $val)
			{
				$list[$key]['question_info'] = $question_infos[$val['question_id']];
			}

			return $list;
		}
	}

}
