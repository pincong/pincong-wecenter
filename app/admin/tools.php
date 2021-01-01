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

class tools extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        @set_time_limit(0);
    }

    public function index_action()
    {
        $this->crumb(_t('系统维护'));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(501));

        TPL::output('admin/tools');
    }

    public function init_action()
    {
        H::redirect_msg(_t('正在准备...'), '/admin/tools/' . H::POST('action') . '/page-1__per_page-' . H::POST('per_page'));
    }

    public function cache_clean_action()
    {
        AWS_APP::cache()->clean();

        H::redirect_msg(_t('缓存清理完成'), '/admin/tools/');
    }

    public function update_question_search_index_action()
    {
        if ($questions_list = $this->model('question')->fetch_page('question', null, 'id ASC', H::GET('page'), H::GET('per_page')))
        {
            foreach ($questions_list as $key => $val)
            {
                $this->model('threadindex')->set_posts_index($val['id'], 'question', $val);
            }

            H::redirect_msg(_t('正在更新问题搜索索引') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/update_question_search_index/page-' . (H::GET('page') + 1) . '__per_page-' . H::GET('per_page'));
        }
        else
        {
            H::redirect_msg(_t('搜索索引更新完成'), '/admin/tools/');
        }
    }

    public function update_article_search_index_action()
    {
        if ($article_list = $this->model('question')->fetch_page('article', null, 'id ASC', H::GET('page'), H::GET('per_page')))
        {
            foreach ($article_list as $key => $val)
            {
                $this->model('threadindex')->set_posts_index($val['id'], 'article', $val);
            }

            H::redirect_msg(_t('正在更新文章搜索索引') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/update_article_search_index/page-' . (H::GET('page') + 1) . '__per_page-' . H::GET('per_page'));
        }
        else
        {
            H::redirect_msg(_t('搜索索引更新完成'), '/admin/tools/');
        }
    }

    public function update_topic_discuss_count_action()
    {
        if ($this->model('system')->update_topic_discuss_count(H::GET('page'), H::GET('per_page')))
        {
            H::redirect_msg(_t('正在更新话题统计') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/update_topic_discuss_count/page-' . (H::GET('page') + 1) . '__per_page-' . H::GET('per_page'));
        }
        else
        {
            H::redirect_msg(_t('话题统计更新完成'), '/admin/tools/');
        }
    }

	public function blur_time_action()
	{
		$table = H::GET('table');
		if (!$table)
		{
			$table = 'question';
		}

		switch ($table)
		{
			case 'question':
				$next_table = 'question_reply';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'question_reply':
				$next_table = 'question_comment';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'question_comment':
				$next_table = 'question_discussion';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'question_discussion':
				$next_table = 'article';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'article':
				$next_table = 'article_reply';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'article_reply':
				$next_table = 'video';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'video':
				$next_table = 'video_reply';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'video_reply':
				$next_table = 'posts_index';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'posts_index':
				$next_table = '1'; // finish

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), ['id', 'eq', $val['id'], 'i']);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/blur_time/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			default:
				H::redirect_msg(_t('处理完成'), '/admin/tools/');
			break;
		}
	}

	public function flush_avatars_action()
	{
		if ($users = $this->model('system')->fetch_page('users', null, 'uid ASC', H::GET('page'), H::GET('per_page')))
		{
			$local_upload_dir = S::get('upload_dir');
			foreach ($users as $key => $val)
			{
				$file_exists = false;
				$path = '/avatar/' . $this->model('avatar')->get_avatar_path($val['uid'], 'min');

				if (Services_RemoteStorage::is_enabled())
				{
					$response = Services_RemoteStorage::get($path);
					if ($response AND $response['status_code'] == 200)
					{
						$file_exists = true;
					}
				}
				else
				{
					if (file_exists($local_upload_dir . $path))
					{
						$file_exists = true;
					}
				}

				if ($file_exists == true)
				{
					$this->model('account')->update_user_fields(array(
						'avatar_file' => random_string(4) // 生成随机字符串
					), $val['uid']);
				}
				else
				{
					$this->model('account')->update_user_fields(array(
						'avatar_file' => null // 清空
					), $val['uid']);
				}
			}

			H::redirect_msg(_t('正在刷新头像') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/flush_avatars/page-' . (H::GET('page') + 1) . '__per_page-' . H::GET('per_page'));
		}
		else
		{
			H::redirect_msg(_t('处理完成'), '/admin/tools/');
		}
	}

	public function move_to_trash_action()
	{
		$trash_category_id = S::get_int('trash_category_id');
		if (!$trash_category_id)
		{
			H::redirect_msg(_t('垃圾箱没有启用, 无法继续'), '/admin/tools/');
		}

		$table = H::GET('table');
		if (!$table)
		{
			$table = 'question';
		}

		switch ($table)
		{
			case 'question':
				$next_table = 'article';

				if ($list = AWS_APP::model()->fetch_page($table, '`title` IS NULL', 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'category_id' => $trash_category_id
						), ['id', 'eq', $val['id'], 'i']);

						AWS_APP::model()->update('posts_index', array(
							'category_id' => $trash_category_id
						), [['post_type', 'eq', 'question'], ['post_id', 'eq', $val['id'], 'i']]);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/move_to_trash/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/move_to_trash/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'article':
				$next_table = 'video';

				if ($list = AWS_APP::model()->fetch_page($table, '`title` IS NULL', 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'category_id' => $trash_category_id
						), ['id', 'eq', $val['id'], 'i']);

						AWS_APP::model()->update('posts_index', array(
							'category_id' => $trash_category_id
						), [['post_type', 'eq', 'article'], ['post_id', 'eq', $val['id'], 'i']]);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/move_to_trash/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/move_to_trash/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			case 'video':
				$next_table = '1'; // finish

				if ($list = AWS_APP::model()->fetch_page($table, '`title` IS NULL', 'id ASC', H::GET('page'), H::GET('per_page')))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'category_id' => $trash_category_id
						), ['id', 'eq', $val['id'], 'i']);

						AWS_APP::model()->update('posts_index', array(
							'category_id' => $trash_category_id
						), [['post_type', 'eq', 'video'], ['post_id', 'eq', $val['id'], 'i']]);
					}

					H::redirect_msg(_t('正在处理 '.$table.' 表') . ', ' . _t('批次: %s', H::GET('page')), '/admin/tools/move_to_trash/page-' . (H::GET('page') + 1) . '__table-'.$table.'__per_page-' . H::GET('per_page'));
				}
				else
				{
					H::redirect_msg(_t('准备继续...'), '/admin/tools/move_to_trash/page-1__table-'.$next_table.'__per_page-' . H::GET('per_page'));
				}
			break;


			default:
				H::redirect_msg(_t('处理完成'), '/admin/tools/');
			break;
		}
	}

}