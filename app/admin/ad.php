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

class ad extends AWS_ADMIN_CONTROLLER
{
    public function index_action()
    {
        $this->crumb(AWS_APP::lang()->_t('广告位'));

        TPL::assign('setting', get_settings());

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list('SETTINGS_AD'));

        TPL::output('admin/ad');
    }
}