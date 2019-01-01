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
        $this->crumb(AWS_APP::lang()->_t('系统维护'), 'admin/tools/');

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
        if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($questions_list as $key => $val)
            {
                $this->model('search_fulltext')->push_index('question', $val['question_content'], $val['question_id']);

                $this->model('posts')->set_posts_index($val['question_id'], 'question', $val);
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

				if ($list = AWS_APP::model()->fetch_page($table, null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time']),
							'update_time' => fake_time($val['update_time'])
						), 'question_id = ' . intval($val['question_id']));
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

				if ($list = AWS_APP::model()->fetch_page($table, null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'add_time' => fake_time($val['add_time'])
						), 'answer_id = ' . intval($val['answer_id']));
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
				$next_table = 'video_danmaku';

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


			case 'video_danmaku':
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

}