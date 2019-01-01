<?php
$config[] = array(
    'title' => AWS_APP::lang()->_t('概述'),
    'cname' => 'home',
    'url' => 'admin/',
    'children' => array()
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('全局设置'),
    'cname' => 'setting',
    'children' => array(
        array(
            'id' => 'SETTINGS_SITE',
            'title' => AWS_APP::lang()->_t('站点信息'),
            'url' => 'admin/settings/category-site'
        ),

        array(
            'id' => 'SETTINGS_REGISTER',
            'title' => AWS_APP::lang()->_t('注册访问'),
            'url' => 'admin/settings/category-register'
        ),

        array(
            'id' => 'SETTINGS_FUNCTIONS',
            'title' => AWS_APP::lang()->_t('站点功能'),
            'url' => 'admin/settings/category-functions'
        ),

        array(
            'id' => 'SETTINGS_CONTENTS',
            'title' => AWS_APP::lang()->_t('内容设置'),
            'url' => 'admin/settings/category-contents'
        ),

        array(
            'id' => 'SETTINGS_CURRENCY',
            'title' => AWS_APP::lang()->_t('威望积分'),
            'url' => 'admin/settings/category-currency'
        ),

        array(
            'id' => 'SETTINGS_PERMISSIONS',
            'title' => AWS_APP::lang()->_t('用户限制'),
            'url' => 'admin/settings/category-permissions'
        ),

        array(
            'id' => 'SETTINGS_CACHE',
            'title' => AWS_APP::lang()->_t('性能优化'),
            'url' => 'admin/settings/category-cache'
        ),

        array(
            'id' => 'SETTINGS_INTERFACE',
            'title' => AWS_APP::lang()->_t('界面设置'),
            'url' => 'admin/settings/category-interface'
        ),

        array(
            'id' => 'SETTINGS_VIDEO',
            'title' => AWS_APP::lang()->_t('视频处理'),
            'url' => 'admin/settings/category-video'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('内容管理'),
    'cname' => 'reply',
    'children' => array(
        array(
            'id' => 301,
            'title' => AWS_APP::lang()->_t('问题管理'),
            'url' => 'admin/question/question_list/'
        ),

        array(
            'id' => 309,
            'title' => AWS_APP::lang()->_t('文章管理'),
            'url' => 'admin/article/list/'
        ),

        array(
            'id' => 303,
            'title' => AWS_APP::lang()->_t('话题管理'),
            'url' => 'admin/topic/list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('用户管理'),
    'cname' => 'user',
    'children' => array(
        array(
            'id' => 402,
            'title' => AWS_APP::lang()->_t('用户列表'),
            'url' => 'admin/user/list/'
        ),

        array(
            'id' => 403,
            'title' => AWS_APP::lang()->_t('用户组'),
            'url' => 'admin/user/group_list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('审核管理'),
    'cname' => 'report',
    'children' => array(
        array(
            'id' => 401,
            'title' => AWS_APP::lang()->_t('认证审核'),
            'url' => 'admin/user/verify_approval_list/'
        ),

        array(
            'id' => 408,
            'title' => AWS_APP::lang()->_t('注册审核'),
            'url' => 'admin/user/register_approval_list/'
        )
    )
);


$config[] = array(
    'title' => AWS_APP::lang()->_t('内容设置'),
    'cname' => 'signup',
    'children' => array(
        array(
            'id' => 307,
            'title' => AWS_APP::lang()->_t('导航设置'),
            'url' => 'admin/nav_menu/'
        ),

        array(
            'id' => 302,
            'title' => AWS_APP::lang()->_t('分类管理'),
            'url' => 'admin/category/list/'
        ),

        array(
            'id' => 304,
            'title' => AWS_APP::lang()->_t('专题管理'),
            'url' => 'admin/feature/list/'
        ),

        array(
            'id' => 308,
            'title' => AWS_APP::lang()->_t('页面管理'),
            'url' => 'admin/page/'
        ),

        array(
            'id' => 305,
            'title' => AWS_APP::lang()->_t('帮助中心'),
            'url' => 'admin/help/list/'
        )
    )
);

$config[] = array(
    'title' => AWS_APP::lang()->_t('工具'),
    'cname' => 'job',
    'children' => array(
        array(
            'id' => 501,
            'title' => AWS_APP::lang()->_t('系统维护'),
            'url' => 'admin/tools/',
        )
    )
);

if (file_exists(AWS_PATH . 'config/admin_menu.extension.php'))
{
    include_once(AWS_PATH . 'config/admin_menu.extension.php');
}
