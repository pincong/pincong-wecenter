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
        if (H::POST('action') == 'search')
        {
			$param = array();

            foreach ($_POST as $key => $val)
            {
                if (in_array($key, array('user_name')))
                {
                    $val = safe_url_encode($val);
                }

                $param[] = $key . '-' . $val;
            }

            H::ajax_location(url_rewrite('/admin/user/list/' . implode('__', $param)));
        }

        $where = array();

        if (H::GET('type') == 'forbidden')
        {
            $where[] = ['forbidden', 'notEq', 0];
        }

        if (H::GET('type') == 'flagged')
        {
            $where[] = ['flagged', 'notEq', 0];
        }

        if (H::GET('user_name'))
        {
            $where[] = ['user_name', 'like', '%' . escape_like_clause(htmlspecialchars(H::GET('user_name'))) . '%', 's'];
        }

        if (H::GET('group_id'))
        {
            $where[] = ['group_id', 'eq', H::GET('group_id'), 'i'];
        }

        if (H::GET('currency_min'))
        {
            $where[] = ['currency', 'gte', H::GET('currency_min'), 'i'];
        }

        if (H::GET('currency_max'))
        {
            $where[] = ['currency', 'lte', H::GET('currency_max'), 'i'];
        }

        if (H::GET('reputation_min'))
        {
            $where[] = ['reputation', 'gte', H::GET('reputation_min'), 'i'];
        }

        if (H::GET('reputation_max'))
        {
            $where[] = ['reputation', 'lte', H::GET('reputation_max'), 'i'];
        }


        $user_list = $this->model('account')->fetch_page('users', $where, 'uid DESC', H::GET('page'), $this->per_page);
        foreach($user_list as $key => $val)
        {
            $user_list[$key]['reputation_group_id'] = $this->model('usergroup')->get_group_id_by_reputation($val['reputation']);
        }

        $total_rows = $this->model('account')->total_rows();

        $url_param = array();

        foreach($_GET as $key => $val)
        {
            if (!in_array($key, array('app', 'c', 'act', 'page')))
            {
                $url_param[] = htmlspecialchars($key) . '-' . htmlspecialchars($val);
            }
        }

        TPL::assign('pagination', AWS_APP::pagination()->create(array(
            'base_url' => url_rewrite('/admin/user/list/') . implode('__', $url_param),
            'total_rows' => $total_rows,
            'per_page' => $this->per_page
        )));

        $this->crumb(_t('会员列表'));

        TPL::assign('member_group', $this->model('usergroup')->get_reputation_group_list());
        TPL::assign('system_group', $this->model('usergroup')->get_normal_group_list());
        TPL::assign('total_rows', $total_rows);
        TPL::assign('list', $user_list);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::output('admin/user/list');
    }

    public function group_list_action()
    {
        $this->crumb(_t('用户组管理'));

        TPL::assign('member_group', $this->model('usergroup')->get_reputation_group_list());
        TPL::assign('system_group', $this->model('usergroup')->get_system_group_list());
        TPL::assign('custom_group', $this->model('usergroup')->get_custom_group_list());
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_list');
    }

    public function group_edit_action()
    {
        $this->crumb(_t('修改用户组'));

        if (! $group = $this->model('usergroup')->get_user_group_by_id(H::GET('group_id')))
        {
            H::redirect_msg(_t('用户组不存在'), '/admin/user/group_list/');
        }

        TPL::assign('group', $group);
        TPL::assign('group_pms', $group['permission']);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(403));
        TPL::output('admin/user/group_edit');
    }

    public function edit_action()
    {
        $this->crumb(_t('编辑用户资料'));

        if (!$user = $this->model('account')->get_user_info_by_uid(H::GET('uid')))
        {
            H::redirect_msg(_t('用户不存在'), '/admin/user/list/');
        }

		TPL::assign('member_group', $this->model('usergroup')->get_user_group_by_id(
			$this->model('usergroup')->get_group_id_by_reputation($user['reputation'])
		));

        TPL::assign('system_group', $this->model('usergroup')->get_normal_group_list());
        TPL::assign('user', $user);
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::output('admin/user/edit');
    }

    public function user_add_action()
    {
        $this->crumb(_t('添加用户'));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        TPL::assign('system_group', $this->model('usergroup')->get_normal_group_list());

        TPL::output('admin/user/add');
    }


    public function currency_log_action()
    {
        if ($log = $this->model('currency')->fetch_page('currency_log', ['uid', 'eq', H::GET('uid'), 'i'], 'id DESC', H::GET('page'), 50))
        {
            TPL::assign('pagination', AWS_APP::pagination()->create(array(
                'base_url' => url_rewrite('/admin/user/currency_log/uid-' . H::GET_I('uid')),
                'total_rows' => $this->model('currency')->total_rows(),
                'per_page' => 50
            )));

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

        TPL::assign('user', $this->model('account')->get_user_info_by_uid(H::GET('uid')));
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(402));

        $this->crumb(_t('代币日志'));

        TPL::output('admin/user/currency_log');
    }
}