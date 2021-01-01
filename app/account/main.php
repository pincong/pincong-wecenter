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
		HTTP::no_cache_header();
	}

	public function index_action()
	{
		HTTP::redirect('/profile/');
	}

	public function logout_action($return_url = '/')
	{
		if ($_GET['key'] != md5(session_id()))
		{
			H::redirect_msg(AWS_APP::lang()->_t('正在准备退出, 请稍候...'), '/account/logout/?key=' . md5(session_id()));
		}

		$this->model('login')->logout();

		$this->model('admin')->admin_logout();

		{
			HTTP::redirect($return_url);
		}
	}

	public function password_updated_action()
	{
		$url = '/login/';

		H::redirect_msg(AWS_APP::lang()->_t('密码修改成功, 请使用新密码登录'), $url);
	}

}