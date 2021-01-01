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

class user extends AWS_ADMIN_CONTROLLER
{
    public function list_action()
    {
        if ($_POST['action'] == 'search')
        {
            foreach ($_POST as $key => $val)
            {
                if (in_array($key, array('user_name')))
                {
                    $val = rawurlencode($val);
                }

                $param[] = $key . '-' . $val;
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/admin/user/list/' . implode('__', $param))
            ), 1, null));
        }

        $where = array();

        if ($_GET['type'] == 'forbidden')
        {
            $where[] = 'forbidden <> 0';
        }

        if ($_GET['type'] == 'flagged')
        {
            $where[] = 'flagged <> 0';
        }

        if ($_GET['user_name'])
        {
            $where[] = "user_name LIKE '%" . $this->model('people')->quote($_GET['user_name']) . "%'";
        }

        if ($_GET['group_id'])
        {
            $where[] = 'group_id = ' . intval($_GET['group_id']);
        }

        if ($_GET['currency_min'])
        {
            $where[] = 'currency >= ' . intval($_GET['currency_min']);
        }

        if ($_GET['currency_max'])
        {
            $where[] = 'currency <= ' . intval($_GET['currency_max']);
        }

        if ($_GET['reputation_min'])
        {
            $where[] = 'reputation >= ' . intval($_GET['reputation_min']);
        }

        if ($_GET['reputation_max'])
        {
            $where[] = 'reputation <= ' . intval($_GET['reputation_max']);
        }


        $user_list = $this->model('people')->fetch_page('users', implode(' AND ', $where), 'uid DESC', $_GET['page'], $this->per_page);
        foreach($user_list as $key => $val)
        {
            $user_list[$key]['reputation_group_id'] = $this->model('reputation')->get_reputation_group_id_by_reputation($val['reputation']);
            $user_list[$key]['url_token'] = urlencode($val['user_name']);
        }

        $total_rows = $this->model('people')->found_rows();

        $url_param = array();

        foreach($_GET as $key => $val)
        {
            if (!in_array($key, array('app', 'c', 'act', 'page')))
            {
                $url_param[] = $key . '-' . $val;
            }
        }

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/user/list/') . implode('__', $url_param),
            'total_rows' => $total_rows,
            'per_page' => $this->per_page
        ))->create_links());

        $this->crumb(AWS_APP::lang()->_t('会员列表'));

        TPL::assign('member_group', $this->model('account')->get_user_group_list(1));
        TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
        TPL::assign('total_rows', $total_rows);
        TPL::assign('list', $user_list);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::output('admin/user/list');
    }

    public function group_list_action()
    {
        $this->crumb(AWS_APP::lang()->_t('用户组管理'));

        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('member_group', $this->model('account')->get_user_group_list(1));
        TPL::assign('system_group', $this->model('account')->get_user_group_list(0, 0));
        TPL::assign('custom_group', $this->model('account')->get_user_group_list(0, 1));
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_list');
    }

    public function group_edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('修改用户组'));

        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        if (! $group = $this->model('account')->get_user_group_by_id($_GET['group_id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户组不存在'), '/admin/user/group_list/');
        }

        TPL::assign('group', $group);
        TPL::assign('group_pms', $group['permission']);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_edit');
    }

    public function edit_action()
    {
        $this->crumb(AWS_APP::lang()->_t('编辑用户资料'));

        if (!$user = $this->model('account')->get_user_info_by_uid($_GET['uid']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/admin/user/list/');
        }

        if ($user['group_id'] == 1 AND !$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有权限编辑管理员账号'), '/admin/user/list/');
        }

        $user['recovery_code'] = $this->model('account')->calc_user_recovery_code($user['uid']);

		TPL::assign('member_group', $this->model('account')->get_user_group_by_id(
			$this->model('reputation')->get_reputation_group_id_by_reputation($user['reputation'])
		));

        TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
        TPL::assign('user', $user);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::output('admin/user/edit');
    }

    public function user_add_action()
    {
        $this->crumb(AWS_APP::lang()->_t('添加用户'));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::assign('system_group', $this->model('account')->get_user_group_list(0));

        TPL::output('admin/user/add');
    }


    public function currency_log_action()
    {
        if ($log = $this->model('currency')->fetch_page('currency_log', 'uid = ' . intval($_GET['uid']), 'id DESC', $_GET['page'], 50))
        {
            TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
                'base_url' => get_js_url('/admin/user/currency_log/uid-' . intval($_GET['uid'])),
                'total_rows' => $this->model('currency')->found_rows(),
                'per_page' => 50
            ))->create_links());

            foreach ($log AS $key => $val)
            {
                $parse_items[$val['id']] = array(
                    'item_id' => $val['item_id'],
                    'item_type' => $val['item_type']
                );
            }

            TPL::assign('currency_log', $log);
            TPL::assign('currency_log_detail', $this->model('currency')->parse_log_items($parse_items));
        }

        TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid']));
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        $this->crumb(AWS_APP::lang()->_t('代币日志'));

        TPL::output('admin/user/currency_log');
    }
}