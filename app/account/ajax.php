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
			'register_agreement',
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
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
		}

		if (get_setting('register_type') == 'close')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
		}
		else if (get_setting('register_type') == 'invite' AND !$_POST['icode'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
		}

		if (trim($_POST['user_name']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
		}

		if (strlen($_POST['password']) < 6)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
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
		if ($this->model('account')->check_username_sensitive_words($_POST['user_name']) OR trim($_POST['user_name']) != $_POST['user_name'])
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

			$this->model('integral')->process($follow_users['uid'], 'INVITE', get_setting('integral_system_config_invite'), '邀请注册: ' . $_POST['user_name'], $follow_users['uid']);
		}

		if (get_setting('register_valid_type') == 'N')
		{
			$this->model('active')->active_user_by_uid($uid);
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
		{
			$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
		}

		if (! $user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}
		else
		{
			if ($user_info['forbidden'] == 1)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录')));
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

	public function register_agreement_action()
	{
		H::ajax_json_output(AWS_APP::RSM(null, 1, nl2br(get_setting('register_agreement'))));
	}

	public function welcome_message_template_action()
	{

		TPL::output('account/ajax/welcome_message_template');
	}

	public function welcome_get_topics_action()
	{
		if ($topics_list = $this->model('topic')->get_topic_list(null, 'RAND()', 8))
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
			$users_list = $this->model('account')->get_activity_random_users(6);
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
		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1',  AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		$this->model('active')->new_find_password($user_info['uid']);

		{
			$url = get_js_url('/account/find_password/process_success/');
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => $url
		), 1, null));
	}

	public function find_password_modify_action()
	{
		if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('链接已失效，请重新找回密码')));
		}

		if (!$_POST['password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('请输入密码')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('两次输入的密码不一致')));
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		$this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $uid, $user_info['salt']);

		if ($user_info['group_id'] == 3)
		{
			$this->model('active')->active_user_by_uid($user_info['uid']);
		}

		$this->model('account')->setcookie_logout();

		$this->model('account')->setsession_logout();

		unset(AWS_APP::session()->find_password);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/account/login/'),
		), 1, AWS_APP::lang()->_t('密码修改成功, 请返回登录')));
	}

	public function avatar_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, '', 1),
			'is_image' => TRUE,
			'max_size' => get_setting('upload_avatar_size_limit'),
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
                    die("{'error':'文件尺寸过大, 最大允许尺寸为 " . get_setting('upload_avatar_size_limit') .  " KB'}");
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

		if (!$this->model('integral')->fetch_log($this->user_id, 'UPLOAD_AVATAR'))
		{
			$this->model('integral')->process($this->user_id, 'UPLOAD_AVATAR', round((get_setting('integral_system_config_profile') * 0.2)), '上传头像');
		}

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
			if ($user_name = htmlspecialchars(trim($_POST['user_name'])))
			{
				if ($this->user_info['user_name_update_time'] AND $this->user_info['user_name_update_time'] > (time() - 3600 * 24 * 30))
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

		if ($_POST['signature'] AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_SIGNATURE'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_SIGNATURE', round((get_setting('integral_system_config_profile') * 0.1)), AWS_APP::lang()->_t('完善一句话介绍'));
		}

		if ($_POST['signature']  AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_CONTACT'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_CONTACT', round((get_setting('integral_system_config_profile') * 0.1)), AWS_APP::lang()->_t('完善联系资料'));
		}

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

	public function integral_log_action()
	{
		if ($log = $this->model('integral')->fetch_all('integral_log', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 10) . ', 10'))
		{
			foreach ($log AS $key => $val)
			{
				$parse_items[$val['id']] = array(
					'item_id' => $val['item_id'],
					'action' => $val['action']
				);
			}

			TPL::assign('log', $log);
			TPL::assign('log_detail', $this->model('integral')->parse_log_item($parse_items));
		}

		TPL::output('account/ajax/integral_log');
	}

	public function verify_action()
	{
		if ($this->is_post() AND !$this->user_info['verified'])
		{
			$this->model('verify')->add_apply($this->user_id, $_POST['name'], $_POST['reason'],$_POST['type']);

			$recipient_uid = get_setting('report_message_uid') ? get_setting('report_message_uid') : 1;

			//$this->model('message')->send_message($this->user_id, $recipient_uid, AWS_APP::lang()->_t('有新的认证请求, 请登录后台查看处理: %s', get_js_url('/admin/user/verify_approval_list/')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function clean_user_recommend_cache_action()
	{
		AWS_APP::cache()->delete('user_recommend_' . $this->user_id);
	}

}
