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
    }

    public function login_process_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (get_setting('admin_login_seccode') == 'Y' AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        {
            $user_info = $this->model('account')->check_login($this->user_info['user_name'], $_POST['password']);
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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

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

        if ($_POST['request_route_custom'])
        {
            $_POST['request_route_custom'] = trim($_POST['request_route_custom']);

            if ($request_routes = explode("\n", $_POST['request_route_custom']))
            {
                foreach ($request_routes as $key => $val)
                {
                    if (! strstr($val, '==='))
                    {
                        continue;
                    }

                    list($m, $n) = explode('===', $val);

                    if (substr($n, 0, 1) != '/' OR substr($m, 0, 1) != '/')
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则 URL 必须以 / 开头')));
                    }

                    if (strstr($m, '/admin') OR strstr($n, '/admin'))
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('URL 自定义路由规则不允许设置 /admin 路由')));
                    }
                }
            }
        }

        if ($_POST['censoruser'])
        {
            $_POST['censoruser'] = trim($_POST['censoruser']);
        }

        if ($_POST['sensitive_words'])
        {
            $_POST['sensitive_words'] = trim($_POST['sensitive_words']);
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
                    $this->model('article')->remove_article($article_id);
                }

                H::ajax_json_output(AWS_APP::RSM(null, 1, null));
            break;
        }
    }

    public function save_category_sort_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['category_id'] AND $_POST['parent_id'] AND $category_list = $this->model('system')->fetch_category('question', $_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('系统允许最多二级分类, 当前分类下有子分类, 不能移动到其它分类')));
        }

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入分类名称')));
        }

        if ($_POST['url_token'])
        {
            if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类别名只允许输入英文或数字')));
            }

            if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_POST['category_id'] != $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类别名不可以全为数字')));
            }

            if ($this->model('category')->check_url_token($_POST['url_token'], $_POST['category_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类别名已经被占用请更换一个')));
            }
        }

        if ($_POST['category_id'])
        {
            $category_id = intval($_POST['category_id']);
        }
        else
        {
            $category_id = $this->model('category')->add_category('question', $_POST['title'], $_POST['parent_id']);
        }

        $category = $this->model('system')->get_category_info($category_id);

        if ($category['id'] == $_POST['parent_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能设置当前分类为父级分类')));
        }

        $this->model('category')->update_category_info($category_id, $_POST['title'], $_POST['parent_id'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/category/list/')
        ), 1, null));
    }

    public function remove_category_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (intval($_POST['category_id']) == 1)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('默认分类不可删除')));
        }

        if ($this->model('category')->contents_exists($_POST['category_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类下存在内容, 请先批量移动问题到其它分类, 再删除当前分类')));
        }

        $this->model('category')->delete_category('question', $_POST['category_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function move_category_contents_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (trim($_POST['title']) == '')
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题标题不能为空')));
        }

        if ($_GET['feature_id'])
        {
            $feature = $this->model('feature')->get_feature_by_id($_GET['feature_id']);

            $feature_id = $feature['id'];
        }

        if ($_POST['url_token'])
        {
            if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题别名只允许输入英文或数字')));
            }

            if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($_GET['feature_id'] != $_POST['url_token']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题别名不可以全为数字')));
            }

            if ($this->model('feature')->check_url_token($_POST['url_token'], $_GET['feature_id']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('专题别名已经被占用请更换一个')));
            }
        }

        if (!$_GET['feature_id'])
        {
            $feature_id = $this->model('feature')->add_feature($_POST['title']);
        }

        if ($_POST['topics'])
        {
            if ($topics = explode("\n", $_POST['topics']))
            {
                $this->model('feature')->empty_topics($feature_id);
            }

            foreach ($topics AS $key => $topic_title)
            {
                if ($topic_info = $this->model('topic')->get_topic_by_title(trim($topic_title)))
                {
                    $this->model('feature')->add_topic($feature_id, $topic_info['topic_id']);
                }
            }
        }

        $update_data = array(
            'title' => $_POST['title'],
            'description' => htmlspecialchars($_POST['description']),
            'css' => htmlspecialchars($_POST['css']),
            'url_token' => $_POST['url_token'],
            'seo_title' => htmlspecialchars($_POST['seo_title'])
        );

        if ($_FILES['icon']['name'])
        {
            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'jpg,jpeg,png,gif',
                'upload_path' => get_setting('upload_dir') . '/feature',
                'is_image' => TRUE
            ))->do_upload('icon');


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

            foreach (AWS_APP::config()->get('image')->feature_thumbnail as $key => $val)
            {
                $thumb_file[$key] = $upload_data['file_path'] . $feature_id . "_" . $val['w'] . "_" . $val['h'] . '.jpg';

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $upload_data['full_path'],
                    'new_image' => $thumb_file[$key],
                    'width' => $val['w'],
                    'height' => $val['h']
                ))->resize();
            }

            unlink($upload_data['full_path']);

            $update_data['icon'] = basename($thumb_file['min']);
        }

        $this->model('feature')->update_feature($feature_id, $update_data);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/feature/list/')
		), 1, null));
    }

    public function remove_feature_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('feature')->delete_feature($_POST['feature_id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_feature_status_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

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

        $settings_var['category_display_mode'] = $_POST['category_display_mode'];
        $settings_var['nav_menu_show_child'] = isset($_POST['nav_menu_show_child']) ? 'Y' : 'N';

        $this->model('setting')->set_vars($settings_var);

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('导航菜单保存成功')));
    }

    public function add_nav_menu_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        switch ($_POST['type'])
        {
            case 'category' :
                $type_id = intval($_POST['type_id']);
                $category = $this->model('system')->get_category_info($type_id);
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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('menu')->remove_nav_menu($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function nav_menu_upload_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        AWS_APP::upload()->initialize(array(
            'allowed_types' => 'jpg,jpeg,png,gif',
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
    }

    public function add_page_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$_POST['url_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入页面 URL')));
        }

        if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
        }

        if ($this->model('page')->get_page_by_url_token($_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的页面 URL')));
        }

        $this->model('page')->add_page($_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/page/')
        ), 1, null));
    }

    public function remove_page_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $this->model('page')->remove_page($_POST['id']);

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function edit_page_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if (!$page_info = $this->model('page')->get_page_by_url_id($_POST['page_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面不存在')));
        }

        if (!$_POST['url_token'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入页面 URL')));
        }

        if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面 URL 只允许输入英文或数字')));
        }

        if ($_page_info = $this->model('page')->get_page_by_url_token($_POST['url_token']))
        {
            if ($_page_info['id'] != $_page_info['id'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('已经存在相同的页面 URL')));
            }
        }

        $this->model('page')->update_page($_POST['page_id'], $_POST['title'], $_POST['keywords'], $_POST['description'], $_POST['contents'], $_POST['url_token']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/page/')
        ), 1, null));
    }

    public function save_page_status_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($_POST['page_ids'])
        {
            foreach ($_POST['page_ids'] AS $page_id => $val)
            {
                $this->model('page')->update_page_enabled($page_id, $_POST['enabled_status'][$page_id]);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('启用状态已自动保存')));
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
                    $this->model('question')->remove_question($question_id);
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

        $this->model('topic')->set_is_parent($topic_id, $_POST['is_parent']);

        if ($_POST['is_parent'] == 0)
        {
            $this->model('topic')->set_parent_id($topic_id, $_POST['parent_id']);
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

            case 'set_parent_id':
                $topic_list = $this->model('topic')->get_topics_by_ids($_POST['topic_ids']);

                foreach ($topic_list AS $topic_info)
                {
                    if ($topic_info['is_parent'] == 0)
                    {
                        $to_update_topic_ids[] = $topic_info['topic_id'];
                    }
                }

                if ($to_update_topic_ids)
                {
                    $this->model('topic')->set_parent_id($to_update_topic_ids, $_POST['parent_id']);
                }

                break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_user_group_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        if ($group_data = $_POST['group'])
        {
            foreach ($group_data as $key => $val)
            {
                if (!$val['group_name'])
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户组名称')));
                }

                if ($val['reputation_factor'])
                {
                    if (!is_digits($val['reputation_factor']) || floatval($val['reputation_factor']) < 0)
                    {
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('威望系数必须为大于或等于 0')));
                    }

                    //if (!is_digits($val['reputation_lower']) || floatval($val['reputation_lower']) < 0 || !is_digits($val['reputation_higer']) || floatval($val['reputation_higer']) < 0)
                    //{
                    //    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('威望介于值必须为大于或等于 0')));
                    //}

                    $val['reputation_factor'] = floatval($val['reputation_factor']);
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
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 1, $group_new['reputation_lower'][$key], $group_new['reputation_higer'][$key], $group_new['reputation_factor'][$key]);
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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

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
                    $this->model('account')->add_user_group($group_new['group_name'][$key], 0);
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

    public function edit_user_group_permission_action()
    {
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }

        $permission_array = array(
            'is_administrator',
            'is_moderator',
            'publish_question',
			'answer_question',
			'publish_article',
			'comment_article',
			'publish_comment',
            'edit_question',
            'edit_topic',
            'create_topic',
            'human_valid',
            'question_valid_hour',
            'answer_valid_hour',
            'visit_site',
            'visit_explore',
            'search_avail',
            'visit_question',
            'visit_topic',
            'visit_feature',
            'visit_people',
            'visit_chapter',
            'answer_show',
            'function_interval',
			'thread_limit_per_day',
			'reply_limit_per_day',
			'comment_limit_per_day',
			'publish_article',
            'edit_article',
            'edit_question_topic',
			'bump_sink',
			'vote_agree',
			'vote_disagree',
			'thank_user',
			'allow_anonymous',
			'post_later',
			'reply_later',
			'send_pm',
			'receive_pm',
			'bring_thread_to_top',
			'invite_answer'
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

            if ($user_info['group_id'] == 1 AND !$this->user_info['permission']['is_administrator'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限编辑管理员账号')));
            }

            if ($_POST['user_name'] != $user_info['user_name'] AND $this->model('account')->get_user_info_by_username($_POST['user_name']))
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已存在')));
            }

            if ($_FILES['user_avatar']['name'])
            {
                AWS_APP::upload()->initialize(array(
                    'allowed_types' => 'jpg,jpeg,png,gif',
                    'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($user_info['uid'], '', 1),
                    'is_image' => TRUE,
                    'max_size' => get_setting('upload_avatar_size_limit'),
                    'file_name' => $this->model('account')->get_avatar($user_info['uid'], '', 2),
                    'encrypt_name' => FALSE
                ))->do_upload('user_avatar');

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

                        case 'upload_invalid_filesize':
                            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
                        break;
                    }
                }

                if (! $upload_data = AWS_APP::upload()->data())
                {
                    H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
                }

                if ($upload_data['is_image'] == 1)
                {
                    foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
                    {
                        $thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($user_info['uid'], $key, 2);

                        AWS_APP::image()->initialize(array(
                            'quality' => 90,
                            'source_image' => $upload_data['full_path'],
                            'new_image' => $thumb_file[$key],
                            'width' => $val['w'],
                            'height' => $val['h']
                        ))->resize();
                    }
                }

                $update_data['avatar_file'] = $this->model('account')->get_avatar($user_info['uid'], null, 1) . basename($thumb_file['min']);
            }

            $verify_apply = $this->model('verify')->fetch_apply($user_info['uid']);

            if ($verify_apply)
            {
                $update_data['verified'] = $_POST['verified'];

                if (!$update_data['verified'])
                {
                    $this->model('verify')->decline_verify($user_info['uid']);
                }
                else if ($update_data['verified'] != $verify_apply['type'])
                {
                    $this->model('verify')->update_apply($user_info['uid'], null, null, null, null, $update_data['verified']);
                }
            }
            else if ($_POST['verified'])
            {
                $verified_id = $this->model('verify')->add_apply($user_info['uid'], null, null, $_POST['verified']);

                $this->model('verify')->approval_verify($verified_id);
            }

            $update_data['forbidden'] = intval($_POST['forbidden']);

            $update_data['group_id'] = intval($_POST['group_id']);

            if ($update_data['group_id'] == 1 AND !$this->user_info['permission']['is_administrator'])
            {
                unset($update_data['group_id']);
            }

            $update_data['sex'] = intval($_POST['sex']);

            $update_data['reputation'] = intval($_POST['reputation']);
            $update_data['agree_count'] = intval($_POST['agree_count']);
            $update_data['currency'] = intval($_POST['currency']);

            $this->model('account')->update_users_fields($update_data, $user_info['uid']);

            if ($_POST['delete_avatar'])
            {
                $this->model('account')->delete_avatar($user_info['uid']);
            }

            if ($_POST['password'])
            {
                $this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $user_info['uid'], fetch_salt());
            }

            $this->model('account')->update_users_attrib_fields(array(
                'signature' => htmlspecialchars($_POST['signature'])
            ), $user_info['uid']);

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

            if ($this->model('account')->check_username($_POST['user_name']))
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

            $this->model('active')->active_user_by_uid($uid);

            if ($_POST['group_id'] == 1 AND !$this->user_info['permission']['is_administrator'])
            {
                $_POST['group_id'] = 4;
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
        $this->model('account')->forbid_user_by_uid($_POST['uid'], $_POST['status'], $this->user_id);

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

    public function register_approval_manage_action()
    {
        if (!is_array($_POST['approval_uids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
                foreach ($_POST['approval_uids'] AS $approval_uid)
                {
                    $this->model('active')->active_user_by_uid($approval_uid);;
                }
            break;

            case 'decline':
                foreach ($_POST['approval_uids'] AS $approval_uid)
                {
                    if ($user_info = $this->model('account')->get_user_info_by_uid($approval_uid))
                    {
                        $this->model('system')->remove_user_by_uid($approval_uid, true);
                    }
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function save_verify_approval_action()
    {
        if ($_POST['uid'])
        {
            $this->model('verify')->update_apply($_POST['uid'], $_POST['name'], $_POST['reason']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/user/verify_approval_list/')
        ), 1, null));
    }

    public function verify_approval_manage_action()
    {
        if (!is_array($_POST['approval_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择条目进行操作')));
        }

        switch ($_POST['batch_type'])
        {
            case 'approval':
            case 'decline':
                $func = $_POST['batch_type'] . '_verify';

                foreach ($_POST['approval_ids'] AS $approval_id)
                {
                    $this->model('verify')->$func($approval_id);
                }
            break;
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
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

            $this->model('system')->remove_user_by_uid($_POST['uid'], $_POST['remove_user_data']);
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

                $this->model('system')->remove_user_by_uid($uid, true);
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
                case 'new_answer_thanks': // 新增答案感谢
                case 'new_favorite_item': // 新增收藏条目
                case 'new_question_thanks': // 新增问题感谢
                case 'new_question_redirect': // 新增问题重定向
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

    public function save_today_topics_action()
    {
        $today_topics = trim($_POST['today_topics']);

        $this->model('setting')->set_vars(array('today_topics' => $today_topics));

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('设置已保存')));
    }

    public function remove_receiving_account_action()
    {
        if (!$_POST['id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择要删除的账号')));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
