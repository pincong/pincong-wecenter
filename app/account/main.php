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
		H::no_cache_header();
	}

	public function index_action()
	{
		H::redirect('/profile/');
	}

	public function logout_action()
	{
		if (!H::is_post())
		{
			$this->crumb(_t('退出'));
			TPL::output("account/logout");
		}
		else
		{
			if (!check_http_referer())
			{
				H::redirect_msg(_t('错误的请求'), '/');
			}
			$this->model('login')->logout();
			$return_url = '/';
			H::redirect($return_url);
		}
	}

	public function change_password_action()
	{
		$this->crumb(_t('修改密码'));

		TPL::assign('token', AWS_APP::form()->create_csrf_token(600, 'account_change_password'));

		TPL::output("account/change_password");
	}

	public function password_updated_action()
	{
		$url = '/';

		H::redirect_msg(_t('密码修改成功, 请您妥善保管新密码'), $url);
	}

}