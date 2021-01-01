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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function index_action()
	{
		if ($this->user_id)
		{
			HTTP::redirect('/');
		}

		if (get_setting('register_type') == 'close')
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'), '/');
		}
		else if (get_setting('register_type') == 'invite' AND !$_GET['icode'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站只接受邀请注册'), '/');
		}

		if ($_GET['icode'])
		{
			{
				H::redirect_msg(AWS_APP::lang()->_t('邀请码无效或已经使用, 请使用新的邀请码'), '/');
			}
		}

		$this->crumb(AWS_APP::lang()->_t('注册'));

		TPL::import_css('css/register.css');

		TPL::output('account/register');
	}

	public function captcha_action()
	{
		AWS_APP::captcha()->generate();
	}

}