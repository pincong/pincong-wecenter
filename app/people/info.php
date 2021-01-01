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
			$rule_action['actions'] = array(
				'question_discussions',
				'answer_discussions',
				'questions',
				'answers',
				'articles',
				'article_comments',
				'videos',
				'video_comments',
				'received_votes',
				'sent_votes',
			);
		}

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403
		return $rule_action;
	}

	public function setup()
	{
		if (H::GET_I('uid') < 1)
		{
			H::error_404();
		}

		$this->per_page = S::get_int('contents_per_page');
	}

	public function question_discussions_action()
	{
		TPL::assign('list', $this->model('question')->get_question_discussions_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/question_discussions_template');
	}

	public function answer_discussions_action()
	{
		TPL::assign('list', $this->model('question')->get_answer_discussions_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/answer_discussions_template');
	}

	public function questions_action()
	{
		TPL::assign('list', $this->model('question')->get_questions_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/questions_template');
	}

	public function answers_action()
	{
		TPL::assign('list', $this->model('question')->get_answers_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/answers_template');
	}

	public function articles_action()
	{
		TPL::assign('list', $this->model('article')->get_articles_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/articles_template');
	}

	public function article_comments_action()
	{
		TPL::assign('list', $this->model('article')->get_article_comments_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/article_comments_template');
	}

	public function videos_action()
	{
		TPL::assign('list', $this->model('video')->get_videos_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/videos_template');
	}

	public function video_comments_action()
	{
		TPL::assign('list', $this->model('video')->get_video_comments_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/video_comments_template');
	}

	public function sent_votes_action()
	{
		TPL::assign('list', $this->model('vote')->get_sent_votes_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/user_votes_template');
	}

	public function received_votes_action()
	{
		TPL::assign('list', $this->model('vote')->get_received_votes_by_uid(H::GET('uid'), H::GET('page'), $this->per_page));

		TPL::output('people/user_votes_template');
	}

}
