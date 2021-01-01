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

	private function validate_thread($thread_type, $title)
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('publish')->check_user_permission($thread_type, $this->user_info))
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		if (!check_repeat_submission($this->user_id, $title))
		{
			H::ajax_error((_t('请不要重复提交')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_' . $thread_type))
		{
			H::ajax_error((_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::ajax_error((_t('今日发帖数量已经达到上限')));
		}
	}


	private function validate_reply($thread_type, $message, &$pay)
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!check_repeat_submission($this->user_id, $message))
		{
			H::ajax_error((_t('请不要重复提交')));
		}

		switch ($thread_type)
		{
			case 'question':
				$thread_id = H::POST('question_id');
				$reply_type = 'question_reply';
				break;
			case 'article':
				$thread_id = H::POST('article_id');
				$reply_type = 'article_reply';
				break;
			case 'video':
				$thread_id = H::POST('video_id');
				$reply_type = 'video_reply';
				break;
		}

		if (!$this->model('publish')->check_user_permission($reply_type, $this->user_info))
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		switch ($thread_type)
		{
			case 'question':
				if (!$this->model('ratelimit')->check_answer($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
				{
					H::ajax_error((_t('你今天的回复已经达到上限')));
				}
				break;
			case 'article':
				if (!$this->model('ratelimit')->check_article_comment($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
				{
					H::ajax_error((_t('你今天的文章评论已经达到上限')));
				}
				break;
			case 'video':
				if (!$this->model('ratelimit')->check_video_comment($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
				{
					H::ajax_error((_t('你今天的影片评论已经达到上限')));
				}
				break;
		}

		if (!$thread_info = $this->model('post')->get_thread_info_by_id($thread_type, $thread_id))
		{
			H::ajax_error((_t('主题不存在')));
		}

		if ($thread_info['lock'])
		{
			H::ajax_error((_t('已经锁定的主题不能回复')));
		}

		if (!$thread_info['title'])
		{
			H::ajax_error((_t('已经删除的主题不能回复')));
		}

		$days = intval($this->user_info['permission']['unallowed_necropost_days']);
		if ($days > 0)
		{
			$seconds = $days * 24 * 3600;
			$time_before = real_time() - $seconds;

			if (intval($thread_info['update_time']) < $time_before)
			{
				H::ajax_error((_t('你的声望还不够, 不能回应已失去时效性的主题')));
			}
		}

		if (!$this->model('category')->check_user_permission_reply($thread_info['category_id'], $this->user_info))
		{
			H::ajax_error((_t('你的声望还不够, 不能在这个分类发言')));
		}

		$pay = true;
		$replied = $this->model('post')->has_user_relpied_to_thread($thread_type, $thread_info['id'], $this->user_id, true);
		if ((S::get('reply_pay_only_once') == 'Y') AND $replied)
		{
			$pay = false;
		}

		if ($pay AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_' . $thread_type))
		{
			H::ajax_error((_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		if ($thread_type == 'question')
		{
			// 判断是否已回复过问题
			if (S::get('answer_unique') == 'Y' AND $replied)
			{
				if ($replied == 2)
				{
					H::ajax_error((_t('你已经使用延迟显示功能回复过该问题')));
				}
				H::ajax_error((_t('一个问题只能回复一次，你可以编辑回复过的回复')));
			}

			// 判断是否是问题发起者
			if (S::get('answer_self_question') == 'N' AND $thread_info['uid'] == $this->user_id)
			{
				H::ajax_error((_t('不能回复自己发布的问题，你可以修改问题内容')));
			}
		}

		return $thread_info;
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

	private function get_topics()
	{
		$topics = H::POSTS_S('topics');

		$topics_limit_min = S::get_int('topics_limit_min');
		$topics_limit_max = S::get_int('topics_limit_max');

		$num_topics = 0;
		if ($topics)
		{
			$topics = array_unique($topics);
			$num_topics = count($topics);
		}

		if ($topics_limit_min AND $num_topics < $topics_limit_min)
		{
			H::ajax_error((_t('话题数量最少 %s 个, 请添加话题', $topics_limit_min)));
		}
		if ($topics_limit_max AND $num_topics > $topics_limit_max)
		{
			H::ajax_error((_t('话题数量最多 %s 个, 请调整话题数量', $topics_limit_max)));
		}

		if (!$num_topics)
		{
			return null;
		}

		$topic_title_limit = S::get_int('topic_title_limit');
		foreach ($topics AS $key => $topic_title)
		{
			if (!$topic_title)
			{
				H::ajax_error((_t('请填写话题标题')));
				break;
			}

			$topic_title_length = strlen($topic_title);
			if ($topic_title_length > 100)
			{
				H::ajax_error((_t('话题标题字数超出限制')));
				break;
			}

			$topic_exists = !!$this->model('topic')->get_topic_id_by_title($topic_title);
			if (!$topic_exists)
			{
				if (!$this->user_info['permission']['create_topic'])
				{
					H::ajax_error((_t('你的声望还不够, 不能创建新话题「%s」, 请选择现有话题', $topic_title)));
					break;
				}

				if ($topic_title_limit AND $topic_title_length > $topic_title_limit)
				{
					H::ajax_error((_t('话题标题字数超出限制')));
					break;
				}
			}
		}

		return $topics;
	}

	private function get_category_id()
	{
		if (S::get('category_enable') == 'N')
		{
			$category_id = 1;
		}
		else
		{
			$category_id = H::POST_I('category_id');
		}

		if (!$category_id)
		{
			H::ajax_error((_t('请选择分类')));
		}

		if (!$this->model('category')->category_exists($category_id))
		{
			H::ajax_error((_t('分类不存在')));
		}

		if (!$this->model('category')->check_user_permission($category_id, $this->user_info))
		{
			H::ajax_error((_t('你的声望还不够, 不能在这个分类发言')));
		}

		return $category_id;
	}

	private function get_anonymous_uid($is_thread = true)
	{
		if ($is_thread)
		{
			if (!$this->user_info['permission']['post_anonymously'])
			{
				H::ajax_error((_t('你的声望还不够, 不能匿名')));
			}
		}
		else
		{
			if (!$this->user_info['permission']['reply_anonymously'])
			{
				H::ajax_error((_t('你的声望还不够, 不能匿名')));
			}
		}

		if (!$anonymous_uid = $this->model('anonymous')->get_anonymous_uid($this->user_info))
		{
			H::ajax_error((_t('本站未开启匿名功能')));
		}

		if (!$this->model('anonymous')->check_rate_limit(null, $anonymous_uid))
		{
			H::ajax_error((_t('今日匿名额度已经用完')));
		}

		if (!$this->model('anonymous')->check_spam($anonymous_uid))
		{
			H::ajax_error((_t('检测到滥用行为, 匿名功能暂时关闭')));
		}

		return $anonymous_uid;
	}


	private function get_delay_time()
	{
		$later = H::POST_I('later');

		if ($later)
		{
			if (!$this->user_info['permission']['post_later'])
			{
				H::ajax_error((_t('你的声望还不够, 不能延迟发布')));
			}

			if ($later < 10)
			{
				H::ajax_error((_t('延迟时间不能小于 10 分钟')));
			}

			if ($later > 1440)
			{
				H::ajax_error((_t('延迟时间不能大于 1440 分钟')));
			}
		}

		return $later;
	}


/*
+--------------------------------------------------------------------------
|   发布主题
+---------------------------------------------------------------------------
*/

	public function publish_question_action()
	{
		$title = $this->get_title('question');
		$this->validate_thread('question', $title);

		$delay = $this->get_delay_time();
		$publish_uid = $this->user_id;
		if (H::POST('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid();
		}

		$thread_id = $this->model('publish')->publish_question(array(
			'title' => $title,
			'message' => $this->get_message('question'),
			'category_id' => $this->get_category_id(),
			'uid' => $publish_uid,
			'topics' => $this->get_topics(),
			'follow' => !H::POST('anonymous'),
			'ask_user_id' => H::POST('ask_user_id'),
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, $delay);

		set_repeat_submission_digest($this->user_id, $title);
		set_user_operation_last_time('publish', $this->user_id);

		if ($delay)
		{
			// 延迟显示
			H::ajax_location(url_rewrite('/publish/delay/'));
		}

		H::ajax_location(url_rewrite('/question/' . $thread_id));
	}


	public function publish_article_action()
	{
		$title = $this->get_title('article');
		$this->validate_thread('article', $title);

		$delay = $this->get_delay_time();
		$publish_uid = $this->user_id;
		if (H::POST('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid();
		}

		$thread_id = $this->model('publish')->publish_article(array(
			'title' => $title,
			'message' => $this->get_message('article'),
			'category_id' => $this->get_category_id(),
			'uid' => $publish_uid,
			'topics' => $this->get_topics(),
			'follow' => !H::POST('anonymous'),
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, $delay);

		set_repeat_submission_digest($this->user_id, $title);
		set_user_operation_last_time('publish', $this->user_id);

		if ($delay)
		{
			// 延迟显示
			H::ajax_location(url_rewrite('/publish/delay/'));
		}

		H::ajax_location(url_rewrite('/article/' . $thread_id));
	}


	public function publish_video_action()
	{
		if (!$web_url = H::POST_S('web_url'))
		{
			H::ajax_error((_t('请输入影片来源')));
		}

		$metadata = Services_VideoParser::parse_video_url($web_url);
		if (!$metadata)
		{
			H::ajax_error((_t('无法识别影片来源')));
		}

		$title = $this->get_title('video');
		$this->validate_thread('video', $title);

		$delay = $this->get_delay_time();
		$publish_uid = $this->user_id;
		if (H::POST('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid();
		}

		$thread_id = $this->model('publish')->publish_video(array(
			'title' => $title,
			'message' => $this->get_message('video'),
			'category_id' => $this->get_category_id(),
			'uid' => $publish_uid,
			'topics' => $this->get_topics(),
			'follow' => !H::POST('anonymous'),
			'source_type' => $metadata['source_type'],
			'source' => $metadata['source'],
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, $delay);

		set_repeat_submission_digest($this->user_id, $title);
		set_user_operation_last_time('publish', $this->user_id);

		if ($delay)
		{
			// 延迟显示
			H::ajax_location(url_rewrite('/publish/delay/'));
		}

		H::ajax_location(url_rewrite('/video/' . $thread_id));
	}



/*
+--------------------------------------------------------------------------
|   发布回应
+---------------------------------------------------------------------------
*/

	public function publish_answer_action()
	{
		$message = $this->get_message('question', false);
		$thread_info = $this->validate_reply('question', $message, $pay);

		$delay = $this->get_delay_time();
		$publish_uid = $this->user_id;
		if (H::POST('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid(false);
		}

		$reply_id = $this->model('publish')->publish_answer(array(
			'parent_id' => $thread_info['id'],
			'message' => $message,
			'uid' => $publish_uid,
			'follow' => (H::POST('follow') AND !H::POST('anonymous') AND ($this->user_info['permission']['follow_thread'] OR $thread_info['uid'] == $this->user_id)),
			'permission_affect_currency' => $this->user_info['permission']['affect_currency'],
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, $delay, $pay);

		set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		if ($delay)
		{
			// 延迟显示
			H::ajax_location(url_rewrite('/publish/delay/'));
		}

		$reply_info = $this->model('question')->get_answer_by_id($reply_id);
		TPL::assign('answer_info', $reply_info);
		H::ajax_response(array(
			'ajax_html' => TPL::process('question/ajax_reply')
		));
	}


	public function publish_article_comment_action()
	{
		$message = $this->get_message('article', false);
		$thread_info = $this->validate_reply('article', $message, $pay);

		$delay = $this->get_delay_time();
		$publish_uid = $this->user_id;
		if (H::POST('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid(false);
		}

		$reply_id = $this->model('publish')->publish_article_comment(array(
			'parent_id' => $thread_info['id'],
			'message' => $message,
			'uid' => $publish_uid,
			'follow' => (H::POST('follow') AND !H::POST('anonymous') AND ($this->user_info['permission']['follow_thread'] OR $thread_info['uid'] == $this->user_id)),
			'at_uid' => H::POST('at_uid'),
			'permission_affect_currency' => $this->user_info['permission']['affect_currency'],
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, $delay, $pay);

		set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		if ($delay)
		{
			// 延迟显示
			H::ajax_location(url_rewrite('/publish/delay/'));
		}

		$reply_info = $this->model('article')->get_article_comment_by_id($reply_id);
		TPL::assign('comment_info', $reply_info);
		H::ajax_response(array(
			'ajax_html' => TPL::process('article/ajax_reply')
		));
	}


	public function publish_video_comment_action()
	{
		$message = $this->get_message('video', false);
		$thread_info = $this->validate_reply('video', $message, $pay);

		$delay = $this->get_delay_time();
		$publish_uid = $this->user_id;
		if (H::POST('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid(false);
		}

		$reply_id = $this->model('publish')->publish_video_comment(array(
			'parent_id' => $thread_info['id'],
			'message' => $message,
			'uid' => $publish_uid,
			'follow' => (H::POST('follow') AND !H::POST('anonymous') AND ($this->user_info['permission']['follow_thread'] OR $thread_info['uid'] == $this->user_id)),
			'at_uid' => H::POST('at_uid'),
			'permission_affect_currency' => $this->user_info['permission']['affect_currency'],
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, $delay, $pay);

		set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		if ($delay)
		{
			// 延迟显示
			H::ajax_location(url_rewrite('/publish/delay/'));
		}

		$reply_info = $this->model('video')->get_video_comment_by_id($reply_id);
		TPL::assign('comment_info', $reply_info);
		H::ajax_response(array(
			'ajax_html' => TPL::process('video/ajax_reply')
		));
	}


}
