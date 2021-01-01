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

	public function avatar_upload_action()
	{
		if (S::get('upload_enable') == 'N')
		{
			H::ajax_error((_t('本站未开启上传功能')));
		}

		if (!check_user_operation_interval('profile', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('avatar')->upload_avatar('aws_upload_file', $this->user_id, $error))
		{
			H::ajax_error($error);
		}

		set_user_operation_last_time('profile', $this->user_id);

		H::ajax_response(array(
			'thumb' => S::get('upload_url') . '/avatar/' . $this->model('avatar')->get_avatar_path($this->user_id, 'max') . '?' . rand(1, 999)
		));
	}


	public function privacy_setting_action()
	{
		$this->model('account')->update_user_fields(array(
			'inbox_recv' => H::POST_I('inbox_recv')
		), $this->user_id);

		$this->model('notification')->set_user_ignore_list($this->user_id, H::POST('notification_settings'), true);

		H::ajax_error((_t('隐私设置保存成功')));
	}

	public function profile_setting_action()
	{
		if ($user_name = H::POST_S('user_name'))
		{
			if ($user_name != $this->user_info['user_name'])
			{
				if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_change_username'))
				{
					H::ajax_error((_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
				}
				if ($check_result = $this->model('register')->check_username_char($user_name))
				{
					H::ajax_error($check_result);
				}
				if ($this->model('register')->check_username_sensitive_words($user_name))
				{
					H::ajax_error((_t('用户名不符合规则')));
				}
				if ($this->model('account')->username_exists($user_name))
				{
					H::ajax_error((_t('已经存在相同的姓名, 请重新填写')));
				}
				$this->model('account')->update_user_name($user_name, $this->user_id);

				$this->model('currency')->process($this->user_id, 'CHANGE_USERNAME', S::get('currency_system_config_change_username'), '修改用户名');
			}
		}

		$update_data['sex'] = H::POST_I('sex');
		if ($update_data['sex'] < 0 OR $update_data['sex'] > 3)
		{
			$update_data['sex'] = 0;
		}

		$update_data['signature'] = htmlspecialchars(H::POST_S('signature'));

		// 更新主表
		$this->model('account')->update_user_fields($update_data, $this->user_id);

		$this->model('account')->set_default_timezone(H::POST_S('default_timezone'), $this->user_id);

		H::ajax_error((_t('个人资料保存成功')));
	}

}
