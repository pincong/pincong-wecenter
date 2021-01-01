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

class mention_class extends AWS_MODEL
{
	public function get_mentioned_uids($content)
	{
		$result = array();
		$max_at_users = S::get_int('max_at_users');
		if ($max_at_users <= 0)
		{
			return $result;
		}

		preg_match_all('/@([^@,:\s,]+)/i', strip_tags($content), $matches);

		if (is_array($matches[1]))
		{
			$usernames = array();

			foreach ($matches[1] as $key => $username)
			{
				if (in_array($username, $usernames))
				{
					continue;
				}
				if (!$user_info = $this->model('account')->get_user_info_by_username($username))
				{
					continue;
				}
				$result[] = $user_info['uid'];
				$usernames[] = $username;
				if (count($usernames) >= $max_at_users)
				{
					break;
				}
			}
		}

		return $result;
	}

}