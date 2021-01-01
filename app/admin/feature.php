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
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(304));
    }

    public function list_action()
    {
        $this->crumb(_t('功能链接'));

        $feature_list = $this->model('feature')->get_feature_list(H::GET('page'), $this->per_page);

        TPL::assign('pagination', AWS_APP::pagination()->create(array(
            'base_url' => url_rewrite('/admin/feature/list/'),
            'total_rows' => $this->model('feature')->total_rows(),
            'per_page' => 20
        )));

        TPL::assign('list', $feature_list);

        TPL::output('admin/feature/list');
    }

    public function add_action()
    {
        $this->crumb(_t('添加'));

        TPL::output("admin/feature/edit");
    }

    public function edit_action()
    {
        $this->crumb(_t('编辑'));

        TPL::assign('feature', $this->model('feature')->get_feature_by_id(H::GET('feature_id')));

        TPL::output('admin/feature/edit');
    }
}