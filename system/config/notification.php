<?php

$config['action_details'][notify_class::TYPE_PEOPLE_FOCUS] = array(
	'user_setting' => 1,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('有人关注了我')
);

$config['action_details'][notify_class::TYPE_NEW_ANSWER] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我关注的问题有了新的回复')
);

$config['action_details'][notify_class::TYPE_INVITE_QUESTION] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有人邀请我回复问题')
);

$config['action_details'][notify_class::TYPE_QUESTION_COMMENT] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的问题被讨论')
);

$config['action_details'][notify_class::TYPE_ANSWER_COMMENT] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的问题讨论被回复')
);

$config['action_details'][notify_class::TYPE_QUESTION_COMMENT_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有问题讨论提到我')
);

$config['action_details'][notify_class::TYPE_ANSWER_COMMENT_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有回答讨论提到我')
);

$config['action_details'][notify_class::TYPE_ANSWER_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有回答提到我')
);



$config['action_details'][notify_class::TYPE_CONTEXT] = array(
	'user_setting' => 0,
	'combine' => 0,
	'desc' => AWS_APP::lang()->_t('文字通知')
);

$config['action_details'][notify_class::TYPE_ARTICLE_NEW_COMMENT] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('我的文章被评论')
);

$config['action_details'][notify_class::TYPE_ARTICLE_COMMENT_AT_ME] = array(
	'user_setting' => 1,
	'combine' => 1,
	'desc' => AWS_APP::lang()->_t('有文章评论提到我')
);


