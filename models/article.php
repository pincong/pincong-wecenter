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

class article_class extends AWS_MODEL
{
	public function get_articles_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_articles_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('article', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, S::get('cache_level_normal'));
		}

		return $list;
	}

	public function get_article_comments_by_uid($uid, $page, $per_page)
	{
		$cache_key = 'user_article_comments_' . intval($uid) . '_page_' . intval($page);
		if ($list = AWS_APP::cache()->get($cache_key))
		{
			return $list;
		}

		$list = $this->fetch_page('article_reply', ['uid', 'eq', $uid, 'i'], 'id DESC', $page, $per_page);
		foreach ($list AS $key => $val)
		{
			$parent_ids[] = $val['parent_id'];
		}

		if ($parent_ids)
		{
			$parents = $this->model('post')->get_posts_by_ids('article', $parent_ids);
			foreach ($list AS $key => $val)
			{
				$list[$key]['article_info'] = $parents[$val['parent_id']];
			}
		}

		if (count($list) > 0)
		{
			AWS_APP::cache()->set($cache_key, $list, S::get('cache_level_normal'));
		}

		return $list;
	}

	public function modify_article($id, $title, $message, $log_uid)
	{
		if (!$item_info = $this->model('post')->get_thread_info_by_id('article', $id))
		{
			return false;
		}

		$this->update('article', array(
			'title' => htmlspecialchars($title),
			'message' => htmlspecialchars($message)
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('article', $id, 'article', $id, '编辑', $log_uid);

		return true;
	}

	public function clear_article($id, $log_uid)
	{
		if (!$item_info = $this->model('post')->get_thread_info_by_id('article', $id))
		{
			return false;
		}

		$data = array(
			'title' => null,
			'message' => null,
		);

		$trash_category_id = S::get_int('trash_category_id');
		if ($trash_category_id)
		{
			$where = [['post_id', 'eq', $id, 'i'], ['post_type', 'eq', 'article']];
			$this->update('posts_index', array('category_id' => $trash_category_id), $where);
			$data['category_id'] = $trash_category_id;
		}

		$this->update('article', $data, ['id', 'eq', $id, 'i']);

		$this->model('content')->log('article', $id, 'article', $id, '删除', $log_uid, 'category', $item_info['category_id']);

		return true;
	}

	public function modify_article_comment($id, $message, $log_uid)
	{
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('article_reply', $id))
		{
			return false;
		}

		$this->update('article_reply', array(
			'message' => htmlspecialchars($message)
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('article', $reply_info['parent_id'], 'article_reply', $id, '编辑', $log_uid);

		return true;
	}

	public function clear_article_comment($id, $log_uid)
	{
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('article_reply', $id))
		{
			return false;
		}

		$this->update('article_reply', array(
			'message' => null,
			'fold' => 1
		), ['id', 'eq', $id, 'i']);

		$this->model('content')->log('article', $reply_info['parent_id'], 'article_reply', $id, '删除', $log_uid);

		return true;
	}


	// 同时获取用户信息
	public function get_article_by_id($id)
	{
		if ($item = $this->fetch_row('article', ['id', 'eq', $id, 'i']))
		{
			$item['user_info'] = $this->model('account')->get_user_info_by_uid($item['uid']);
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_article_comment_by_id($id)
	{
		if ($item = $this->fetch_row('article_reply', ['id', 'eq', $id, 'i']))
		{
			$user_infos = $this->model('account')->get_user_info_by_uids(array(
				$item['uid'],
				$item['at_uid']
			));

			$item['user_info'] = $user_infos[$item['uid']] ?? null;
			$item['at_user_info'] = $user_infos[$item['at_uid']] ?? null;
		}

		return $item;
	}

	// 同时获取用户信息
	public function get_article_comments($thread_ids, $page, $per_page, $order = 'id ASC')
	{
		$where = ['parent_id', 'in', $thread_ids, 'i'];

		if ($list = $this->fetch_page('article_reply', $where, $order, $page, $per_page))
		{
			foreach ($list AS $key => $val)
			{
				$uids[$val['uid']] = $val['uid'];

				if ($val['at_uid'])
				{
					$uids[$val['at_uid']] = $val['at_uid'];
				}
			}

			if ($uids)
			{
				$user_infos = $this->model('account')->get_user_info_by_uids($uids);
			}

			foreach ($list AS $key => $val)
			{
				$list[$key]['user_info'] = $user_infos[$val['uid']] ?? null;
				$list[$key]['at_user_info'] = $user_infos[$val['at_uid']] ?? null;
			}
		}

		return $list;
	}

}