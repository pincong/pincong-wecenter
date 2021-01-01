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

class article extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(309));
	}

	public function list_action()
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

			H::ajax_location(url_rewrite('/admin/article/list/' . implode('__', $param)));
		}


		$where = array();

		if (H::GET('keyword'))
		{
			$where[] = ['title', 'like', '%' . escape_like_clause(htmlspecialchars(H::GET('keyword'))) . '%', 's'];
		}

		if (H::GET('start_date'))
		{
			$where[] = ['add_time', 'gte', strtotime(base64_decode(H::GET('start_date')))];
		}

		if (H::GET('end_date'))
		{
			$where[] = ['add_time', 'lte', strtotime('+1 day', strtotime(base64_decode(H::GET('end_date'))))];
		}

		if (H::GET('user_name'))
		{
			$user_info = $this->model('account')->get_user_info_by_username(H::GET('user_name'));

			$where[] = ['uid', 'eq', $user_info['uid'], 'i'];
		}

		if (H::GET('comment_count_min'))
		{
			$where[] = ['reply_count', 'gte', H::GET('comment_count_min'), 'i'];
		}

		if (H::GET('answer_count_max'))
		{
			$where[] = ['reply_count', 'lte', H::GET('comment_count_max'), 'i'];
		}

		if ($article_list = $this->model('article')->fetch_page('article', $where, 'id DESC', H::GET('page'), $this->per_page))
		{
			$search_articles_total = $this->model('article')->total_rows();
		}

		if ($article_list)
		{
			foreach ($article_list AS $key => $val)
			{
				$article_list_uids[$val['uid']] = $val['uid'];
			}

			if ($article_list_uids)
			{
				$article_list_user_infos = $this->model('account')->get_user_info_by_uids($article_list_uids);
			}

			foreach ($article_list AS $key => $val)
			{
				$article_list[$key]['user_info'] = $article_list_user_infos[$val['uid']];
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
			'base_url' => url_rewrite('/admin/article/list/') . implode('__', $url_param),
			'total_rows' => $search_articles_total,
			'per_page' => $this->per_page
		)));

		$this->crumb(_t('文章管理'));

		TPL::assign('articles_count', $search_articles_total);
		TPL::assign('list', $article_list);

		TPL::output('admin/article/list');
	}
}