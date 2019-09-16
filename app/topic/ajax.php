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

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'topic_info',
				'get_focus_users'
			);
		}

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function get_focus_users_action()
	{
		H::ajax_json_output($this->model('topic')->get_focus_users_by_topic($_GET['topic_id'], 18));
	}

	public function topic_info_action()
	{
		$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']);

		$topic_info['type'] = 'topic';

		$topic_info['topic_description'] = cjk_substr(strip_tags($topic_info['topic_description']), 0, 80, 'UTF-8', '...');

		$topic_info['focus_count'] = $topic_info['focus_count'];

		if ($this->user_id)
		{
			$topic_info['focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}

		$topic_info['topic_pic'] = get_topic_pic_url($topic_info, 'mid');
		$topic_info['url'] = get_js_url('/topic/' . $topic_info['url_token']);

		H::ajax_json_output($topic_info);
	}

	public function edit_topic_action()
	{
		if (!check_user_operation_interval('edit_topic', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}

			if ($this->model('topic')->has_lock_topic($_POST['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的话题不能编辑')));
			}
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		if (!$_POST['topic_description'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写话题描述')));
		}

		$this->model('topic')->update_topic($this->user_id, $_POST['topic_id'], null, $_POST['topic_description']);

		set_user_operation_last_time('edit_topic', $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/topic/' . $_POST['topic_id'])
		), 1, null));
	}

	public function save_related_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的话题不能编辑')));
			}
		}

		if (!$this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		if (!$topic_title = trim($_POST['topic_title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入话题标题')));
		}

		if (strstr($topic_title, '/') OR strstr($topic_title, '-') OR strstr($topic_title, '&'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题不能包含 / - &')));
		}

		if (get_setting('topic_title_limit') AND cjk_strlen($topic_title) > get_setting('topic_title_limit'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题字数不得超过 %s 字', get_setting('topic_title_limit'))));
		}

		if (! $related_id = $this->model('topic')->save_topic($topic_title, $this->user_id, $this->user_info['permission']['create_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题已锁定或没有创建话题权限')));
		}

		if (!$this->model('topic')->save_related_topic($_GET['topic_id'], $related_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同推荐话题')));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'related_id' => $related_id,
		), 1, null));
	}

	public function remove_related_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的话题不能编辑')));
			}
		}

		$this->model('topic')->remove_related_topic($_GET['topic_id'], $_GET['related_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function upload_topic_pic_action()
	{
		if (get_setting('upload_enable') == 'N' AND !$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('本站未开启上传功能')));
		}

		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的话题不能编辑')));
			}
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		if (!$this->model('topic')->upload_image('aws_upload_file', $_GET['topic_id'], $error))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', $error));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'thumb' => get_setting('upload_url') . '/topic/' . $this->model('topic')->get_image_path($_GET['topic_id'], 'mid') . '?' . rand(1, 999)
		), '1', null));
	}

	public function focus_topic_action()
	{
		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('topic')->add_focus_topic($this->user_id, intval($_POST['topic_id']))
		), '1', null));
	}

	public function lock_topic_action()
	{
		$this->model('topic')->lock_topic_by_id($_GET['topic_id'], $this->model('topic')->has_lock_topic($_GET['topic_id']));

		H::ajax_json_output(AWS_APP::RSM(null, 1));
	}

	public function set_parent_id_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		if ($topic_info['is_parent'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不允许为根话题设置根话题')));
		}

		if ($topic_info['topic_id'] == intval($_POST['parent_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不允许将根话题设置为自己')));
		}

		$this->model('topic')->set_parent_id($topic_info['topic_id'], $_POST['parent_id']);

		H::ajax_json_output(AWS_APP::RSM(null, -1, null));
	}

	public function save_url_token_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题别名只允许输入英文或数字')));
		}

		if ($this->model('topic')->check_url_token($_POST['url_token'], $topic_info['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题别名已经被占用请更换一个')));
		}

		if (preg_match("/^[\d]+$/i", $_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题别名不允许为纯数字')));
		}

		$this->model('topic')->update_url_token($_POST['url_token'], $topic_info['topic_id']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/topic/' . $_POST['url_token'])
		), 1, null));
	}

	public function save_seo_title_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		$this->model('topic')->update_seo_title($_POST['seo_title'], $topic_info['topic_id']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/topic/' . $topic_info['url_token'])
		), 1, null));
	}

	public function lock_action()
	{
		if (! $this->user_info['permission']['manage_topic'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (! $topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('话题不存在')));
		}

		$this->model('topic')->lock_topic_by_ids($_POST['topic_id'], !$topic_info['topic_lock']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_action()
	{
		if (! $this->user_info['permission']['manage_topic'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		$this->model('topic')->remove_topic_by_ids($_POST['topic_id']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/topic/')
		), 1, null));
	}

	public function merge_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的话题不能编辑')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_title($_POST['topic_title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题不存在')));
		}

		if ($topic_info['topic_id'] == $_POST['target_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题合并不能与自己合并')));
		}

		if ($topic_info['merged_id'])
		{
			$merged_topic_info = $this->model('topic')->get_topic_by_id($topic_info['merged_id']);

			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该话题已经与 %s 合并', $merged_topic_info['topic_title'])));
		}

		$this->model('topic')->merge_topic($topic_info['topic_id'], $_POST['target_id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_merge_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		$this->model('topic')->remove_merge_topic($_POST['source_id'], $_POST['target_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_topic_relation_action()
	{
		if (!$_POST['topic_id'] OR !$_POST['item_id'] OR !$_POST['type'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定的项目不存在')));
		}

		switch ($_POST['type'])
		{
			case 'question':
				if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}

				if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $question_info['uid'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
				}
			break;

			case 'article':
				if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['item_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}

				if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $article_info['uid'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
				}
			break;

			case 'video':
				if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['item_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}

				if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $video_info['uid'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
				}
			break;

			default:
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}
			break;
		}

		$this->model('topic')->remove_topic_relation($this->user_id, $_POST['topic_id'], $_POST['item_id'], $_POST['type']);

		H::ajax_json_output(AWS_APP::RSM(null, -1, null));
	}

	public function save_topic_relation_action()
	{
		if (!$_POST['topic_title'] OR !$_POST['item_id'] OR !$_POST['type'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定的项目不存在')));
		}

		if (!check_user_operation_interval('edit_topic', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		switch ($_POST['type'])
		{
			case 'question':
				if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}

				if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $question_info['uid'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
				}
			break;

			case 'article':
				if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['item_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}

				if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $article_info['uid'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
				}
			break;

			case 'video':
				if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['item_id']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}

				if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $video_info['uid'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
				}
			break;

			default:
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定项目不存在')));
				}
			break;
		}

		if (!$topic_title = trim($_POST['topic_title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入话题标题')));
		}

		if (strstr($topic_title, '/') OR strstr($topic_title, '-') OR strstr($topic_title, '&'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题不能包含 / - &')));
		}

		if (get_setting('topic_title_limit') AND cjk_strlen($topic_title) > get_setting('topic_title_limit') AND !$this->model('topic')->get_topic_id_by_title($topic_title))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题字数不得超过 %s 字', get_setting('topic_title_limit'))));
		}

		switch ($_POST['type'])
		{
			case 'question':
				if ($question_info['lock'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定内容不能添加话题')));
				}
			break;

			case 'article':
				if ($article_info['lock'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定内容不能添加话题')));
				}
			break;

			case 'video':
				if ($video_info['lock'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定内容不能添加话题')));
				}
			break;
		}

		if (sizeof($this->model('topic')->get_topics_by_item_id($_POST['item_id'], $_POST['type'])) >= get_setting('question_topics_limit') AND get_setting('question_topics_limit'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('单个问题或文章话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
		}

		if (! $topic_id = $this->model('topic')->save_topic($topic_title, $this->user_id, $this->user_info['permission']['create_topic']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题已锁定或没有创建话题权限, 不能添加话题')));
		}

		$this->model('topic')->save_topic_relation($this->user_id, $topic_id, $_POST['item_id'], $_POST['type']);

		set_user_operation_last_time('edit_topic', $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'topic_id' => $topic_id,
			'topic_url' => get_js_url('topic/' . $topic_id)
		), 1, null));
	}

	public function focus_topics_list_action()
	{
		if ($topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, intval($_GET['page']) * 10 . ', 10'))
		{
			foreach ($topics_list AS $key => $val)
			{
				$topics_list[$key]['action_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $val['topic_id']));
			}
		}

		TPL::assign('topics_list', $topics_list);

		TPL::output('topic/ajax/focus_topics_list');
	}
}