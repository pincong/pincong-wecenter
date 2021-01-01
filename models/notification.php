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

class notification_class extends AWS_MODEL
{

	private function check_notification_setting($recipient_uid, $action)
	{
		$notification_setting = $this->model('account')->get_notification_setting_by_uid($recipient_uid);

		// 默认不认置则全部都发送
		if (!$notification_setting['data'])
		{
			return true;
		}

		// 设置则不发送
		if (in_array($action, $notification_setting['data']))
		{
			return false;
		}

		return true;
	}

	public function send($sender_uid, $recipient_uid, $action, $item_type, $item_id, $child_type = null, $child_id = 0)
	{
		if (intval($recipient_uid) <= 0)
		{
			return;
		}

		if (!$this->check_notification_setting($recipient_uid, $action))
		{
			return;
		}

		$add_time = fake_time();
		if ($notification_id = $this->insert('notification', array(
			'sender_uid' => intval($sender_uid),
			'recipient_uid' => intval($recipient_uid),
			'action' => $action,
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'child_type' => $child_type,
			'child_id' => intval($child_id),
			'add_time' => $add_time,
			'read_flag' => 0
		)))
		{
			$this->model('account')->update_notification_unread($recipient_uid);
			return $notification_id;
		}
	}

	public function mark_as_read($notification_id, $uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), 'notification_id = ' . intval($notification_id) . ' AND recipient_uid = ' . intval($uid));

		$this->model('account')->update_notification_unread($uid);
	}

	public function mark_all_as_read($uid)
	{
		$this->update('notification', array(
			'read_flag' => 1
		), 'recipient_uid = ' . intval($uid));

		$this->model('account')->update_notification_unread($uid);
	}

	/**
	 * 获得通知列表
	 * 
	 * @param $read_status 0 - 未读, 1 - 已读, other - 所有
	 */
	public function list_notifications($recipient_uid, $read_status, $page, $per_page)
	{

	}

	public function delete_expired_data()
	{
		$days = intval(get_setting('expiration_notifications'));
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
		$this->delete('notification', 'add_time < ' . $time_before);
	}
}
