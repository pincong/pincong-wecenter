<?php
$config[] = array(
	'title' => AWS_APP::lang()->_t('概述'),
	'cname' => 'home',
	'url' => '/admin/'
);

$config[] = array(
	'title' => AWS_APP::lang()->_t('设置'),
	'cname' => 'setting',
	'children' => array(
		array(
			'id' => 'SETTINGS_SITE',
			'title' => AWS_APP::lang()->_t('站点信息'),
			'url' => '/admin/settings/category-site'
		),

		array(
			'id' => 'SETTINGS_REGISTER',
			'title' => AWS_APP::lang()->_t('注册访问'),
			'url' => '/admin/settings/category-register'
		),

		array(
			'id' => 'SETTINGS_FUNCTIONS',
			'title' => AWS_APP::lang()->_t('站点功能'),
			'url' => '/admin/settings/category-functions'
		),

		array(
			'id' => 'SETTINGS_CONTENTS',
			'title' => AWS_APP::lang()->_t('内容设置'),
			'url' => '/admin/settings/category-contents'
		),

		array(
			'id' => 'SETTINGS_CURRENCY',
			'title' => AWS_APP::lang()->_t('声望代币'),
			'url' => '/admin/settings/category-currency'
		),

		array(
			'id' => 'SETTINGS_PERMISSIONS',
			'title' => AWS_APP::lang()->_t('用户限制'),
			'url' => '/admin/settings/category-permissions'
		),

		array(
			'id' => 'SETTINGS_CACHE',
			'title' => AWS_APP::lang()->_t('性能优化'),
			'url' => '/admin/settings/category-cache'
		),

		array(
			'id' => 'SETTINGS_INTERFACE',
			'title' => AWS_APP::lang()->_t('界面设置'),
			'url' => '/admin/settings/category-interface'
		),

		array(
			'id' => 'SETTINGS_VIDEO',
			'title' => AWS_APP::lang()->_t('影片处理'),
			'url' => '/admin/settings/category-video'
		),
	)
);

$config[] = array(
	'title' => AWS_APP::lang()->_t('管理'),
	'cname' => 'user',
	'children' => array(
		array(
			'id' => 402,
			'title' => AWS_APP::lang()->_t('用户列表'),
			'url' => '/admin/user/list/'
		),

		array(
			'id' => 403,
			'title' => AWS_APP::lang()->_t('用户组'),
			'url' => '/admin/user/group_list/'
		),

		array(
			'id' => 307,
			'title' => AWS_APP::lang()->_t('导航'),
			'url' => '/admin/nav_menu/'
		),

		array(
			'id' => 302,
			'title' => AWS_APP::lang()->_t('分类'),
			'url' => '/admin/category/list/'
		),

		array(
			'id' => 304,
			'title' => AWS_APP::lang()->_t('功能链接'),
			'url' => '/admin/feature/list/'
		),

		array(
			'id' => 303,
			'title' => AWS_APP::lang()->_t('话题列表'),
			'url' => '/admin/topic/list/'
		),

		array(
			'id' => 301,
			'title' => AWS_APP::lang()->_t('问题列表'),
			'url' => '/admin/question/question_list/'
		),

		array(
			'id' => 309,
			'title' => AWS_APP::lang()->_t('文章列表'),
			'url' => '/admin/article/list/'
		),
	)
);

$config[] = array(
	'title' => AWS_APP::lang()->_t('内容'),
	'cname' => 'signup',
	'children' => array(

		array(
			'id' => 'content_custom_css',
			'title' => AWS_APP::lang()->_t('自定义 CSS'),
			'url' => '/admin/content/custom_css/'
		),

		array(
			'id' => 'content_custom_head',
			'title' => AWS_APP::lang()->_t('自定义 head'),
			'url' => '/admin/content/custom_head/'
		),

		array(
			'id' => 'content_custom_body_top',
			'title' => AWS_APP::lang()->_t('自定义 body 顶端'),
			'url' => '/admin/content/custom_body_top/'
		),

		array(
			'id' => 'content_custom_body_bottom',
			'title' => AWS_APP::lang()->_t('自定义 body 底端'),
			'url' => '/admin/content/custom_body_bottom/'
		),

		array(
			'id' => 'content_statistic_code',
			'title' => AWS_APP::lang()->_t('网站统计代码'),
			'url' => '/admin/content/statistic_code/'
		),

		array(
			'id' => 'content_content_replacing_list',
			'title' => AWS_APP::lang()->_t('用户内容替换列表'),
			'url' => '/admin/content/content_replacing_list/'
		),

		array(
			'id' => 'content_html_replacing_list',
			'title' => AWS_APP::lang()->_t('网页内容替换列表'),
			'url' => '/admin/content/html_replacing_list/'
		),

		array(
			'id' => 'content_sensitive_words',
			'title' => AWS_APP::lang()->_t('敏感词列表'),
			'url' => '/admin/content/sensitive_words/'
		),
	)
);

$config[] = array(
	'title' => AWS_APP::lang()->_t('广告'),
	'cname' => 'reply',
	'children' => array(
		array(
			'id' => 'ad_space_a1',
			'title' => AWS_APP::lang()->_t('广告位 A1'),
			'url' => '/admin/ad/ad_space_a1/'
		),

		array(
			'id' => 'ad_space_a2',
			'title' => AWS_APP::lang()->_t('广告位 A2'),
			'url' => '/admin/ad/ad_space_a2/'
		),

		array(
			'id' => 'ad_space_a3',
			'title' => AWS_APP::lang()->_t('广告位 A3'),
			'url' => '/admin/ad/ad_space_a3/'
		),

		array(
			'id' => 'ad_space_b1',
			'title' => AWS_APP::lang()->_t('广告位 B1'),
			'url' => '/admin/ad/ad_space_b1/'
		),

		array(
			'id' => 'ad_space_b2',
			'title' => AWS_APP::lang()->_t('广告位 B2'),
			'url' => '/admin/ad/ad_space_b2/'
		),

		array(
			'id' => 'ad_space_b3',
			'title' => AWS_APP::lang()->_t('广告位 B3'),
			'url' => '/admin/ad/ad_space_b3/'
		),
	)
);

$config[] = array(
	'title' => AWS_APP::lang()->_t('工具'),
	'cname' => 'job',
	'children' => array(
		array(
			'id' => 501,
			'title' => AWS_APP::lang()->_t('系统维护'),
			'url' => '/admin/tools/',
		),
	)
);

if (file_exists(AWS_PATH . 'config/admin_menu.extension.php'))
{
	include_once(AWS_PATH . 'config/admin_menu.extension.php');
}
