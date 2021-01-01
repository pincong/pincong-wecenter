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
        HTTP::no_cache_header();

        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
    }

    public function login_process_action()
    {
        if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        {
            $user_info = $this->model('login')->check_login($this->user_info['user_name'], $_POST['password']);
        }

        if ($user_info['uid'])
        {
            $this->model('admin')->set_admin_login($user_info['uid']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $_POST['url'] ? base64_decode($_POST['url']) : get_js_url('/admin/')
            ), 1, null));
        }
        else
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('帐号或密码错误')));
        }
    }

    public function save_settings_action()
    {
        if ($_POST['upload_dir'])
        {
            $_POST['upload_dir'] = rtrim(trim($_POST['upload_dir']), '\/');
        }

        if ($_POST['upload_url'])
        {
            $_POST['upload_url'] = rtrim(trim($_POST['upload_url']), '\/');
        }

        if ($_POST['img_url'])
        {
            $_POST['img_url'] = rtrim(trim($_POST['img_url']), '\/');
        }

        if ($_POST['_set_notification_settings'])
        {
            if ($notify_actions = $this->model('notify')->notify_action_details)
            {
                $notification_setting = array();

                foreach ($notify_actions as $key => $val)
                {
                    if (! isset($_POST['new_user_notification_setting'][$key]) AND $val['user_setting'])
                    {
                        $notification_setting[] = intval($key);
                    }
                }
            }

            $_POST['new_user_notification_setting'] = $notification_setting;
        }

        $this->model('setting')->set_vars($_POST);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存设置成功')));
    }

    public function article_manage_action()
    {
        if (!$_POST['article_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章进行操作')));
        }

        switch ($_POST['action'])
        {
            case 'del':
                foreach ($_POST['article_ids'] AS $article_id)
                {
                    $this->model('article')->clear_article($article_id, null);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function save_category_sort_action()
    {
        if (is_array($_POST['category']))
        {
            foreach ($_POST['category'] as $key => $val)
            {
                $this->model('category')->set_category_sort($key, $val['sort']);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类排序已自动保存')));
    }

    public function save_category_action()
    {
        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入分类名称')));
        }

        if ($_POST['category_id'])
        {
            $this->model('category')->update_category_info($_POST['category_id'], $_POST['title'], $_POST['group_id'], $_POST['description'], $_POST['skip']);
        }
        else
        {
            $this->model('category')->add_category($_POST['title'], $_POST['group_id'], $_POST['description'], $_POST['skip']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/category/list/')
        ), 1, null));
    }

    public function remove_category_action()
    {
        if (intval($_POST['category_id']) == 1)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('默认分类不可删除')));
        }

        if ($this->model('category')->contents_exists($_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类下存在内容, 请先批量移动问题到其它分类, 再删除当前分类')));
        }

        $this->model('category')->delete_category($_POST['category_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function move_category_contents_action()
    {
        if (!$_POST['from_id'] OR !$_POST['target_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先选择指定分类和目标分类')));
        }

        if ($_POST['target_id'] == $_POST['from_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定分类不能与目标分类相同')));
        }

        $this->model('category')->move_contents($_POST['from_id'], $_POST['target_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_feature_action()
    {
        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('标题不能为空')));
        }

        if ($_GET['feature_id'])
        {
            $feature = $this->model('feature')->get_feature_by_id($_GET['feature_id']);

            $feature_id = $feature['id'];
        }

        if (!$_GET['feature_id'])
        {
            $feature_id = $this->model('feature')->add_feature($_POST['title']);
        }

        $update_data = array(
            'title' => $_POST['title'],
            'link' => $_POST['link'],
            'sort' => intval($_POST['sort'])
        );

        $this->model('feature')->update_feature($feature_id, $update_data);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/feature/list/')
		), 1, null));
    }

    public function remove_feature_action()
    {
        $this->model('feature')->delete_feature($_POST['feature_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_feature_status_action()
    {
        if ($_POST['feature_ids'])
        {
            foreach ($_POST['feature_ids'] AS $feature_id => $val)
            {
                $this->model('feature')->update_feature_enabled($feature_id, $_POST['enabled_status'][$feature_id]);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('规则状态已自动保存')));
    }

    public function save_nav_menu_action()
    {
        if ($_POST['nav_sort'])
        {
            if ($menu_ids = explode(',', $_POST['nav_sort']))
            {
                foreach($menu_ids as $key => $val)
                {
                    $this->model('menu')->update_nav_menu($val, array(
                        'sort' => $key
                    ));
                }
            }
        }

        if ($_POST['nav_menu'])
        {
            foreach($_POST['nav_menu'] as $key => $val)
            {
                $this->model('menu')->update_nav_menu($key, $val);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航菜单保存成功')));
    }

    public function add_nav_menu_action()
    {
        switch ($_POST['type'])
        {
            case 'category' :
                $type_id = intval($_POST['type_id']);
                $category = $this->model('category')->get_category_info($type_id);
                $title = $category['title'];
            break;

            case 'custom' :
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $link = trim($_POST['link']);
                $type_id = 0;
            break;
        }

        if (!$title)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入导航标题')));
        }

        $this->model('menu')->add_nav_menu($title, $description, $_POST['type'], $type_id, $link);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function remove_nav_menu_action()
    {
        $this->model('menu')->remove_nav_menu($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function nav_menu_upload_action()
    {
        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败')));
/*
        // TODO: 以后再说
        AWS_APP::upload()->initialize(array(
            'allowed_types' => get_setting('allowed_upload_types'),
            'upload_path' => get_setting('upload_dir') . '/nav_menu',
            'is_image' => TRUE,
            'file_name' => intval($_GET['id']) . '.jpg',
            'encrypt_name' => FALSE
        ))->do_upload('aws_upload_file');

        if (AWS_APP::upload()->get_error())
        {
            switch (AWS_APP::upload()->get_error())
            {
                default:
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
                break;

                case 'upload_invalid_filetype':
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));
                break;
            }
        }

        if (! $upload_data = AWS_APP::upload()->data())
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
        }

        if ($upload_data['is_image'] == 1)
        {
            AWS_APP::image()->initialize(array(
                'quality' => 90,
                'source_image' => $upload_data['full_path'],
                'new_image' => $upload_data['full_path'],
                'width' => 50,
                'height' => 50
            ))->resize();
        }

        $this->model('menu')->update_nav_menu($_GET['id'], array('icon' => basename($upload_data['full_path'])));

        echo htmlspecialchars(json_encode(array(
            'success' => true,
            'thumb' => get_setting('upload_url') . '/nav_menu/' . basename($upload_data['full_path'])
        )), ENT_NOQUOTES);
*/
    }


    public function question_manage_action()
    {
        if (!$_POST['question_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题进行操作')));
        }

        switch ($_POST['action'])
        {
            case 'remove':
                foreach ($_POST['question_ids'] AS $question_id)
                {
                    $this->model('question')->clear_question($question_id, null);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function lock_topic_action()
    {
        $this->model('topic')->lock_topic_by_ids($_POST['topic_id'], $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_topic_action()
    {
        if ($_POST['topic_id'])
        {
            if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题不存在')));
            }

            if ($topic_info['topic_title'] != $_POST['topic_title'] AND $this->model('topic')->get_topic_by_title($_POST['topic_title']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('同名话题已经存在')));
            }

            $this->model('topic')->update_topic($this->user_id, $topic_info['topic_id'], $_POST['topic_title'], $_POST['topic_description']);

            $this->model('topic')->lock_topic_by_ids($topic_info['topic_id'], $_POST['topic_lock']);

            $topic_id = $topic_info['topic_id'];
        }
        else
        {
            if ($this->model('topic')->get_topic_by_title($_POST['topic_title']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('同名话题已经存在')));
            }

            $topic_id = $this->model('topic')->save_topic($_POST['topic_title'], $this->user_id, true, $_POST['topic_description']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/topic/list/')
        ), 1, null));
    }

    public function topic_manage_action()
    {
        if (!$_POST['topic_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择话题进行操作')));
        }

        switch($_POST['action'])
        {
            case 'remove' :
                $this->model('topic')->remove_topic_by_ids($_POST['topic_ids']);

                break;

            case 'lock' :
                $this->model('topic')->lock_topic_by_ids($_POST['topic_ids'], 1);

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_user_group_action()
    {
        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (!$val['group_name'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户组名称')));
                }

                $val['reputation_factor'] = floatval($val['reputation_factor']);
                /*f ($val['reputation_factor'] < 0)
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('声望系数必须为大于或等于 0')));
                }*/

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        if ($group_new = $_POST['group_new'])
        {
            foreach ($group_new['group_name'] as $key => $val)
            {
                if (trim($group_new['group_name'][$key]))
                {
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 'member', $group_new['reputation_lower'][$key], $group_new['reputation_higer'][$key], $group_new['reputation_factor'][$key]);
                }
            }
        }

        if ($group_ids = $_POST['group_ids'])
        {
            foreach ($group_ids as $key => $id)
            {
                $group_info = $this->model('account')->get_user_group_by_id($id);

                if ($group_info['custom'] == 1 OR $group_info['type'] == 1)
                {
                    $this->model('account')->delete_user_group_by_id($id);
                }
                else
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统用户组不可删除')));
                }
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_custom_user_group_action()
    {
        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (!$val['group_name'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户组名称')));
                }

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        if ($group_new = $_POST['group_new'])
        {
            foreach ($group_new['group_name'] as $key => $val)
            {
                if (trim($group_new['group_name'][$key]))
                {
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 'custom', 0, 0, $group_new['reputation_factor'][$key]);
                }
            }
        }

        if ($group_ids = $_POST['group_ids'])
        {
            foreach ($group_ids as $key => $id)
            {
                $group_info = $this->model('account')->get_user_group_by_id($id);

                if ($group_info['custom'] == 1)
                {
                    $this->model('account')->delete_user_group_by_id($id);
                }
                else
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统用户组不可删除')));
                }
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        if ($group_new OR $group_ids)
        {
            $rsm = array(
                'url' => get_js_url('/admin/user/group_list/r-' . rand(1, 999) . '#custom')
            );
        }

        H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
    }


    public function save_internal_user_group_action()
    {
        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (!$val['group_name'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户组名称')));
                }

                $this->model('account')->update_user_group_data($key, $val);
            }
        }

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }


    public function edit_user_group_permission_action()
    {
        $permission_array = array(
			'is_administrator',
			'is_moderator',
			'edit_any_post',
			'edit_specific_post',
			'forbid_user',
			'flag_user',
			'change_user_group',
			'edit_user',
			'edit_topic',
			'manage_topic',
			'pin_post',
			'fold_post',
			'recommend_post',
			'lock_post',
			'bump_sink',
			'change_category',
			'banning_type',
			'restricted_categories',
			'interval_post',
			'interval_modify',
			'interval_vote',
			'interval_manage',
			'thread_limit_per_day',
			'reply_limit_per_day',
			'discussion_limit_per_day',
			'user_vote_limit_per_day',
			'publish_question',
			'answer_question',
			'publish_article',
			'comment_article',
			'publish_video',
			'comment_video',
			'publish_discussion',
			'post_anonymously',
			'reply_anonymously',
			'post_later',
			'create_topic',
			'edit_question_topic',
			'vote_agree',
			'vote_disagree',
			'affect_currency',
			'invite_answer',
			'follow_people',
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
            if ($_POST[$permission])
            {
                $group_setting[$permission] = $_POST[$permission];
            }
        }

        $this->model('account')->update_user_group_data($_POST['group_id'], array(
            'permission' => serialize($group_setting)
        ));

        AWS_APP::cache()->cleanGroup('users_group');

        H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('用户组权限已更新')));
    }

    public function save_user_action()
    {
        if ($_POST['uid'])
        {
            if (!$user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));
            }

            if ($_POST['user_name'] != $user_info['user_name'] AND $this->model('account')->get_user_info_by_username($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已存在')));
            }

            if ($_FILES['user_avatar']['name'])
            {
                if (!$this->model('avatar')->upload_avatar('user_avatar', $user_info['uid'], $error))
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, $error));
                }
            }

            if ($_POST['verified'])
            {
                $update_data['verified'] = htmlspecialchars($_POST['verified']);
            }
            else
            {
                $update_data['verified'] = null;
            }

            $update_data['forbidden'] = intval($_POST['forbidden']);
            $update_data['flagged'] = intval($_POST['flagged']);

            $update_data['group_id'] = intval($_POST['group_id']);

            $update_data['sex'] = intval($_POST['sex']);

            $update_data['reputation'] = floatval($_POST['reputation']);
            $update_data['agree_count'] = intval($_POST['agree_count']);
            $update_data['currency'] = intval($_POST['currency']);

            $update_data['signature'] = htmlspecialchars($_POST['signature']);

            $this->model('account')->update_user_fields($update_data, $user_info['uid']);

            if ($_POST['delete_avatar'])
            {
                $this->model('avatar')->delete_avatar($user_info['uid']);
            }

            if ($_POST['password'])
            {
                $this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $user_info['uid']);
            }

            if ($_POST['user_name'] AND $_POST['user_name'] != $user_info['user_name'])
            {
                $this->model('account')->update_user_name($_POST['user_name'], $user_info['uid']);
            }

            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户资料更新成功')));
        }
        else
        {
            $_POST['user_name'] = trim($_POST['user_name']);

            $_POST['password'] = trim($_POST['password']);

            $_POST['group_id'] = intval($_POST['group_id']);

            if (!$_POST['user_name'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
            }

            if ($this->model('account')->username_exists($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
            }

            if (strlen($_POST['password']) < 6)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
            }

            $uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password']);

            if (!$uid)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('注册失败')));
            }

            if ($_POST['group_id'] != 4)
            {
                $this->model('account')->update('users', array(
                    'group_id' => $_POST['group_id'],
                ), 'uid = ' . $uid);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/list/')
            ), 1, null));
        }
    }

    public function forbid_user_action()
    {
        $this->model('user')->forbid_user_by_uid($_POST['uid'], $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function flag_user_action()
    {
        $this->model('user')->flag_user_by_uid($_POST['uid'], $_POST['status']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function currency_process_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择用户进行操作')));
        }

        if (!$_POST['note'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写理由')));
        }

        $this->model('currency')->process($_POST['uid'], 'AWARD', $_POST['currency'], $_POST['note']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/currency_log/uid-' . $_POST['uid'])
        ), 1, null));
    }

    public function remove_user_action()
    {
        if (!$_POST['uid'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        @set_time_limit(0);

        $user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']);

        if (!$user_info)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('所选用户不存在')));
        }
        else
        {
            if ($user_info['group_id'] == 1)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不允许删除管理员用户组用户')));
            }

            $this->model('user')->delete_user_by_uid($_POST['uid']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/list/')
        ), 1, null));
    }

    public function remove_users_action()
    {
        if (!is_array($_POST['uids']) OR !$_POST['uids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的用户')));
        }

        @set_time_limit(0);

        foreach ($_POST['uids'] AS $uid)
        {
            $user_info = $this->model('account')->get_user_info_by_uid($uid);

            if ($user_info)
            {
                if ($user_info['group_id'] == 1)
                {
                    continue;
                }

                $this->model('user')->delete_user_by_uid($uid);
            }
            else
            {
                continue;
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function topic_statistic_action()
    {
    	$topic_statistic = array();

    	if ($topic_list = $this->model('topic')->get_hot_topics(null, $_GET['limit'], $_GET['tag']))
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
        if (!$start_time = strtotime($_GET['start_date'] . ' 00:00:00'))
        {
            $start_time = strtotime('-12 months');
        }

        if (!$end_time = strtotime($_GET['end_date'] . ' 23:59:59'))
        {
            $end_time = time();
        }

        if ($_GET['tag'])
        {
            $statistic_tag = explode(',', $_GET['tag']);
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
                case 'new_question':    // 新增问题
                case 'new_user':    // 新注册用户
                case 'new_topic':   // 新增话题
                case 'new_answer_vote': // 新增答案投票
                case 'new_favorite_item': // 新增收藏条目
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
