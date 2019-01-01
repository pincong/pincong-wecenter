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

class info extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'questions';
			$rule_action['actions'][] = 'answers';
			$rule_action['actions'][] = 'articles';
			$rule_action['actions'][] = 'article_comments';
			$rule_action['actions'][] = 'videos';
			$rule_action['actions'][] = 'video_comments';
			$rule_action['actions'][] = 'followers';
			$rule_action['actions'][] = 'topics';
		}

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403
		return $rule_action;
	}

	public function setup()
	{
		$_GET['uid'] = intval($_GET['uid']);
		if ($_GET['uid'] < 1)
		{
			HTTP::error_404();
		}

		$_GET['page'] = intval($_GET['page']);
		if ($_GET['page'] < 1)
		{
			$_GET['page'] = 0;
		}

		$this->per_page = intval(get_setting('contents_per_page'));
	}

	public function questions_action()
	{
		TPL::assign('list', $this->model('question')->get_questions_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/questions_template');
	}

	public function answers_action()
	{
		TPL::assign('list', $this->model('question')->get_answers_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/answers_template');
	}

	public function articles_action()
	{
		TPL::assign('list', $this->model('article')->get_articles_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/articles_template');
	}

	public function article_comments_action()
	{
		TPL::assign('list', $this->model('article')->get_article_comments_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/article_comments_template');
	}

	public function videos_action()
	{
		TPL::assign('list', $this->model('video')->get_videos_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/videos_template');
	}

	public function video_comments_action()
	{
		TPL::assign('list', $this->model('video')->get_video_comments_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/video_comments_template');
	}

	public function sent_votes_action()
	{
		TPL::assign('list', $this->model('vote')->get_sent_votes_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/sent_votes_template');
	}

	public function received_votes_action()
	{
		TPL::assign('list', $this->model('vote')->get_received_votes_by_uid($_GET['uid'], $_GET['page'], $this->per_page));

		TPL::output('people/received_votes_template');
	}

	public function followers_action()
	{
		switch ($_GET['type'])
		{
			case 'following':
				$users_list = $this->model('follow')->get_user_friends($_GET['uid'], (($_GET['page']) * $this->per_page) . ", {$this->per_page}");
			break;

			case 'followers':
				$users_list = $this->model('follow')->get_user_fans($_GET['uid'], (($_GET['page']) * $this->per_page) . ", {$this->per_page}");
			break;
		}

		if ($users_list AND $this->user_id)
		{
			foreach ($users_list as $key => $val)
			{
				$users_ids[] = $val['uid'];
			}

			if ($users_ids)
			{
				$follow_checks = $this->model('follow')->users_follow_check($this->user_id, $users_ids);

				foreach ($users_list as $key => $val)
				{
					$users_list[$key]['follow_check'] = $follow_checks[$val['uid']];
				}
			}
		}

		TPL::assign('users_list', $users_list);

		TPL::output('people/followers_template');
	}

	public function topics_action()
	{
		$topic_list = $this->model('topic')->get_focus_topic_list($_GET['uid'], (($_GET['page']) * $this->per_page) . ", {$this->per_page}");
		if ($topic_list AND $this->user_id)
		{
			$topic_ids = array();

			foreach ($topic_list as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}

			if ($topic_ids)
			{
				$topic_focus = $this->model('topic')->has_focus_topics($this->user_id, $topic_ids);

				foreach ($topic_list as $key => $val)
				{
					$topic_list[$key]['has_focus'] = $topic_focus[$val['topic_id']];
				}
			}
		}

		TPL::assign('topic_list', $topic_list);

		TPL::output('people/topics_template');
	}
}