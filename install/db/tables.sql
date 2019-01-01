-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

--
CREATE TABLE IF NOT EXISTS `aws_answer` (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '回答id',
  `question_id` int(11) DEFAULT '0' COMMENT '问题id',
  `answer_content` text COMMENT '回答内容',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `agree_count` int(11) DEFAULT '0' COMMENT '支持人数',
  `uid` int(11) DEFAULT '0' COMMENT '回答问题用户ID',
  `comment_count` int(11) DEFAULT '0' COMMENT '评论总数',
  PRIMARY KEY (`answer_id`),
  KEY `question_id` (`question_id`),
  KEY `agree_count` (`agree_count`),
  KEY `add_time` (`add_time`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='回答';

--


--
CREATE TABLE IF NOT EXISTS `aws_answer_discussion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `answer_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `answer_id` (`answer_id`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_article` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT '0',
  `title` varchar(240) DEFAULT NULL,
  `message` text,
  `comments` int(10) DEFAULT '0',
  `views` int(10) DEFAULT '0',
  `add_time` int(10) DEFAULT '0',
  `lock` int(1) DEFAULT '0',
  `agree_count` int(10) DEFAULT '0',
  `title_fulltext` text,
  `category_id` int(10) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `update_time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `comments` (`comments`),
  KEY `views` (`views`),
  KEY `add_time` (`add_time`),
  KEY `lock` (`lock`),
  KEY `agree_count` (`agree_count`),
  KEY `category_id` (`category_id`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  KEY `update_time` (`update_time`),
  FULLTEXT KEY `title_fulltext` (`title_fulltext`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_article_comment` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT '0',
  `article_id` int(10) DEFAULT '0',
  `message` text,
  `add_time` int(10) DEFAULT '0',
  `at_uid` int(10) DEFAULT NULL,
  `agree_count` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `article_id` (`article_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `description` varchar(240) DEFAULT NULL,
  `group_id` int(11) DEFAULT '0',
  `sort` smallint(6) DEFAULT '0',
  `skip` TINYINT(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `item_id` int(11) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  `type` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `item_id` (`item_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_feature` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL COMMENT '专题标题',
  `link` text COMMENT '自定义链接',
  `enabled` tinyint(1) DEFAULT '0',
  `sort` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_inbox` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0' COMMENT '发送者 ID',
  `dialog_id` int(11) DEFAULT '0' COMMENT '对话id',
  `message` text COMMENT '内容',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `sender_remove` tinyint(1) DEFAULT '0',
  `recipient_remove` tinyint(1) DEFAULT '0',
  `receipt` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dialog_id` (`dialog_id`),
  KEY `uid` (`uid`),
  KEY `add_time` (`add_time`),
  KEY `sender_remove` (`sender_remove`),
  KEY `recipient_remove` (`recipient_remove`),
  KEY `sender_receipt` (`receipt`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_inbox_dialog` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '对话ID',
  `sender_uid` int(11) DEFAULT NULL COMMENT '发送者UID',
  `sender_unread` int(11) DEFAULT NULL COMMENT '发送者未读',
  `recipient_uid` int(11) DEFAULT NULL COMMENT '接收者UID',
  `recipient_unread` int(11) DEFAULT NULL COMMENT '接收者未读',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) DEFAULT '0' COMMENT '最后更新时间',
  `sender_count` int(11) DEFAULT NULL COMMENT '发送者显示对话条数',
  `recipient_count` int(11) DEFAULT NULL COMMENT '接收者显示对话条数',
  PRIMARY KEY (`id`),
  KEY `recipient_uid` (`recipient_uid`),
  KEY `sender_uid` (`sender_uid`),
  KEY `update_time` (`update_time`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_currency_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `action` varchar(64) DEFAULT NULL,
  `currency` int(11) DEFAULT NULL,
  `note` varchar(128) DEFAULT NULL,
  `balance` int(11) DEFAULT '0',
  `item_id` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `action` (`action`),
  KEY `time` (`time`),
  KEY `currency` (`currency`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_nav_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `description` varchar(240) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `type_id` int(11) DEFAULT '0',
  `link` varchar(240) DEFAULT NULL COMMENT '链接',
  `icon` varchar(240) DEFAULT NULL COMMENT '图标',
  `sort` smallint(6) DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`link`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `sender_uid` int(11) DEFAULT NULL COMMENT '发送者ID',
  `recipient_uid` int(11) DEFAULT '0' COMMENT '接收者ID',
  `action_type` int(4) DEFAULT NULL COMMENT '操作类型',
  `model_type` smallint(11) DEFAULT '0',
  `source_id` varchar(16) DEFAULT '0' COMMENT '关联 ID',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `read_flag` tinyint(1) DEFAULT '0' COMMENT '阅读状态',
  PRIMARY KEY (`notification_id`),
  KEY `recipient_read_flag` (`recipient_uid`,`read_flag`),
  KEY `sender_uid` (`sender_uid`),
  KEY `model_type` (`model_type`),
  KEY `source_id` (`source_id`),
  KEY `action_type` (`action_type`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='系统通知';

--


--
CREATE TABLE IF NOT EXISTS `aws_notification_data` (
  `notification_id` int(11) NOT NULL,
  `data` text,
  `add_time` int(10) DEFAULT '0',
  PRIMARY KEY (`notification_id`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='系统通知数据表';

--


--
CREATE TABLE IF NOT EXISTS `aws_pages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `url_token` varchar(32) DEFAULT NULL,
  `title` varchar(240) DEFAULT NULL,
  `keywords` varchar(240) DEFAULT NULL,
  `description` varchar(240) DEFAULT NULL,
  `contents` text,
  `enabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_token` (`url_token`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_posts_index` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `post_id` int(10) DEFAULT '0' ,
  `post_type` varchar(16) DEFAULT NULL,
  `add_time` int(10) DEFAULT '0',
  `update_time` int(10) DEFAULT '0',
  `category_id` int(10) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `view_count` int(10) DEFAULT '0',
  `uid` int(10) DEFAULT '0',
  `lock` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `agree_count` int(10) DEFAULT '0',
  `answer_count` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `post_type` (`post_type`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`),
  KEY `category_id` (`category_id`),
  KEY `recommend` (`recommend`),
  KEY `uid` (`uid`),
  KEY `lock` (`lock`),
  KEY `sort` (`sort`),
  KEY `agree_count` (`agree_count`),
  KEY `answer_count` (`answer_count`),
  KEY `view_count` (`view_count`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_question` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_content` varchar(240) DEFAULT NULL COMMENT '问题标题',
  `question_detail` text COMMENT '问题详细说明',
  `add_time` int(11) DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0' COMMENT '发布用户UID',
  `answer_count` int(11) DEFAULT '0' COMMENT '回答计数',
  `view_count` int(11) DEFAULT '0' COMMENT '浏览次数',
  `focus_count` int(11) DEFAULT '0' COMMENT '关注数',
  `comment_count` int(11) DEFAULT '0' COMMENT '评论数',
  `category_id` int(11) DEFAULT '0' COMMENT '分类 ID',
  `agree_count` int(11) DEFAULT '0' COMMENT '回复赞同数总和',
  `best_answer` int(11) DEFAULT '0' COMMENT '最佳回复 ID',
  `last_answer` int(11) DEFAULT '0' COMMENT '最后回答 ID',
  `lock` tinyint(1) DEFAULT '0' COMMENT '是否锁定',
  `question_content_fulltext` text,
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  PRIMARY KEY (`question_id`),
  KEY `category_id` (`category_id`),
  KEY `update_time` (`update_time`),
  KEY `add_time` (`add_time`),
  KEY `uid` (`uid`),
  KEY `answer_count` (`answer_count`),
  KEY `agree_count` (`agree_count`),
  KEY `question_content` (`question_content`),
  KEY `lock` (`lock`),
  KEY `best_answer` (`best_answer`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  FULLTEXT KEY `question_content_fulltext` (`question_content_fulltext`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='问题列表';

--


--
CREATE TABLE IF NOT EXISTS `aws_question_discussion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_question_focus` (
  `focus_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `question_id` int(11) DEFAULT '0' COMMENT '话题ID',
  `uid` int(11) DEFAULT '0' COMMENT '用户UID',
  `add_time` int(10) DEFAULT '0',
  PRIMARY KEY (`focus_id`),
  KEY `question_id` (`question_id`),
  KEY `question_uid` (`question_id`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='问题关注表';

--


--
CREATE TABLE IF NOT EXISTS `aws_question_invite` (
  `question_invite_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `question_id` int(11) DEFAULT '0' COMMENT '问题ID',
  `sender_uid` int(11) DEFAULT '0',
  `recipients_uid` int(11) DEFAULT '0',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `available_time` int(10) DEFAULT '0' COMMENT '生效时间',
  PRIMARY KEY (`question_invite_id`),
  KEY `question_id` (`question_id`),
  KEY `sender_uid` (`sender_uid`),
  KEY `recipients_uid` (`recipients_uid`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='邀请问答';

--


--
CREATE TABLE IF NOT EXISTS `aws_redirect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT '0',
  `target_id` int(11) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_related_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT '0' COMMENT '话题 ID',
  `related_id` int(11) DEFAULT '0' COMMENT '相关话题 ID',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `related_id` (`related_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_search_cache` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL,
  `data` mediumtext NOT NULL,
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_sessions` (
  `id` varchar(32) NOT NULL,
  `modified` int(10) NOT NULL,
  `data` text NOT NULL,
  `lifetime` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified` (`modified`),
  KEY `lifetime` (`lifetime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_system_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `varname` varchar(240) NOT NULL COMMENT '字段名',
  `value` text COMMENT '变量值',
  PRIMARY KEY (`id`),
  UNIQUE KEY `varname` (`varname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='系统设置';

--


--
CREATE TABLE IF NOT EXISTS `aws_topic` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '话题id',
  `topic_title` varchar(64) DEFAULT NULL COMMENT '话题标题',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `discuss_count` int(11) DEFAULT '0' COMMENT '讨论计数',
  `topic_description` text COMMENT '话题描述',
  `topic_pic` varchar(240) DEFAULT NULL COMMENT '话题图片',
  `topic_lock` tinyint(2) DEFAULT '0' COMMENT '话题是否锁定 1 锁定 0 未锁定',
  `focus_count` int(11) DEFAULT '0' COMMENT '关注计数',
  `user_related` tinyint(1) DEFAULT '0' COMMENT '是否被用户关联',
  `url_token` varchar(32) DEFAULT NULL,
  `merged_id` int(11) DEFAULT '0',
  `seo_title` varchar(240) DEFAULT NULL,
  `parent_id` int(10) DEFAULT '0',
  `is_parent` tinyint(1) DEFAULT '0',
  `discuss_count_last_week` int(10) DEFAULT '0',
  `discuss_count_last_month` int(10) DEFAULT '0',
  `discuss_count_update` int(10) DEFAULT '0',
  PRIMARY KEY (`topic_id`),
  UNIQUE KEY `topic_title` (`topic_title`),
  KEY `url_token` (`url_token`),
  KEY `merged_id` (`merged_id`),
  KEY `discuss_count` (`discuss_count`),
  KEY `add_time` (`add_time`),
  KEY `user_related` (`user_related`),
  KEY `focus_count` (`focus_count`),
  KEY `topic_lock` (`topic_lock`),
  KEY `parent_id` (`parent_id`),
  KEY `is_parent` (`is_parent`),
  KEY `discuss_count_last_week` (`discuss_count_last_week`),
  KEY `discuss_count_last_month` (`discuss_count_last_month`),
  KEY `discuss_count_update` (`discuss_count_update`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='话题';

--


--
CREATE TABLE IF NOT EXISTS `aws_topic_focus` (
  `focus_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `topic_id` int(11) DEFAULT '0' COMMENT '话题ID',
  `uid` int(11) DEFAULT '0' COMMENT '用户UID',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`focus_id`),
  KEY `uid` (`uid`),
  KEY `topic_id` (`topic_id`),
  KEY `topic_uid` (`topic_id`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='话题关注表';

--


--
CREATE TABLE IF NOT EXISTS `aws_topic_merge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_id` int(11) DEFAULT '0',
  `target_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`),
  KEY `target_id` (`target_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_topic_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增 ID',
  `topic_id` int(11) DEFAULT '0' COMMENT '话题id',
  `item_id` int(11) DEFAULT '0',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `uid` int(11) DEFAULT '0' COMMENT '用户ID',
  `type` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户的 UID',
  `user_name` varchar(240) DEFAULT NULL COMMENT '用户名',
  `password` varchar(60) DEFAULT NULL COMMENT '用户密码',
  `salt` varchar(16) DEFAULT NULL COMMENT '用户附加混淆码',
  `avatar_file` varchar(128) DEFAULT NULL COMMENT '头像文件',
  `sex` tinyint(1) DEFAULT '3' COMMENT '性别',
  `reg_time` int(10) DEFAULT '0' COMMENT '注册时间',
  `last_login` int(10) DEFAULT '0' COMMENT '最后登录时间',
  `notification_unread` int(11) DEFAULT '0' COMMENT '未读系统通知',
  `inbox_unread` int(11) DEFAULT '0' COMMENT '未读短信息',
  `inbox_recv` tinyint(1) DEFAULT '0' COMMENT '0-所有人可以发给我,1-我关注的人',
  `fans_count` int(10) DEFAULT '0' COMMENT '粉丝数',
  `friend_count` int(10) DEFAULT '0' COMMENT '观众数',
  `invite_count` int(10) DEFAULT '0' COMMENT '邀请我回答数量',
  `topic_focus_count` int(10) DEFAULT '0' COMMENT '关注话题数量',
  `group_id` int(10) DEFAULT '4' COMMENT '用户组',
  `forbidden` tinyint(1) DEFAULT '0' COMMENT '是否禁止用户',
  `agree_count` int(10) DEFAULT '0' COMMENT '赞同数量',
  `views_count` int(10) DEFAULT '0' COMMENT '个人主页查看数量',
  `reputation` int(10) DEFAULT '0' COMMENT '威望',
  `currency` int(10) DEFAULT '0',
  `user_update_time` int(10) DEFAULT '0',
  `verified` varchar(32) DEFAULT NULL,
  `default_timezone` varchar(32) DEFAULT NULL,
  `recent_topics` text,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_name` (`user_name`) USING BTREE,
  KEY `reputation` (`reputation`),
  KEY `group_id` (`group_id`),
  KEY `agree_count` (`agree_count`),
  KEY `forbidden` (`forbidden`),
  KEY `currency` (`currency`),
  KEY `verified` (`verified`),
  KEY `last_login` (`last_login`),
  KEY `user_update_time` (`user_update_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_users_attrib` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` int(11) DEFAULT '0' COMMENT '用户UID',
  `signature` varchar(240) DEFAULT NULL COMMENT '个人签名',
  `extra_data` TEXT COMMENT '不是特别重要的额外数据',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='用户附加属性表';

--


--
CREATE TABLE IF NOT EXISTS `aws_users_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT '0' COMMENT '0-系统组 1-会员组',
  `custom` tinyint(1) DEFAULT '0' COMMENT '是否自定义',
  `group_name` text,
  `reputation_lower` int(11) DEFAULT '0',
  `reputation_higer` int(11) DEFAULT '0',
  `reputation_factor` float DEFAULT '0' COMMENT '威望系数',
  `permission` text COMMENT '权限设置',
  PRIMARY KEY (`group_id`),
  KEY `type` (`type`),
  KEY `custom` (`custom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='用户组';

--


--
CREATE TABLE IF NOT EXISTS `aws_users_notification_setting` (
  `notice_setting_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` int(11) DEFAULT '0',
  `data` text COMMENT '设置数据',
  PRIMARY KEY (`notice_setting_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='通知设定';

--


--
CREATE TABLE IF NOT EXISTS `aws_user_follow` (
  `follow_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `fans_uid` int(11) DEFAULT '0' COMMENT '关注人的UID',
  `friend_uid` int(11) DEFAULT '0' COMMENT '被关注人的uid',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`follow_id`),
  KEY `fans_uid` (`fans_uid`),
  KEY `friend_uid` (`friend_uid`),
  KEY `user_follow` (`fans_uid`,`friend_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='用户关注表';

--


--
CREATE TABLE IF NOT EXISTS `aws_scheduled_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  `uid` int(11) DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  `data` text,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `uid` (`uid`),
  KEY `parent_id` (`parent_id`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_content_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `item_id` int(11) DEFAULT '0',
  `child_type` varchar(32) DEFAULT NULL,
  `child_id` int(11) DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `item_id` (`item_id`),
  KEY `child_id` (`child_id`),
  KEY `item_type` (`item_type`),
  KEY `child_type` (`child_type`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_video` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT '0',
  `title` varchar(240) DEFAULT NULL,
  `message` text,
  `source_type` varchar(32) DEFAULT NULL,
  `source` text,
  `duration` int(10) DEFAULT '0' COMMENT '时长 秒',
  `comment_count` int(10) DEFAULT '0',
  `danmaku_count` int(10) DEFAULT '0',
  `view_count` int(10) DEFAULT '0',
  `agree_count` int(10) DEFAULT '0',
  `lock` int(1) DEFAULT '0',
  `title_fulltext` text,
  `category_id` int(10) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `add_time` int(10) DEFAULT '0',
  `update_time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `duration` (`duration`),
  KEY `comment_count` (`comment_count`),
  KEY `danmaku_count` (`danmaku_count`),
  KEY `view_count` (`view_count`),
  KEY `agree_count` (`agree_count`),
  KEY `lock` (`lock`),
  KEY `category_id` (`category_id`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`),
  FULLTEXT KEY `title_fulltext` (`title_fulltext`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_video_comment` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT '0',
  `video_id` int(10) DEFAULT '0',
  `message` text,
  `add_time` int(10) DEFAULT '0',
  `at_uid` int(10) DEFAULT NULL,
  `agree_count` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `video_id` (`video_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_video_danmaku` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT '0',
  `video_id` int(10) DEFAULT '0',
  `add_time` int(10) DEFAULT '0',
  `agree_count` int(10) DEFAULT '0',
  `stime` int(10) DEFAULT '0' COMMENT '出现时间 毫秒',
  `mode` int(10) DEFAULT '0',
  `size` int(10) DEFAULT '0',
  `color` int(10) DEFAULT '0',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `video_id` (`video_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`),
  KEY `stime` (`stime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_vote` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT '0',
  `type` varchar(32) DEFAULT NULL,
  `item_id` int(10) DEFAULT '0',
  `value` tinyint(1) DEFAULT '0',
  `add_time` int(10) DEFAULT '0',
  `extra_data` text COMMENT '预留',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `item_id` (`item_id`),
  KEY `add_time` (`add_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `item_id` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE IF NOT EXISTS `aws_failed_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `type` varchar(32) DEFAULT NULL,
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--


/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
