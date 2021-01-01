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

class ajax extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		H::no_cache_header();
	}

	public function login_process_action()
	{
		$user_info = $this->model('login')->verify($this->user_info['uid'], H::POST('scrambled_password'));

		if ($user_info['uid'])
		{
			$this->model('admin')->admin_login();

			H::ajax_location(url_rewrite('/admin/'));
		}
		else
		{
			H::ajax_error((_t('帐号或密码错误')));
		}
	}

	public function save_settings_action()
	{
		$this->model('setting')->set_vars($_POST);

		H::ajax_error((_t('保存设置成功')));
	}

	public function article_manage_action()
	{
		if (!H::POST('article_ids'))
		{
			H::ajax_error((_t('请选择文章进行操作')));
		}

		switch (H::POST('action'))
		{
			case 'del':
				foreach (H::POST('article_ids') AS $article_id)
				{
					$this->model('article')->clear_article($article_id, null);
				}

				H::ajax_success();
			break;
		}
	}

	public function save_category_sort_action()
	{
		if (is_array(H::POST('category')))
		{
			foreach (H::POST('category') as $key => $val)
			{
				$this->model('category')->set_category_sort($key, $val['sort']);
			}
		}

		H::ajax_error((_t('分类排序已自动保存')));
	}

	public function save_category_action()
	{
		if (!$title = H::POST_S('title'))
		{
			H::ajax_error((_t('请输入分类名称')));
		}

		if (H::POST('category_id'))
		{
			$this->model('category')->update_category_info(H::POST('category_id'), $title, H::POST('group_id'), H::POST_S('description'), H::POST('skip'));
		}
		else
		{
			$this->model('category')->add_category($title, H::POST('group_id'), H::POST_S('description'), H::POST('skip'));
		}

		H::ajax_location(url_rewrite('/admin/category/list/'));
	}

	public function remove_category_action()
	{
		$category_id = H::POST_I('category_id');
		if ($category_id == 1)
		{
			H::ajax_error((_t('默认分类不可删除')));
		}

		if ($this->model('category')->contents_exists($category_id))
		{
			H::ajax_error((_t('分类下存在内容, 请先批量移动问题到其它分类, 再删除当前分类')));
		}

		$this->model('category')->delete_category($category_id);

		H::ajax_success();
	}

	public function move_category_contents_action()
	{
		if (!H::POST('from_id') OR !H::POST('target_id'))
		{
			H::ajax_error((_t('请先选择指定分类和目标分类')));
		}

		if (H::POST('target_id') == H::POST('from_id'))
		{
			H::ajax_error((_t('指定分类不能与目标分类相同')));
		}

		$this->model('category')->move_contents(H::POST('from_id'), H::POST('target_id'));

		H::ajax_success();
	}

	public function save_feature_action()
	{
		if (!$title = H::POST_S('title'))
		{
			H::ajax_error((_t('标题不能为空')));
		}

		if (H::GET('feature_id'))
		{
			$feature = $this->model('feature')->get_feature_by_id(H::GET('feature_id'));

			$feature_id = $feature['id'];
		}

		if (!H::GET('feature_id'))
		{
			$feature_id = $this->model('feature')->add_feature($title);
		}

		$update_data = array(
			'title' => $title,
			'link' => H::POST_S('link'),
			'sort' => H::POST_I('sort')
		);

		$this->model('feature')->update_feature($feature_id, $update_data);

		H::ajax_location(url_rewrite('/admin/feature/list/'));
	}

	public function remove_feature_action()
	{
		$this->model('feature')->delete_feature(H::POST('feature_id'));

		H::ajax_success();
	}

	public function save_feature_status_action()
	{
		if (H::POST('feature_ids'))
		{
			foreach (H::POST('feature_ids') AS $feature_id => $val)
			{
				$this->model('feature')->update_feature_enabled($feature_id, H::POST('enabled_status')[$feature_id]);
			}
		}

		H::ajax_error((_t('规则状态已自动保存')));
	}

	public function save_nav_menu_action()
	{
		if (H::POST('nav_sort'))
		{
			if ($menu_ids = explode(',', H::POST('nav_sort')))
			{
				foreach($menu_ids as $key => $val)
				{
					$this->model('menu')->update_nav_menu($val, array(
						'sort' => $key
					));
				}
			}
		}

		if (H::POST('nav_menu'))
		{
			foreach(H::POST('nav_menu') as $key => $val)
			{
				$this->model('menu')->update_nav_menu($key, $val);
			}
		}

		H::ajax_error((_t('导航菜单保存成功')));
	}

	public function add_nav_menu_action()
	{
		switch (H::POST('type'))
		{
			case 'category' :
				$type_id = H::POST_I('type_id');
				$category = $this->model('category')->get_category_info($type_id);
				$title = $category['title'];
			break;

			case 'custom' :
				$title = H::POST_S('title');
				$description = H::POST_S('description');
				$link = H::POST_S('link');
				$type_id = 0;
			break;
		}

		if (!$title)
		{
			H::ajax_error((_t('请输入导航标题')));
		}

		$this->model('menu')->add_nav_menu($title, $description, H::POST('type'), $type_id, $link);

		H::ajax_success();
	}

	public function remove_nav_menu_action()
	{
		$this->model('menu')->remove_nav_menu(H::POST('id'));

		H::ajax_success();
	}

	public function nav_menu_upload_action()
	{
		H::ajax_error((_t('上传失败')));

		// TODO: 以后再说
	}


	public function question_manage_action()
	{
		if (!H::POST('question_ids'))
		{
			H::ajax_error((_t('请选择问题进行操作')));
		}

		switch (H::POST('action'))
		{
			case 'remove':
				foreach (H::POST('question_ids') AS $question_id)
				{
					$this->model('question')->clear_question($question_id, null);
				}

				H::ajax_success();
			break;
		}
	}

	public function lock_topic_action()
	{
		$this->model('topic')->lock_topic_by_ids(H::POST('topic_id'), H::POST('status'));

		H::ajax_success();
	}

	public function save_topic_action()
	{
		$topic_title = H::POST_S('topic_title');
		if (H::POST('topic_id'))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id(H::POST('topic_id')))
			{
				H::ajax_error((_t('话题不存在')));
			}

			if ($topic_info['topic_title'] != htmlspecialchars($topic_title) AND $this->model('topic')->get_topic_by_title($topic_title))
			{
				H::ajax_error((_t('同名话题已经存在')));
			}

			$this->model('topic')->update_topic($this->user_id, $topic_info['topic_id'], $topic_title, H::POST_S('topic_description'));

			$this->model('topic')->lock_topic_by_ids($topic_info['topic_id'], H::POST('topic_lock'));

			$topic_id = $topic_info['topic_id'];
		}
		else
		{
			if ($this->model('topic')->get_topic_by_title($topic_title))
			{
				H::ajax_error((_t('同名话题已经存在')));
			}

			$topic_id = $this->model('topic')->save_topic($topic_title, $this->user_id, true, H::POST_S('topic_description'));
		}

		H::ajax_location(url_rewrite('/admin/topic/list/'));
	}

	public function topic_manage_action()
	{
		if (!H::POST('topic_ids'))
		{
			H::ajax_error((_t('请选择话题进行操作')));
		}

		switch(H::POST('action'))
		{
			case 'remove' :
				$this->model('topic')->remove_topic_by_ids(H::POST('topic_ids'));

				break;

			case 'lock' :
				$this->model('topic')->lock_topic_by_ids(H::POST('topic_ids'), 1);

				break;
		}

		H::ajax_success();
	}

	public function save_user_group_action()
	{
		if ($group_data = H::POST('group'))
		{
			foreach ($group_data as $key => $val)
			{
				if (!$val['group_name'])
				{
					H::ajax_error((_t('请输入用户组名称')));
				}

				$this->model('usergroup')->update_user_group_data($key, $val);
			}
		}

		if ($group_new = H::POST('group_new'))
		{
			foreach ($group_new['group_name'] as $key => $val)
			{
				if (trim($group_new['group_name'][$key]))
				{
					$this->model('usergroup')->add_reputation_group(
						trim($group_new['group_name'][$key]),
						$group_new['reputation_factor'][$key],
						$group_new['reputation_factor_receive'][$key],
						$group_new['content_reputation_factor'][$key],
						$group_new['reputation_lower'][$key],
						$group_new['reputation_higer'][$key]
					);
				}
			}
		}

		if ($group_ids = H::POST('group_ids'))
		{
			foreach ($group_ids as $key => $id)
			{
				$this->model('usergroup')->delete_user_group_by_id($id);
			}
		}

		AWS_APP::cache()->cleanGroup('users_group');

		H::ajax_success();
	}

	public function save_custom_user_group_action()
	{
		if ($group_data = H::POST('group'))
		{
			foreach ($group_data as $key => $val)
			{
				if (!$val['group_name'])
				{
					H::ajax_error((_t('请输入用户组名称')));
				}

				$this->model('usergroup')->update_user_group_data($key, $val);
			}
		}

		if ($group_new = H::POST('group_new'))
		{
			foreach ($group_new['group_name'] as $key => $val)
			{
				if (trim($group_new['group_name'][$key]))
				{
					$this->model('usergroup')->add_custom_group(
						trim($group_new['group_name'][$key]),
						$group_new['reputation_factor'][$key],
						$group_new['reputation_factor_receive'][$key],
						$group_new['content_reputation_factor'][$key]
					);
				}
			}
		}

		if ($group_ids = H::POST('group_ids'))
		{
			foreach ($group_ids as $key => $id)
			{
				$this->model('usergroup')->delete_user_group_by_id($id);
			}
		}

		AWS_APP::cache()->cleanGroup('users_group');

		H::ajax_success();
	}

	public function save_internal_user_group_action()
	{
		if ($group_data = H::POST('group'))
		{
			foreach ($group_data as $key => $val)
			{
				if (!$val['group_name'])
				{
					H::ajax_error((_t('请输入用户组名称')));
				}

				$this->model('usergroup')->update_user_group_data($key, $val);
			}
		}

		if ($group_new = H::POST('group_new'))
		{
			foreach ($group_new['group_name'] as $key => $val)
			{
				if (trim($group_new['group_name'][$key]))
				{
					$this->model('usergroup')->add_system_group(
						trim($group_new['group_name'][$key]),
						$group_new['reputation_factor'][$key],
						$group_new['reputation_factor_receive'][$key],
						$group_new['content_reputation_factor'][$key]
					);
				}
			}
		}

		if ($group_ids = H::POST('group_ids'))
		{
			foreach ($group_ids as $key => $id)
			{
				$this->model('usergroup')->delete_user_group_by_id($id);
			}
		}

		AWS_APP::cache()->cleanGroup('users_group');

		H::ajax_success();
	}


	public function edit_user_group_permission_action()
	{
		$permission_array = array(
			'is_administrator',
			'is_moderator',
			'forbid_user',
			'unforbid_user',
			'flag_user',
			'unflag_user',
			'delete_user',
			'ignore_reputation',
			'change_user_group',
			'edit_user',
			'edit_topic',
			'manage_topic',
			'pin_post',
			'recommend_post',
			'redirect_post',
			'bump_post',
			'sink_post',
			'lock_post',
			'fold_post',
			'fold_post_own_thread',
			'change_category',
			'protected',
			'edit_own_post',
			'edit_any_post',
			'edit_specific_post',
			'post_anonymously',
			'reply_anonymously',
			'flagged_ids',
			'specific_post_uids',
			'anonymous_uid',
			'unallowed_necropost_days',
			'unallowed_post_types',
			'restricted_categories',
			'restricted_categories_reply',
			'restricted_categories_move_to',
			'restricted_categories_move_from',
			'interval_post',
			'interval_modify',
			'interval_vote',
			'interval_follow',
			'interval_manage',
			'thread_limit_per_day',
			'reply_limit_per_day',
			'discussion_limit_per_day',
			'user_vote_limit_per_day',
			'flagging_reputation_lt',
			'flagging_reputation_lt_group_id',
			'flagging_reputation_gt',
			'flagging_reputation_gt_group_id',
			'informal_user',
			'inactive_user',
			'post_later',
			'create_topic',
			'edit_question_topic',
			'vote_agree',
			'vote_disagree',
			'affect_currency',
			'no_dynamic_reputation_factor',
			'no_bonus_reputation_factor',
			'no_upvote_reputation_factor',
			'no_downvote_reputation_factor',
			'no_reputation_upvote',
			'no_reputation_downvote',
			'invite_answer',
			'follow_people',
			'follow_thread',
			'send_pm',
			'dispatch_pm',
			'receive_pm',
			'kb_explore',
			'kb_add',
			'kb_manage',
			'debug',
			'visit_site',
			'visit_people',
		);

		$group_setting = array();

		foreach ($permission_array as $permission)
		{
			if (is_string(H::POST($permission)))
			{
				$group_setting[$permission] = H::POST_S($permission);
			}
		}

		$this->model('usergroup')->update_user_group_data(H::POST('group_id'), array(
			'permission' => serialize($group_setting)
		));

		AWS_APP::cache()->cleanGroup('users_group');

		H::ajax_error((_t('用户组权限保存成功')));
	}

	public function copy_user_group_permission_action()
	{
		$from = H::POST('group_id_from');
		$to = H::POST('group_id_to');

		if (!is_numeric($from) OR !is_numeric($to))
		{
			H::ajax_success(); // 不处理
		}
		$from = intval($from);
		$to = intval($to);
		if ($from < 0 OR $to < 0)
		{
			H::ajax_error((_t('请填写正确的用户组 ID')));
		}
		if ($from == $to)
		{
			H::ajax_success(); // 不处理
		}

		$pms = $this->model('usergroup')->fetch_one('users_group', 'permission', ['group_id', 'eq', $from]);
		if (!is_string($pms))
		{
			H::ajax_error((_t('用户组不存在')));
		}

		$res = $this->model('usergroup')->update('users_group', array(
			'permission' => $pms
		), ['group_id', 'eq', $to]);

		if (!$res)
		{
			H::ajax_error((_t('发生错误')));
		}

		AWS_APP::cache()->cleanGroup('users_group');
		H::ajax_success();
	}

	public function save_user_action()
	{
		if (!$user_info = $this->model('account')->get_user_info_by_uid(H::POST('uid')))
		{
			H::ajax_error((_t('用户不存在')));
		}

		$username = H::POST_S('username');
		if ($username != $user_info['user_name'] AND $this->model('account')->get_user_info_by_username($username))
		{
			H::ajax_error((_t('用户名已存在')));
		}

		if ($_FILES['user_avatar']['name'])
		{
			if (!$this->model('avatar')->upload_avatar('user_avatar', $user_info['uid'], $error))
			{
				H::ajax_error($error);
			}
		}

		if (H::POST('verified'))
		{
			$update_data['verified'] = htmlspecialchars(H::POST_S('verified'));
		}
		else
		{
			$update_data['verified'] = null;
		}

		$update_data['forbidden'] = H::POST_I('forbidden');
		$update_data['flagged'] = H::POST_I('flagged');

		$update_data['group_id'] = H::POST_I('group_id');

		$update_data['sex'] = H::POST_I('sex');
		if ($update_data['sex'] < 0 OR $update_data['sex'] > 3)
		{
			$update_data['sex'] = 0;
		}

		$update_data['reputation'] = H::POST_D('reputation');
		$update_data['agree_count'] = H::POST_I('agree_count');
		$update_data['currency'] = H::POST_I('currency');

		$update_data['signature'] = htmlspecialchars(H::POST_S('signature'));

		$this->model('account')->update_user_fields($update_data, $user_info['uid']);

		if (H::POST('delete_avatar'))
		{
			$this->model('avatar')->delete_avatar($user_info['uid']);
		}

		if (H::POST('confirm_change_password'))
		{
			$new_scrambled_password = H::POST('new_scrambled_password');
			$new_client_salt = H::POST('new_client_salt');

			if (!$this->model('password')->check_base64_string($new_client_salt, 60) OR
				!$this->model('password')->check_structure($new_scrambled_password))
			{
				H::ajax_error((_t('请输入正确的密码')));
			}

			$new_public_key = H::POST('new_public_key');
			$new_private_key = H::POST('new_private_key');

			if (!$this->model('password')->check_base64_string($new_public_key, 1000) OR
				!$this->model('password')->check_base64_string($new_private_key, 1000))
			{
				H::ajax_error((_t('密钥无效')));
			}

			$this->model('password')->update_password($user_info['uid'], $new_scrambled_password, $new_client_salt, $new_public_key, $new_private_key);
		}

		if ($username AND $username != $user_info['user_name'])
		{
			$this->model('account')->update_user_name($username, $user_info['uid']);
		}

		H::ajax_error((_t('用户资料更新成功')));
	}

	public function create_user_action()
	{
		$group_id = H::POST_I('group_id');

		$username = H::POST_S('username');
		if (!$username OR
			!$this->model('password')->check_base64_string(H::POST('client_salt'), 60) OR
			!$this->model('password')->check_structure(H::POST('scrambled_password')))
		{
			H::ajax_error((_t('请输入正确的用户名和密码')));
		}

		$public_key = H::POST('public_key');
		$private_key = H::POST('private_key');

		if (!$this->model('password')->check_base64_string($public_key, 1000) OR
			!$this->model('password')->check_base64_string($private_key, 1000))
		{
			H::ajax_error((_t('密钥无效')));
		}

		if ($this->model('account')->username_exists($username))
		{
			H::ajax_error((_t('用户名已经存在')));
		}

		$uid = $this->model('register')->register($username, H::POST('scrambled_password'), H::POST('client_salt'), $public_key, $private_key);

		if (!$uid)
		{
			H::ajax_error((_t('注册失败')));
		}

		if ($group_id)
		{
			$this->model('account')->update('users', array(
				'group_id' => $group_id,
			), ['uid', 'eq', $uid, 'i']);
		}

		H::ajax_location(url_rewrite('/admin/user/list/'));
	}

	public function currency_process_action()
	{
		if (!H::POST('uid'))
		{
			H::ajax_error((_t('请选择用户进行操作')));
		}

		$note = H::POST_S('note');

		$this->model('currency')->process(H::POST('uid'), 'AWARD', H::POST('currency'), htmlspecialchars($note));

		H::ajax_location(url_rewrite('/admin/user/currency_log/uid-' . H::POST('uid')));
	}

	public function remove_user_action()
	{
		if (!H::POST('uid'))
		{
			H::ajax_error((_t('错误的请求')));
		}

		@set_time_limit(0);

		$user_info = $this->model('account')->get_user_info_by_uid(H::POST('uid'));

		if (!$user_info)
		{
			H::ajax_error((_t('所选用户不存在')));
		}
		else
		{
			$this->model('user')->delete_user_by_uid(H::POST('uid'), H::POST('remove_user_data'));
		}

		H::ajax_location(url_rewrite('/admin/user/list/'));
	}

	public function remove_users_action()
	{
		$uids = H::POST('uids');
		if (!is_array($uids) OR !$uids)
		{
			H::ajax_error((_t('请选择要删除的用户')));
		}

		if (!$remove_user_data = H::POST('remove_user_data'))
		{
			$remove_user_data = [];
		}

		@set_time_limit(0);

		foreach ($uids AS $uid)
		{
			$user_info = $this->model('account')->get_user_info_by_uid($uid);

			if ($user_info)
			{
				$this->model('user')->delete_user_by_uid($uid, $remove_user_data[$uid] ?? null);
			}
			else
			{
				continue;
			}
		}

		H::ajax_success();
	}

	public function topic_statistic_action()
	{
		$topic_statistic = array();

		if ($topic_list = $this->model('topic')->get_hot_topics(null, intval_minmax(H::GET('limit'), 1, 50), H::GET('tag')))
		{
			foreach ($topic_list AS $key => $val)
			{
				$topic_statistic[] = array(
					'title' => $val['topic_title'],
					'week' => $val['discuss_count_last_week'],
					'month' => $val['discuss_count_last_month'],
					'all' => $val['discuss_count']
				);
			}
		}

		echo json_encode($topic_statistic);
	}

	public function statistic_action()
	{
		if (!$start_time = strtotime(H::GET('start_date') . ' 00:00:00'))
		{
			$start_time = strtotime('-12 months');
		}

		if (!$end_time = strtotime(H::GET('end_date') . ' 23:59:59'))
		{
			$end_time = time();
		}

		if (H::GET('tag'))
		{
			$statistic_tag = explode(',', H::GET('tag'));
		}

		if (!$month_list = get_month_list($start_time, $end_time, 'y'))
		{
			die;
		}

		foreach ($month_list AS $key => $val)
		{
			$labels[] = $val['year'] . '-' . $val['month'];
			$data_template[] = 0;
		}

		if (!$statistic_tag)
		{
			die;
		}

		foreach ($statistic_tag AS $key => $val)
		{
			switch ($val)
			{
				case 'new_answer':  // 新增答案
				case 'new_question':	// 新增问题
				case 'new_user':	// 新注册用户
				case 'new_topic':   // 新增话题
				case 'new_answer_vote': // 新增答案投票
					$statistic[] = $this->model('system')->statistic($val, $start_time, $end_time);
				break;
			}
		}

		foreach($statistic AS $key => $val)
		{
			$statistic_data = $data_template;

			foreach ($val AS $k => $v)
			{
				$data_key = array_search($v['date'], $labels);

				$statistic_data[$data_key] = $v['count'];
			}

			$data[] = $statistic_data;

		}

		echo json_encode(array(
			'labels' => $labels,
			'data' => $data
		));
	}

}
