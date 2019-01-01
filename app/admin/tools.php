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

    public function update_users_reputation_action()
    {
        if ($this->model('reputation')->calculate((($_GET['page'] * $_GET['per_page']) - $_GET['per_page']), $_GET['per_page']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('正在更新用户威望') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_users_reputation/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户威望更新完成'), '/admin/tools/');
        }
    }

    public function bbcode_to_markdown_action()
    {
        switch ($_GET['type'])
        {
            default:
                if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($questions_list as $key => $val)
                    {
                        $this->model('question')->update('question', array(
                            'question_detail' => FORMAT::bbcode_2_markdown($val['question_detail'])
                        ), 'question_id = ' . intval($val['question_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换问题内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_markdown/page-1__type-answer__per_page-' . $_GET['per_page']);
                }
            break;

            case 'answer':
                if ($answer_list = $this->model('question')->fetch_page('answer', null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($answer_list as $key => $val)
                    {
                        $this->model('answer')->update_answer_by_id($val['answer_id'], array(
                            'answer_content' => FORMAT::bbcode_2_markdown($val['answer_content'])
                        ));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换回答内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-answer__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/bbcode_to_markdown/page-1__type-topic__per_page-' . $_GET['per_page']);
                }
            break;

            case 'topic':
                if ($topic_list = $this->model('topic')->get_topic_list(null, 'topic_id ASC', $_GET['per_page'], $_GET['page']))
                {
                    foreach ($topic_list as $key => $val)
                    {
                        $this->model('topic')->update('topic', array(
                            'topic_description' => FORMAT::bbcode_2_markdown($val['topic_description'])
                        ), 'topic_id = ' . intval($val['topic_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换话题内容 BBCode') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/bbcode_to_markdown/page-' . ($_GET['page'] + 1) . '__type-topic__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('BBCode 转换完成'), '/admin/tools/');
                }
            break;
        }
    }

    public function markdown_to_bbcode_action()
    {
        switch ($_GET['type'])
        {
            default:
                if ($questions_list = $this->model('question')->fetch_page('question', null, 'question_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($questions_list as $key => $val)
                    {
                        $this->model('question')->update('question', array(
                            'question_detail' => FORMAT::markdown_2_bbcode($val['question_detail'])
                        ), 'question_id = ' . intval($val['question_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换问题内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/markdown_to_bbcode/page-1__type-answer__per_page-' . $_GET['per_page']);
                }
            break;

            case 'answer':
                if ($answer_list = $this->model('question')->fetch_page('answer', null, 'answer_id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($answer_list as $key => $val)
                    {
                        $this->model('answer')->update_answer_by_id($val['answer_id'], array(
                            'answer_content' => FORMAT::markdown_2_bbcode($val['answer_content'])
                        ));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换回答内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__type-answer__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/markdown_to_bbcode/page-1__type-article__per_page-' . $_GET['per_page']);
                }
            break;

            case 'article':
                if ($article_list = $this->model('article')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
                {
                    foreach ($article_list as $key => $val)
                    {
                        $this->model('article')->update('article', array(
                            'message' => FORMAT::markdown_2_bbcode($val['message'])
                        ), 'id = ' . $val['id']);
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换文章内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__type-article__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/markdown_to_bbcode/page-1__type-topic__per_page-' . $_GET['per_page']);
                }
            break;

            case 'topic':
                if ($topic_list = $this->model('topic')->get_topic_list(null, 'topic_id ASC', $_GET['per_page'], $_GET['page']))
                {
                    foreach ($topic_list as $key => $val)
                    {
                        $this->model('topic')->update('topic', array(
                            'topic_description' => FORMAT::markdown_2_bbcode($val['topic_description'])
                        ), 'topic_id = ' . intval($val['topic_id']));
                    }

                    H::redirect_msg(AWS_APP::lang()->_t('正在转换话题内容 Markdown') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/markdown_to_bbcode/page-' . ($_GET['page'] + 1) . '__type-topic__per_page-' . $_GET['per_page']);
                }
                else
                {
                    H::redirect_msg(AWS_APP::lang()->_t('Markdown 转换完成'), '/admin/tools/');
                }
            break;
        }
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
        if ($articles_list = $this->model('question')->fetch_page('article', null, 'id ASC', $_GET['page'], $_GET['per_page']))
        {
            foreach ($articles_list as $key => $val)
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

    public function update_fresh_actions_action()
    {
        if ($this->model('system')->update_associate_fresh_action($_GET['page'], $_GET['per_page']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('正在更新最新动态') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/update_fresh_actions/page-' . ($_GET['page'] + 1) . '__per_page-' . $_GET['per_page']);
        }
        else
        {
            H::redirect_msg(AWS_APP::lang()->_t('最新动态更新完成'), '/admin/tools/');
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
				$next_table = 'question_comments';

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


			case 'question_comments':
				$next_table = 'answer_comments';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'time' => fake_time($val['time'])
						), 'id = ' . intval($val['id']));
					}

					H::redirect_msg(AWS_APP::lang()->_t('正在处理 '.$table.' 表') . ', ' . AWS_APP::lang()->_t('批次: %s', $_GET['page']), '/admin/tools/blur_time/page-' . ($_GET['page'] + 1) . '__table-'.$table.'__per_page-' . $_GET['per_page']);
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('准备继续...'), '/admin/tools/blur_time/page-1__table-'.$next_table.'__per_page-' . $_GET['per_page']);
				}
			break;


			case 'answer_comments':
				$next_table = 'article';

				if ($list = AWS_APP::model()->fetch_page($table, null, 'id ASC', $_GET['page'], $_GET['per_page']))
				{
					foreach ($list as $key => $val)
					{
						AWS_APP::model()->update($table, array(
							'time' => fake_time($val['time'])
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
				$next_table = 'article_comments';

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


			case 'article_comments':
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