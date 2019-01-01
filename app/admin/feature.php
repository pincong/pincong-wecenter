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

class feature extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(304));
    }

    public function list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('功能链接'), 'admin/feature/list/');

        $feature_list = $this->model('feature')->get_feature_list($_GET['page'], $this->per_page);

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/feature/list/'),
            'total_rows' => $this->model('feature')->found_rows(),
            'per_page' => 20
        ))->create_links());

        TPL::assign('list', $feature_list);

        TPL::output('admin/feature/list');
    }

    public function add_action()
    {
        $this->crumb(AWS_APP::lang()->_t('添加'), 'admin/feature/add/');

        TPL::output("admin/feature/edit");
    }

    public function edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('编辑'), "admin/feature/list/");

        TPL::assign('feature', $this->model('feature')->get_feature_by_id($_GET['feature_id']));

        TPL::output('admin/feature/edit');
    }
}