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

class user_class extends AWS_MODEL
{

	public function delete_user_by_uid($uid)
	{
		// TODO
		/*
		$delete_tables = array(
			'favorite',
			'currency_log',
			'question_focus',
			'topic_focus',
			'users_attrib',
			'users'
		);

		$update_tables = array(
			'redirect',
			'topic_merge',
			'topic_relation'
		);

		if ($remove_user_data)
		{
			if ($user_answers = $this->query_all("SELECT answer_id FROM " . get_table('answer') . " WHERE uid = " . intval($uid)))
			{
				foreach ($user_answers AS $key => $val)
				{
					$answer_ids[] = $val['answer_id'];
				}
			}

			if ($user_articles = $this->query_all("SELECT id FROM " . get_table('article') . " WHERE uid = " . intval($uid)))
			{
				foreach ($user_articles AS $key => $val)
				{
					$this->model('article')->remove_article($val['id']);
				}
			}

			if ($user_questions = $this->query_all("SELECT question_id FROM " . get_table('question') . " WHERE published_uid = " . intval($uid)))
			{
				foreach ($user_questions AS $key => $val)
				{
					$this->model('question')->remove_question($val['question_id']);
				}
			}

			$update_tables[] = 'answer';
			$update_tables[] = 'article';

			$delete_tables[] = 'answer_discussion';
			$delete_tables[] = 'article_comment';
			$delete_tables[] = 'question_discussion';

			if ($inbox_dialog = $this->fetch_all('inbox_dialog', 'recipient_uid = ' . intval($uid) . ' OR sender_uid = ' . intval($uid)))
			{
				foreach ($inbox_dialog AS $key => $val)
				{
					$this->delete('inbox', 'dialog_id = ' . $val['id']);
					$this->delete('inbox_dialog', 'id = ' . $val['id']);
				}
			}
		}
		else
		{
			$update_tables[] = 'answer';
			$update_tables[] = 'answer_discussion';
			$update_tables[] = 'article';
			$update_tables[] = 'article_comment';
			$update_tables[] = 'question_discussion';
			$delete_tables[] = 'inbox';

			$this->update('question', array(
				'published_uid' => '-1'
			), 'published_uid = ' . intval($uid));
		}

		foreach ($delete_tables AS $key => $table)
		{
			$this->delete($table, 'uid = ' . intval($uid));
		}

		foreach ($update_tables AS $key => $table)
		{
			$this->update($table, array(
				'uid' => '-1'
			), 'uid = ' . intval($uid));
		}

		$this->model('verify')->remove_apply($uid);
		$this->model('notify')->delete_notify('sender_uid = ' . intval($uid) . ' OR recipient_uid = ' . intval($uid));

		$this->delete('question_invite', 'sender_uid = ' . intval($uid) . ' OR recipients_uid = ' . intval($uid));

		ACTION_LOG::delete_action_history('uid = ' . intval($uid));

		$this->delete('user_follow', 'fans_uid = ' . intval($uid) . ' OR friend_uid = ' . intval($uid));

		return true;
		*/
	}

}