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
	public function setup()
	{
		HTTP::no_cache_header();
	}

	private function validate_title_length($type, &$length)
	{
		$length_min = intval(S::get('title_length_min'));
		$length_max = intval(S::get('title_length_max'));
		$length = cjk_strlen($_POST['title']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得大于 %s 字', $length_max)));
		}
	}

	private function validate_body_length($type)
	{
		$length_min = intval(S::get($type . '_body_length_min'));
		$length_max = intval(S::get($type . '_body_length_max'));
		$length = cjk_strlen($_POST['message']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正文字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正文字数不得大于 %s 字', $length_max)));
		}
	}

	private function validate_reply_length($type)
	{
		$length_min = intval(S::get($type . '_reply_length_min'));
		$length_max = intval(S::get($type . '_reply_length_max'));
		$length = cjk_strlen($_POST['message']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复字数不得大于 %s 字', $length_max)));
		}
	}

	private function do_validate()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}
	}

	private function validate_thread($type)
	{
		$this->do_validate();

		$_POST['title'] = trim($_POST['title']);
		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入标题')));
		}
		$this->validate_title_length($type, $title_length);

		if ($type == 'question' AND S::get('question_ends_with_question') == 'Y')
		{
			$question_mark = cjk_substr($_POST['title'], $title_length - 1, 1);
			if ($question_mark != '？' AND $question_mark != '?' AND $question_mark != '¿')
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请以问号提问')));
			}
		}

		$_POST['message'] = trim($_POST['message']);
		$this->validate_body_length($type);
	}


	private function validate_reply($parent_type)
	{
		$this->do_validate();

		$_POST['message'] = trim($_POST['message']);
		if (!$_POST['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}
		$this->validate_reply_length($parent_type);
	}



/*
+--------------------------------------------------------------------------
|   发布主题
+---------------------------------------------------------------------------
*/

	public function modify_question_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$question_info = $this->model('content')->get_thread_info_by_id('question', $_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题已锁定, 不能编辑')));
		}

		if (!can_edit_post($question_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个问题')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_thread('question');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('question')->clear_question(
				$question_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('question')->modify_question(
				$question_info['id'],
				$_POST['title'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/question/' . $question_info['id'])
		), 1, null));

	}


	public function modify_article_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$article_info = $this->model('content')->get_thread_info_by_id('article', $_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
		}

		if ($article_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章已锁定, 不能编辑')));
		}

		if (!can_edit_post($article_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个文章')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_thread('article');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('article')->clear_article(
				$article_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('article')->modify_article(
				$article_info['id'],
				$_POST['title'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/article/' . $article_info['id'])
		), 1, null));
	}


	public function modify_video_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$video_info = $this->model('content')->get_thread_info_by_id('video', $_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('影片不存在')));
		}

		if ($video_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('影片已锁定, 不能编辑')));
		}

		if (!can_edit_post($video_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个影片')));
		}

		if (!$_POST['do_delete'])
		{
			if ($web_url = trim($_POST['web_url']))
			{
				$metadata = Services_VideoParser::parse_video_url($web_url);
				if (!$metadata)
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('无法识别影片来源')));
				}
			}

			$this->validate_thread('video');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('video')->clear_video(
				$video_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			if ($metadata)
			{
				$this->model('video')->update_video_source(
					$video_info['id'],
					$metadata['source_type'],
					$metadata['source']
				);
			}

			$this->model('video')->modify_video(
				$video_info['id'],
				$_POST['title'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/video/' . $video_info['id'])
		), 1, null));

	}
    
    public function modify_voting_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$voting_info = $this->model('content')->get_thread_info_by_id('voting', $_POST['voting_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
		}

		if ($voting_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章已锁定, 不能编辑')));
		}

		if (!can_edit_post($voting_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个文章')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_thread('voting');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('voting')->clear_voting(
				$voting_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('voting')->modify_voting(
				$voting_info['id'],
				$_POST['title'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/voting/' . $voting_info['id'])
		), 1, null));
	}



/*
+--------------------------------------------------------------------------
|   发布回应
+---------------------------------------------------------------------------
*/

	public function modify_answer_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$answer_info = $this->model('content')->get_reply_info_by_id('answer', $_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if (!can_edit_post($answer_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$question_info = $this->model('content')->get_thread_info_by_id('question', $answer_info['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'] AND !$question_info['redirect_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的问题不能编辑')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_reply('question');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('question')->clear_answer(
				$answer_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('question')->modify_answer(
				$answer_info['id'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		// 删除回复邀请, 如果有
		$this->model('invite')->answer_question_invite($answer_info['question_id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	public function modify_article_comment_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$comment_info = $this->model('content')->get_reply_info_by_id('article_comment', $_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if (!can_edit_post($comment_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$article_info = $this->model('content')->get_thread_info_by_id('article', $comment_info['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
		}

		if ($article_info['lock'] AND !$article_info['redirect_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的文章不能编辑')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_reply('article');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('article')->clear_article_comment(
				$comment_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('article')->modify_article_comment(
				$comment_info['id'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function modify_video_comment_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$comment_info = $this->model('content')->get_reply_info_by_id('video_comment', $_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if (!can_edit_post($comment_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$video_info = $this->model('content')->get_thread_info_by_id('video', $comment_info['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('影片不存在')));
		}

		if ($video_info['lock'] AND !$video_info['redirect_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的影片不能编辑')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_reply('video');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('video')->clear_video_comment(
				$comment_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('video')->modify_video_comment(
				$comment_info['id'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
    
    public function modify_voting_comment_action()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$comment_info = $this->model('content')->get_reply_info_by_id('voting_comment', $_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if (!can_edit_post($comment_info['uid'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$voting_info = $this->model('content')->get_thread_info_by_id('voting', $comment_info['voting_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
		}

		if ($voting_info['lock'] AND !$voting_info['redirect_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的文章不能编辑')));
		}

		if (!$_POST['do_delete'])
		{
			$this->validate_reply('voting');
		}

		set_user_operation_last_time('publish', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('voting')->clear_voting_comment(
				$comment_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('voting')->modify_voting_comment(
				$comment_info['id'],
				$_POST['message'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}
