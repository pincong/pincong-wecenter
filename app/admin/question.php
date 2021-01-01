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

if (! defined('IN_ANWSION'))
{
	die();
}

class question extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(301));
	}

	public function question_list_action()
	{
		if (H::is_post())
		{
			$param = array();

			foreach ($_POST as $key => $val)
			{
				if ($key == 'start_date' OR $key == 'end_date')
				{
					$val = base64_encode($val);
				}

				if ($key == 'keyword' OR $key == 'user_name')
				{
					$val = safe_url_encode($val);
				}

				$param[] = $key . '-' . $val;
			}

			H::ajax_location(url_rewrite('/admin/question/question_list/' . implode('__', $param)));
		}

		$where = array();

		if (H::GET('keyword'))
		{
			$where[] = ['title', 'like', '%' . escape_like_clause(htmlspecialchars(H::GET('keyword'))) . '%', 's'];
		}

		if (H::GET('category_id'))
		{
			$where[] = ['category_id', 'eq', $category_id, 'i'];
		}

		if (base64_decode(H::GET('start_date')))
		{
			$where[] = ['add_time', 'gte', strtotime(base64_decode(H::GET('start_date')))];
		}

		if (base64_decode(H::GET('end_date')))
		{
			$where[] = ['add_time', 'lte', strtotime('+1 day', strtotime(base64_decode(H::GET('end_date'))))];
		}

		if (H::GET('user_name'))
		{
			$user_info = $this->model('account')->get_user_info_by_username(H::GET('user_name'));

			$where[] = ['uid', 'eq', $user_info['uid'], 'i'];
		}

		if (H::GET('answer_count_min'))
		{
			$where[] = ['reply_count', 'gte', H::GET('answer_count_min'), 'i'];
		}

		if (H::GET('answer_count_max'))
		{
			$where[] = ['reply_count', 'lte', H::GET('answer_count_max'), 'i'];
		}

		if ($question_list = $this->model('question')->fetch_page('question', $where, 'id DESC', H::GET('page'), $this->per_page))
		{
			$total_rows = $this->model('question')->total_rows();

			foreach ($question_list AS $key => $val)
			{
				$question_list_uids[$val['uid']] = $val['uid'];
			}

			if ($question_list_uids)
			{
				$question_list_user_infos = $this->model('account')->get_user_info_by_uids($question_list_uids);
			}

			foreach ($question_list AS $key => $val)
			{
				$question_list[$key]['user_info'] = $question_list_user_infos[$val['uid']];
			}
		}

		$url_param = array();

		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = htmlspecialchars($key) . '-' . htmlspecialchars($val);
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/admin/question/question_list/') . implode('__', $url_param),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		)));

		$this->crumb(_t('问题管理'));

		TPL::assign('question_count', $total_rows);
		TPL::assign('category_list', $this->model('category')->get_category_list());
		TPL::assign('keyword', H::GET('keyword'));
		TPL::assign('list', $question_list);

		TPL::output('admin/question/question_list');
	}

}