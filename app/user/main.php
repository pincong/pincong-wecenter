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
	public function admin_log_action()
	{
		$this->crumb(AWS_APP::lang()->_t('管理记录'));

		TPL::output('user/admin_log');
	}

	public function list_admin_logs_action()
	{
		$log_list = $this->model('user')->list_admin_logs($_GET['uid'], $_GET['admin_uid'], $_GET['type'], $_GET['status'], $_GET['page'], get_setting('contents_per_page'));

		TPL::assign('list', $log_list);

		TPL::output('user/list_admin_logs_template');
	}

}
