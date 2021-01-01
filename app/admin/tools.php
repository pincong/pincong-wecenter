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
        if (!$this->user_info['permission']['is_administrator'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'));
        }

        @set_time_limit(0);
    }

    public function index_action()
    {
        $this->crumb(AWS_APP::lang()->_t('系统维护'));

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(501));

        TPL::output('admin/tools');
    }

    public function init_action()
    {
        H::redirect_msg(AWS_APP::lang()->_t('正在准备...'), '/admin/tools/' . $_POST['action'] . '/page-1__per_page-' . $_POST['per_page']);
    }

    public function cache_clean_action()
    {
        AWS_APP::cache()->clean();

        H::redirect_msg(AWS_APP::lang()->_t('缓存清理完成'), '/admin/tools/');
    }

    public function update_question_search_index_action()
    {
        if ($questions_list = $this->model('question')->fetch_page('question', null, 'id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($questions_list as $key => $val)
            {
                $this->model('search_fulltext')->push_index('question', $val['title'], $val['id']);

                $this->model('posts')->set_posts_index($val['id'], 'question', $val);
            }

            H::redirect_msg(AWS_APP::lang()->_t('正在更新问题搜索索引') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_question_search_index/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('搜索索引更新完成'), '/admin/tools/');
        }
    }

    public function update_article_search_index_action()
    {
        if ($article_list = $this->model('question')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($article_list as $key => $val)
            {
                $this->model('search_fulltext')->push_index('article', $val['title'], $val['id']);

                $this->model('posts')->set_posts_index($val['id'], 'article', $val);
            }

            H::redirect_msg(AWS_APP::lang()->_t('正在更新文章搜索索引') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_article_search_index/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('搜索索引更新完成'), '/admin/tools/');
        }
    }

    public function update_topic_discuss_count_action()
    {
        if ($this->model('system')->update_topic_discuss_count($_GET['page'], $_GET['per_page']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('正在更新话题统计') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_topic_discuss_count/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('话题统计更新完成'), '/admin/tools/');
        }
    }

	public function blur_time_action()
	{
		$table = $_GET['table'];
		if (!$table)
		{
			$table = 'question';
		}

		switch ($table)
		{
			case 'question':
				$next_table = 'answer';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'answer':
				$next_table = 'question_discussion';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'question_discussion':
				$next_table = 'answer_discussion';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'answer_discussion':
				$next_table = 'article';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'article':
				$next_table = 'article_comment';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'article_comment':
				$next_table = 'video';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'video':
				$next_table = 'video_comment';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'video_comment':
				$next_table = 'posts_index';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'posts_index':
				$next_table = '1'; // finish

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			default:
				H::redirect_msg(AWS_APP::lang()->_t('处理完成'), '/admin/tools/');
			break;
		}
	}

	public function flush_avatars_action()
	{
		if ($users = $this->model('system')->fetch_page('users', null, 'uid ASC', $_GET['page'], $_GET['per_page']))
		{
			$local_upload_dir = get_setting('upload_dir');
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
						'avatar_file' => fetch_salt(4) // 生成随机字符串
					), $val['uid']);
				}
				else
				{
					$this->model('account')->update_user_fields(array(
						'avatar_file' => null // 清空
					), $val['uid']);
				}
			}

			H::redirect_msg(AWS_APP::lang()->_t('正在刷新头像') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/flush_avatars/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
		}
		else
		{
			H::redirect_msg(AWS_APP::lang()->_t('处理完成'), '/admin/tools/');
		}
	}

	public function move_to_trash_action()
	{
		$trash_category_id = intval(get_setting('trash_category_id'));
		if (!$trash_category_id)
		{
			H::redirect_msg(AWS_APP::lang()->_t('垃圾箱没有启用, 无法继续'), '/admin/tools/');
		}

		$table = $_GET['table'];
		if (!$table)
		{
			$table = 'question';
		}

		switch ($table)
		{
			case 'question':
				$next_table = 'article';

				if ($list = AWS_APP::model()->fetch_page($table, '`title` IS NULL', 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'category_id' => $trash_category_id
						), 'id = ' . intval($val['id']));

						AWS_APP::model()->update('posts_index', array(
							'category_id' => $trash_category_id
						), "post_type = 'question' AND post_id = " . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/move_to_trash/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/move_to_trash/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'article':
				$next_table = 'video';

				if ($list = AWS_APP::model()->fetch_page($table, '`title` IS NULL', 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'category_id' => $trash_category_id
						), 'id = ' . intval($val['id']));

						AWS_APP::model()->update('posts_index', array(
							'category_id' => $trash_category_id
						), "post_type = 'article' AND post_id = " . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/move_to_trash/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/move_to_trash/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'video':
				$next_table = '1'; // finish

				if ($list = AWS_APP::model()->fetch_page($table, '`title` IS NULL', 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'category_id' => $trash_category_id
						), 'id = ' . intval($val['id']));

						AWS_APP::model()->update('posts_index', array(
							'category_id' => $trash_category_id
						), "post_type = 'video' AND post_id = " . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/move_to_trash/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/move_to_trash/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			default:
				H::redirect_msg(AWS_APP::lang()->_t('处理完成'), '/admin/tools/');
			break;
		}
	}

}