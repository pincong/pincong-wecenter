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
        $this->crumb(_t('概述'));

        {
            $writable_check = array(
                'cache' => is_really_writable(ROOT_PATH . 'cache/'),
                'tmp' => is_really_writable(ROOT_PATH . './tmp/'),
                S::get('upload_dir') => is_really_writable(S::get('upload_dir'))
            );

            TPL::assign('writable_check', $writable_check);
        }

        TPL::assign('users_count', $this->model('system')->count('users'));
        TPL::assign('question_count', $this->model('system')->count('question'));
        TPL::assign('answer_count', $this->model('system')->count('question_reply'));
        TPL::assign('question_no_answer_count', $this->model('system')->count('question', ['reply_count', 'eq', 0]));
        TPL::assign('topic_count', $this->model('system')->count('topic'));

		TPL::assign('global_failed_login_count', $this->model('login')->get_global_failed_login_count());

        $admin_menu = (array)AWS_APP::config()->get('admin_menu');

        $admin_menu[0]['select'] = true;

        TPL::assign('menu_list', $admin_menu);

        TPL::output('admin/index');
    }

    public function login_action()
    {
        if (AWS_APP::auth()->is_admin())
        {
            H::redirect('/admin/');
        }

        TPL::output('admin/login');
    }

    public function logout_action($return_url = '/')
    {
        $this->model('admin')->admin_logout();

        H::redirect($return_url);
    }

    public function settings_action()
    {
        $this->crumb(_t('系统设置'));

		$category = H::GET('category');
        if (!$category)
        {
            $category = 'site';
        }

        switch ($category)
        {
            case 'interface':
                TPL::assign('styles', $this->model('setting')->get_ui_styles());
            break;
        }

        TPL::assign('setting', S::get_all());

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('SETTINGS_' . strtoupper($category)));

        TPL::output('admin/settings');
    }

    public function nav_menu_action()
    {
        $this->crumb(_t('导航设置'));

        TPL::assign('nav_menu_list', $this->model('menu')->get_nav_menu_list());

        TPL::assign('category_list', $this->model('category')->get_category_list());

        TPL::assign('setting', S::get_all());

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(307));

        TPL::output('admin/nav_menu');
    }
}