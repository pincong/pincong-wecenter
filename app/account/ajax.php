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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		$rule_action['actions'] = array(
			'check_username',
			'register_process',
			'login_process',
			'request_find_password',
			'find_password_modify'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function check_username_action()
	{
		if ($this->model('account')->check_username_char($_POST['username']) OR $this->model('account')->check_username_sensitive_words($_POST['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名不符合规则')));
		}
		
		if ($this->model('account')->check_username($_POST['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已被注册')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function register_process_action()
	{
		if (! $_POST['agreement_chk'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意 %s 才能继续', get_setting('user_agreement_name'))));
		}

		if (get_setting('register_type') == 'close')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
		}
		else if (get_setting('register_type') == 'invite' AND !$_POST['icode'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
		}

		if (my_trim($_POST['user_name']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
		}

		if (strlen($_POST['password']) < 6)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('两次输入的密码不一致')));
		}

		if (!check_user_operation_interval_by_uid('register', 0, get_setting('register_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('本站已开启注册频率限制, 请稍后再试')));
		}

		// 检查验证码
		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']) AND get_setting('register_seccode') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名包含无效字符')));
		}
		if ($this->model('account')->check_username_sensitive_words($_POST['user_name']) OR my_trim($_POST['user_name']) != $_POST['user_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名中包含敏感词或系统保留字')));
		}

		if ($this->model('account')->check_username($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
		}

		$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password']);

		if (!$uid)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('注册失败')));
		}

		set_user_operation_last_time_by_uid('register', 0);

		if (isset($_POST['sex']))
		{
			$update_data['sex'] = intval($_POST['sex']);


			$update_attrib_data['signature'] = htmlspecialchars($_POST['signature']);

			// 更新主表
			$this->model('account')->update_users_fields($update_data, $uid);

			// 更新从表
			$this->model('account')->update_users_attrib_fields($update_attrib_data, $uid);
		}

		$this->model('account')->setcookie_logout();
		$this->model('account')->setsession_logout();

		if (HTTP::get_cookie('fromuid'))
		{
			$follow_users = $this->model('account')->get_user_info_by_uid(HTTP::get_cookie('fromuid'));
		}

		if ($follow_users['uid'])
		{
			$this->model('follow')->user_follow_add($uid, $follow_users['uid']);
			$this->model('follow')->user_follow_add($follow_users['uid'], $uid);

			// 邀请注册
		}

		if (get_setting('register_valid_type') == 'N')
		{
			$this->model('account')->active_user_by_uid($uid);
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (get_setting('register_valid_type') == 'N' OR $user_info['group_id'] != 3)
		{
			$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);

			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/home/first_login-TRUE')
				), 1, null));
			}
		}
		else
		{

			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/account/valid_approval/')
				), 1, null));
			}
		}

	}

	public function login_process_action()
	{
		// 检查验证码
		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']) AND get_setting('login_seccode') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
		}


		$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);

		if (is_null($user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该账号已经连续多次尝试登录失败, 为了安全起见, 该账号 %s 分钟内禁止登录', get_setting('limit_login_attempts_interval'))));
		}
		elseif (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}
		
		{
			if ($user_info['forbidden'])
			{
				//H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录')));
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/people/') . $user_info['url_token']
				), 1, null));
			}

			if (get_setting('site_close') == 'Y' AND $user_info['group_id'] != 1)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, get_setting('close_notice')));
			}

			if (get_setting('register_valid_type') == 'approval' AND $user_info['group_id'] == 3)
			{
				$url = get_js_url('/account/valid_approval/');
			}
			else
			{
				if ($_POST['net_auto_login'])
				{
					$expire = 60 * 60 * 24 * 360;
				}

				$this->model('account')->update_user_last_login($user_info['uid']);
				$this->model('account')->setcookie_logout();

				$this->model('account')->setcookie_login($user_info['uid'], $_POST['user_name'], $_POST['password'], $user_info['salt'], $expire);

				if ($user_info['is_first_login'])
				{
					$url = get_js_url('/home/first_login-TRUE');
				}
				else if ($_POST['return_url'] AND !strstr($_POST['return_url'], '/logout') AND
					(strstr($_POST['return_url'], '://') AND strstr($_POST['return_url'], base_url())))
				{
					$url = get_js_url($_POST['return_url']);
				}

			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
			), 1, null));
		}
	}

	public function welcome_message_template_action()
	{

		TPL::output('account/ajax/welcome_message_template');
	}

	public function welcome_get_topics_action()
	{
		if ($topics_list = $this->model('topic')->get_topic_list("discuss_count > 5", 'RAND()', 8))
		{
			foreach ($topics_list as $key => $topic)
			{
				$topics_list[$key]['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic['topic_id']);
			}
		}
		TPL::assign('topics_list', $topics_list);

		TPL::output('account/ajax/welcome_get_topics');
	}

	public function welcome_get_users_action()
	{
		if ($welcome_recommend_users = trim(rtrim(get_setting('welcome_recommend_users'), ',')))
		{
			$welcome_recommend_users = explode(',', $welcome_recommend_users);

			$users_list = $this->model('account')->get_users_list("user_name IN('" . implode("','", $welcome_recommend_users) . "')", 6, true, true, 'RAND()');
		}

		if (!$users_list)
		{
			$users_list = $this->model('account')->get_users_list("reputation > 5 AND last_login > " . (time() - (60 * 60 * 24 * 7)), 6, true, true, 'RAND()');
		}

		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				$users_list[$key]['follow_check'] = $this->model('follow')->user_follow_check($this->user_id, $val['uid']);
			}
		}

		TPL::assign('users_list', $users_list);

		TPL::output('account/ajax/welcome_get_users');
	}

	public function clean_first_login_action()
	{
		$this->model('account')->clean_first_login($this->user_id);

		die('success');
	}

	public function request_find_password_action()
	{
		if (!$user_name = my_trim($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1',  AWS_APP::lang()->_t('请填写用户名')));
		}

		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1',  AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		if (!$user_info = $this->model('account')->get_user_info_by_username($user_name))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1',  AWS_APP::lang()->_t('用户名不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/account/find_password/modify/uid-') . $user_info['uid']
		), 1, null));
	}

	public function find_password_modify_action()
	{
		if (!$recovery_code = my_trim($_POST['recovery_code']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1',  AWS_APP::lang()->_t('请填写恢复码')));
		}

		if (!$_POST['password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请输入密码')));
		}

		if (strlen($_POST['password']) < 6)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('两次输入的密码不一致')));
		}

		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		$user_info = $this->model('account')->get_user_info_by_uid(intval($_POST['uid']));

		if (!$this->model('account')->verify_user_recovery_code($user_info['uid'], $recovery_code))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('恢复码无效')));
		}

		$this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $user_info['uid'], $user_info['salt']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/account/find_password/process_success/')
		), 1, null));
	}

	public function avatar_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => get_setting('allowed_upload_types'),
			'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, '', 1),
			'is_image' => TRUE,
			'max_size' => get_setting('upload_size_limit'),
			'file_name' => $this->model('account')->get_avatar($this->user_id, '', 2),
			'encrypt_name' => FALSE
		))->do_upload('aws_upload_file');

		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
            {
                default:
                    die("{'error':'错误代码: " . AWS_APP::upload()->get_error() . "'}");
                break;

                case 'upload_invalid_filetype':
                    die("{'error':'文件类型无效'}");
                break;

                case 'upload_invalid_filesize':
                    die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_size_limit') . " KB'}");
                break;
            }
		}

		if (! $upload_data = AWS_APP::upload()->data())
        {
            die("{'error':'上传失败, 请与管理员联系'}");
        }

		if ($upload_data['is_image'] == 1)
		{
			foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
			{
				$thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($this->user_id, $key, 2);

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();
			}
		}

		$update_data['avatar_file'] = $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['min']);

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);

		echo htmlspecialchars(json_encode(array(
			'success' => true,
			'thumb' => get_setting('upload_url') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['max'])
		)), ENT_NOQUOTES);
	}


	public function privacy_setting_action()
	{
		if ($notify_actions = $this->model('notify')->notify_action_details)
		{
			$notification_setting = array();

			foreach ($notify_actions as $key => $val)
			{
				if (! isset($_POST['notification_settings'][$key]) AND $val['user_setting'])
				{
					$notification_setting[] = intval($key);
				}
			}
		}

		$this->model('account')->update_users_fields(array(
			'inbox_recv' => intval($_POST['inbox_recv'])
		), $this->user_id);

		$this->model('account')->update_notification_setting_fields($notification_setting, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('隐私设置保存成功')));
	}

	public function profile_setting_action()
	{
		if ($_POST['user_name'] AND $_POST['user_name'] != $this->user_info['user_name'])
		{
			if ($user_name = htmlspecialchars(my_trim($_POST['user_name'])))
			{
				if ($this->user_info['user_update_time'] AND $this->user_info['user_update_time'] > (time() - 3600 * 24 * 30))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你距离上次修改用户名未满 30 天')));
				}
				if ($check_result = $this->model('account')->check_username_char($user_name))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', $check_result));
				}
				if ($this->model('account')->check_username_sensitive_words($user_name))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名不符合规则')));
				}
				if ($this->model('account')->check_username($user_name))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的姓名, 请重新填写')));
				}
				$this->model('account')->update_user_name($user_name, $this->user_id);
			}
		}

		$update_data['sex'] = intval($_POST['sex']);

		$update_attrib_data['signature'] = htmlspecialchars($_POST['signature']);

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);

		// 更新从表
		$this->model('account')->update_users_attrib_fields($update_attrib_data, $this->user_id);

		$this->model('account')->set_default_timezone($_POST['default_timezone'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('个人资料保存成功')));
	}

	public function modify_password_action()
	{
		if (!$_POST['old_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入当前密码')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入相同的确认密码')));
		}

		if (strlen($_POST['password']) < 6)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if ($this->model('account')->update_user_password($_POST['old_password'], $_POST['password'], $this->user_id, $this->user_info['salt']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('密码修改成功, 请牢记新密码')));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的当前密码')));
		}
	}

	public function verify_action()
	{
		if (get_setting('id_verification_disabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('认证功能已经关闭')));
		}

		if ($this->is_post() AND !$this->user_info['verified'])
		{
			$this->model('verify')->add_apply($this->user_id, $_POST['name'], $_POST['reason'],$_POST['type']);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function clean_user_recommend_cache_action()
	{
		AWS_APP::cache()->delete('user_recommend_' . $this->user_id);
	}

	public function forbid_user_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$status = intval($_POST['status']);
		if ($status)
		{
			$reason = my_trim($_POST['reason']);
			if (!$reason)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写理由')));
			}
			// TODO: 字数选项
			if (cjk_strlen($reason) > 200)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('理由太长')));
			}
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		$uid = intval($_POST['uid']);
		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($this->user_info['group_id'] != 1 AND $this->user_info['group_id'] != 2)
		{
			if ($user_info['group_id'] != 4 OR intval($this->user_info['reputation']) <= intval($user_info['reputation']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
		}

		if ($status)
		{
			if ($user_info['forbidden'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已经被封禁')));
			}

			$user_group = $this->model('account')->get_user_group_by_user_info($user_info);
			if ($user_group)
			{
				$banning_type = $user_group['permission']['banning_type'];
			}

			if ($banning_type == 'protected')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('操作失败')));
			}
			elseif ($banning_type == 'permanent')
			{
				$status = 3;
			}
			elseif ($banning_type == 'temporary')
			{
				$status = 4;
			}
			else
			{
				$status = 1;
			}
		}
		else
		{
			if (!$user_info['forbidden'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户没有被封禁')));
			}
		}

		$this->model('account')->forbid_user_by_uid($uid, $status, $this->user_id, $reason);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}
