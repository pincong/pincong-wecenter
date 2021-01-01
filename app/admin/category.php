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

class category extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(_t('分类管理'));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(302));
    }

    public function list_action()
    {
        TPL::assign('category_list', $this->model('category')->get_category_list());

		// TODO: category_option 改为 category_group

        TPL::output('admin/category/list');
    }

    public function edit_action()
    {
        if (!$category_info = $this->model('category')->get_category_info(H::GET('category_id')))
        {
            H::redirect_msg(_t('指定分类不存在'), '/admin/category/list/');
        }

        TPL::assign('category', $category_info);
		// TODO: category_option 改为 category_group

        TPL::output('admin/category/edit');
    }
}