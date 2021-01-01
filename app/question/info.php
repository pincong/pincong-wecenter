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

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'comments',
				'discussions'
			);
		}

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403
		return $rule_action;
	}

	public function setup()
	{
		$this->parent_id = H::GET_I('parent_id');
		if ($this->parent_id < 1)
		{
			H::error_404();
		}

		$this->per_page = S::get_int('replies_per_page');
		if (!$this->per_page)
		{
			$this->per_page = 100;
		}
	}

	public function comments_action()
	{
		// 判断是否已合并
		if ($redirect_posts = $this->model('post')->get_redirect_threads('question', $this->parent_id))
		{
			foreach ($redirect_posts AS $key => $val)
			{
				$post_ids[] = $val['id'];
			}
		}
		$post_ids[] = $this->parent_id;

		$discussions = $this->model('question')->get_question_discussions($post_ids, H::GET('page'), $this->per_page);

		TPL::assign('discussions', $discussions);

		TPL::output("question/comments_template");
	}

	public function discussions_action()
	{
		$discussions = $this->model('question')->get_answer_discussions($this->parent_id, H::GET('page'), $this->per_page);

		TPL::assign('discussions', $discussions);

		TPL::output("question/discussions_template");
	}

}