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

class main extends AWS_ADMIN_CONTROLLER
{
    public function index_action()
    {
        $this->crumb(AWS_APP::lang()->_t('概述'), 'admin/main/');

        if (!defined('IN_SAE'))
        {
            $writable_check = array(
                'cache' => is_really_writable(ROOT_PATH . 'cache/'),
                'tmp' => is_really_writable(ROOT_PATH . './tmp/'),
                get_setting('upload_dir') => is_really_writable(get_setting('upload_dir'))
            );

            TPL::assign('writable_check', $writable_check);
        }

        TPL::assign('users_count', $this->model('system')->count('users'));
        TPL::assign('question_count', $this->model('system')->count('question'));
        TPL::assign('answer_count', $this->model('system')->count('answer'));
        TPL::assign('question_no_answer_count', $this->model('system')->count('question', 'answer_count = 0'));
        TPL::assign('topic_count', $this->model('system')->count('topic'));

		TPL::assign('global_failed_login_count', $this->model('login')->get_global_failed_login_count());

        $admin_menu = (array)AWS_APP::config()->get('admin_menu');

        $admin_menu[0]['select'] = true;

        TPL::assign('menu_list', $admin_menu);

        TPL::output('admin/index');
    }

    public function login_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }
        else if (AWS_APP::session()->admin_login)
        {
            $admin_info = json_decode(AWS_APP::crypt()->decode(AWS_APP::session()->admin_login), true);

            if ($admin_info['uid'])
            {
                HTTP::redirect('/admin/');
            }
        }

        TPL::import_css('admin/css/login.css');

        TPL::output('admin/login');
    }

    public function logout_action($return_url = '/')
    {
        $this->model('admin')->admin_logout();

        HTTP::redirect($return_url);
    }

    public function settings_action()
    {
        $this->crumb(AWS_APP::lang()->_t('系统设置'), 'admin/settings/');

        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        if (!$_GET['category'])
        {
            $_GET['category'] = 'site';
        }

        switch ($_GET['category'])
        {
            case 'interface':
                TPL::assign('styles', $this->model('setting')->get_ui_styles());
            break;

            case 'register':
                TPL::assign('notification_settings', get_setting('new_user_notification_setting'));
                TPL::assign('notify_actions', $this->model('notify')->notify_action_details);
            break;
        }

        TPL::assign('setting', get_setting(null, false));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('SETTINGS_' . strtoupper($_GET['category'])));

        TPL::output('admin/settings');
    }

    public function nav_menu_action()
    {
        $this->crumb(AWS_APP::lang()->_t('导航设置'), 'admin/nav_menu/');

        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('nav_menu_list', $this->model('menu')->get_nav_menu_list());

        TPL::assign('category_list', $this->model('category')->get_category_list());

        TPL::assign('setting', get_setting());

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(307));

        TPL::output('admin/nav_menu');
    }
}