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

class setting extends AWS_CONTROLLER
{
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('设置'));

		TPL::import_css('css/user-setting.css');
	}

	public function index_action()
	{
		HTTP::redirect('/account/setting/profile/');
	}

	public function profile_action()
	{
		$this->crumb(AWS_APP::lang()->_t('基本资料'));

		TPL::output('account/setting/profile');
	}

	public function privacy_action()
	{
		$this->crumb(AWS_APP::lang()->_t('隐私/提醒'));

		TPL::assign('notification_settings', $this->model('account')->get_notification_setting_by_uid($this->user_id));
		TPL::assign('notify_actions', $this->model('notification')->notify_action_details);

		TPL::output('account/setting/privacy');
	}

	public function currency_action()
	{
		$this->crumb(AWS_APP::lang()->_t('我的%s', get_setting('currency_name')));

		TPL::output('account/setting/currency');
	}

	public function security_action()
	{
		$this->crumb(AWS_APP::lang()->_t('安全设置'));

		TPL::output('account/setting/security');
	}

}
