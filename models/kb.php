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

class kb_class extends AWS_MODEL
{

	public function list($page, $per_page)
	{
		return $this->fetch_page('knowledge', null, 'id ASC', $page, $per_page);
	}

	public function get($id)
	{
		$id = intval($id);
		if ($id <= 0)
		{
			return false;
		}

		static $array;

		if (!$array[$id])
		{
			if ($item = $this->fetch_row('knowledge', 'id = ' . $id))
			{
				$array[$id] = $item;
			}
		}

		return $array[$id];
	}

	public function add(&$title, &$message, $uid, $last_uid)
	{
		$now = fake_time();

		$id = $this->insert('knowledge', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message),
			'uid' => $uid,
			'last_uid' => $last_uid,
			'add_time' => $now,
			'update_time' => $now,
		));

		return $id;
	}

	public function edit($id, &$title, &$message, $uid, $last_uid)
	{
		$now = fake_time();

		$this->update('knowledge', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message),
			'uid' => $uid,
			'last_uid' => $last_uid,
			'update_time' => $now,
		), 'id = ' . intval($id));
	}

	public function remark($id, &$remarks)
	{
		$this->update('knowledge', array(
			'remarks' => htmlspecialchars($remarks),
		), 'id = ' . intval($id));
	}


}