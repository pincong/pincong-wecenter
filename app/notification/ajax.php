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

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function setup()
	{
		H::no_cache_header();
	}

	public function mark_as_read_action()
	{
		$this->model('notification')->mark_as_read(H::GET('notification_id'), $this->user_id);

		H::ajax_success();
	}

	public function mark_all_as_read_action()
	{
		$this->model('notification')->mark_all_as_read($this->user_id);

		H::ajax_success();
	}
}