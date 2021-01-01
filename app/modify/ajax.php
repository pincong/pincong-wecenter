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
		H::no_cache_header();
	}

	private function validate_thread($thread_type)
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		switch ($thread_type)
		{
			case 'question':
				$thread_id = H::POST('question_id');
				break;
			case 'article':
				$thread_id = H::POST('article_id');
				break;
			case 'video':
				$thread_id = H::POST('video_id');
				break;
		}

		if (!$thread_info = $this->model('post')->get_thread_info_by_id($thread_type, $thread_id))
		{
			H::ajax_error((_t('主题不存在')));
		}

		if ($thread_info['lock'])
		{
			H::ajax_error((_t('主题已锁定, 不能编辑')));
		}

		if (!can_edit_post($thread_info['uid'], $this->user_info))
		{
			H::ajax_error((_t('你没有权限编辑此主题')));
		}

		set_user_operation_last_time('publish', $this->user_id);

		return $thread_info;
	}


	private function validate_reply($thread_type)
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		switch ($thread_type)
		{
			case 'question':
				$reply_type = 'question_reply';
				break;
			case 'article':
				$reply_type = 'article_reply';
				break;
			case 'video':
				$reply_type = 'video_reply';
				break;
		}

		if (!$reply_info = $this->model('post')->get_reply_info_by_id($reply_type, H::GET('id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if (!can_edit_post($reply_info['uid'], $this->user_info))
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		$thread_id = $reply_info['parent_id'];

		if (!$tread_info = $this->model('post')->get_thread_info_by_id($thread_type, $thread_id))
		{
			H::ajax_error((_t('主题不存在')));
		}

		if ($tread_info['lock'] AND !$tread_info['redirect_id'])
		{
			H::ajax_error((_t('已经锁定的主题不能编辑')));
		}

		set_user_operation_last_time('publish', $this->user_id);

		return $reply_info;
	}


	private function get_title($thread_type)
	{
		$title = H::POST_S('title');

		$length_min = S::get_int('title_length_min');
		$length_max = S::get_int('title_length_max');
		$length = iconv_strlen($title);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_error((_t('标题字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_error((_t('标题字数不得大于 %s 字', $length_max)));
		}

		if ($thread_type == 'question' AND S::get('question_ends_with_question') == 'Y')
		{
			if (iconv_strpos($title, '？') === false AND
				iconv_strpos($title, '?') === false AND
				iconv_strpos($title, '¿') === false)
			{
				H::ajax_error((_t('请以问号提问')));
			}
		}

		return $title;
	}

	private function get_message($thread_type, $is_thread = true)
	{
		$message = H::POST_S('message');

		if ($is_thread)
		{
			$length_min = S::get_int($thread_type . '_body_length_min');
			$length_max = S::get_int($thread_type . '_body_length_max');
		}
		else
		{
			$length_min = S::get_int($thread_type . '_reply_length_min');
			$length_max = S::get_int($thread_type . '_reply_length_max');
		}

		$length = iconv_strlen($message);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_error((_t('正文字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_error((_t('正文字数不得大于 %s 字', $length_max)));
		}

		return $message;
	}



/*
+--------------------------------------------------------------------------
|   发布主题
+---------------------------------------------------------------------------
*/

	public function modify_question_action()
	{
		$thread_info = $this->validate_thread('question');

		if (H::POST('do_delete'))
		{
			$this->model('question')->clear_question(
				$thread_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('question')->modify_question(
				$thread_info['id'],
				$this->get_title('question'),
				$this->get_message('question'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_location(url_rewrite('/question/' . $thread_info['id']));
	}


	public function modify_article_action()
	{
		$thread_info = $this->validate_thread('article');

		if (H::POST('do_delete'))
		{
			$this->model('article')->clear_article(
				$thread_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('article')->modify_article(
				$thread_info['id'],
				$this->get_title('article'),
				$this->get_message('article'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_location(url_rewrite('/article/' . $thread_info['id']));
	}


	public function modify_video_action()
	{
		if (!H::POST('do_delete'))
		{
			if ($web_url = H::POST_S('web_url'))
			{
				$metadata = Services_VideoParser::parse_video_url($web_url);
				if (!$metadata)
				{
					H::ajax_error((_t('无法识别影片来源')));
				}
			}
		}

		$thread_info = $this->validate_thread('video');

		if (H::POST('do_delete'))
		{
			$this->model('video')->clear_video(
				$thread_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			if (isset($metadata))
			{
				$this->model('video')->update_video_source(
					$thread_info['id'],
					$metadata['source_type'],
					$metadata['source']
				);
			}

			$this->model('video')->modify_video(
				$thread_info['id'],
				$this->get_title('video'),
				$this->get_message('video'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_location(url_rewrite('/video/' . $thread_info['id']));

	}



/*
+--------------------------------------------------------------------------
|   发布回应
+---------------------------------------------------------------------------
*/

	public function modify_answer_action()
	{
		$reply_info = $this->validate_reply('question');

		if (H::POST('do_delete'))
		{
			$this->model('question')->clear_answer(
				$reply_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('question')->modify_answer(
				$reply_info['id'],
				$this->get_message('question', false),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}


	public function modify_article_comment_action()
	{
		$reply_info = $this->validate_reply('article');

		if (H::POST('do_delete'))
		{
			$this->model('article')->clear_article_comment(
				$reply_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('article')->modify_article_comment(
				$reply_info['id'],
				$this->get_message('article', false),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function modify_video_comment_action()
	{
		$reply_info = $this->validate_reply('video');

		if (H::POST('do_delete'))
		{
			$this->model('video')->clear_video_comment(
				$reply_info['id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else
		{
			$this->model('video')->modify_video_comment(
				$reply_info['id'],
				$this->get_message('video', false),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

}
