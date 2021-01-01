-- --------------------------------------------------------


--
CREATE TABLE `aws_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `category_id` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  `view_count` int(11) DEFAULT '0',
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `lock` tinyint(1) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `last_uid` int(11) DEFAULT '0',
  `redirect_id` int(11) DEFAULT '0',
  `title` varchar(240) DEFAULT NULL,
  `message` text,
  `reply_count` int(11) DEFAULT '0',
  `comment_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `category_id` (`category_id`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`),
  KEY `view_count` (`view_count`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`),
  KEY `lock` (`lock`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  KEY `reply_count` (`reply_count`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_question_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(11) DEFAULT '0',
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `comment_count` int(11) DEFAULT '0',
  `fold` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`),
  KEY `add_time` (`add_time`),
  KEY `uid` (`uid`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_question_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(11) DEFAULT '0',
  `at_uid` int(11) DEFAULT NULL,
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `fold` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_question_discussion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(11) DEFAULT '0',
  `at_uid` int(11) DEFAULT NULL,
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `fold` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `category_id` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  `view_count` int(11) DEFAULT '0',
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `lock` tinyint(1) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `last_uid` int(11) DEFAULT '0',
  `redirect_id` int(11) DEFAULT '0',
  `title` varchar(240) DEFAULT NULL,
  `message` text,
  `reply_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `category_id` (`category_id`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`),
  KEY `view_count` (`view_count`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`),
  KEY `lock` (`lock`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  KEY `reply_count` (`reply_count`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_article_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(11) DEFAULT '0',
  `at_uid` int(11) DEFAULT NULL,
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `fold` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `parent_id` (`parent_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `category_id` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  `view_count` int(11) DEFAULT '0',
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `lock` tinyint(1) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `last_uid` int(11) DEFAULT '0',
  `redirect_id` int(11) DEFAULT '0',
  `title` varchar(240) DEFAULT NULL,
  `message` text,
  `reply_count` int(11) DEFAULT '0',
  `source_type` varchar(32) DEFAULT NULL,
  `source` text,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `category_id` (`category_id`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`),
  KEY `view_count` (`view_count`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`),
  KEY `lock` (`lock`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  KEY `reply_count` (`reply_count`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_video_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `message` text,
  `add_time` int(11) DEFAULT '0',
  `at_uid` int(11) DEFAULT NULL,
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `fold` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `parent_id` (`parent_id`),
  KEY `add_time` (`add_time`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_posts_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) DEFAULT '0' ,
  `post_type` varchar(16) DEFAULT NULL,
  `uid` int(11) DEFAULT '0',
  `category_id` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  `view_count` int(11) DEFAULT '0',
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0' COMMENT '回复所获声望总和',
  `lock` tinyint(1) DEFAULT '0',
  `recommend` tinyint(1) DEFAULT '0',
  `sort` tinyint(2) DEFAULT '0',
  `reply_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `post_type` (`post_type`),
  KEY `uid` (`uid`),
  KEY `category_id` (`category_id`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`),
  KEY `view_count` (`view_count`),
  KEY `agree_count` (`agree_count`),
  KEY `reputation` (`reputation`),
  KEY `lock` (`lock`),
  KEY `recommend` (`recommend`),
  KEY `sort` (`sort`),
  KEY `reply_count` (`reply_count`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `description` varchar(240) DEFAULT NULL,
  `group_id` int(11) DEFAULT '0',
  `sort` smallint(6) DEFAULT '0',
  `skip` TINYINT(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `item_id` int(11) DEFAULT '0',
  `time` int(11) DEFAULT '0',
  `type` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `time` (`time`),
  KEY `item_id` (`item_id`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_feature` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `link` text,
  `enabled` tinyint(1) DEFAULT '0',
  `sort` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`),
  KEY `sort` (`sort`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_currency_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `action` varchar(64) DEFAULT NULL,
  `currency` int(11) DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `balance` int(11) DEFAULT '0',
  `item_id` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `action` (`action`),
  KEY `time` (`time`),
  KEY `currency` (`currency`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_nav_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `description` varchar(240) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `type_id` int(11) DEFAULT '0',
  `link` varchar(240) DEFAULT NULL,
  `icon` varchar(240) DEFAULT NULL,
  `sort` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`link`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_uid` int(11) DEFAULT '0',
  `recipient_uid` int(11) DEFAULT '0',
  `action` varchar(64) DEFAULT NULL,
  `thread_type` varchar(32) DEFAULT NULL,
  `thread_id` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `item_id` int(11) DEFAULT '0',
  `read_flag` tinyint(1) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sender_uid` (`sender_uid`),
  KEY `recipient_uid` (`recipient_uid`),
  KEY `read_flag` (`read_flag`),
  KEY `add_time` (`add_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_question_invite` (
  `question_invite_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `question_id` int(11) DEFAULT '0',
  `sender_uid` int(11) DEFAULT '0',
  `recipients_uid` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`question_invite_id`),
  KEY `question_id` (`question_id`),
  KEY `sender_uid` (`sender_uid`),
  KEY `recipients_uid` (`recipients_uid`),
  KEY `add_time` (`add_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_post_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_type` varchar(16) DEFAULT NULL,
  `post_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `post_type` (`post_type`),
  KEY `post_id` (`post_id`),
  KEY `uid` (`uid`),
  KEY `add_time` (`add_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_related_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT '0',
  `related_id` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `related_id` (`related_id`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_system_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `varname` varchar(240) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `varname` (`varname`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_topic` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_title` varchar(64) NOT NULL,
  `add_time` int(11) DEFAULT '0',
  `discuss_count` int(11) DEFAULT '0',
  `topic_description` text,
  `topic_pic` varchar(240) DEFAULT NULL,
  `topic_lock` tinyint(2) DEFAULT '0',
  `focus_count` int(11) DEFAULT '0',
  `merged_id` int(11) DEFAULT '0' COMMENT '是否被重定向到另一个话题',
  `discuss_count_last_week` int(11) DEFAULT '0',
  `discuss_count_last_month` int(11) DEFAULT '0',
  `discuss_count_update` int(11) DEFAULT '0',
  PRIMARY KEY (`topic_id`),
  UNIQUE KEY `topic_title` (`topic_title`),
  KEY `merged_id` (`merged_id`),
  KEY `discuss_count` (`discuss_count`),
  KEY `add_time` (`add_time`),
  KEY `focus_count` (`focus_count`),
  KEY `topic_lock` (`topic_lock`),
  KEY `discuss_count_last_week` (`discuss_count_last_week`),
  KEY `discuss_count_last_month` (`discuss_count_last_month`),
  KEY `discuss_count_update` (`discuss_count_update`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_topic_focus` (
  `focus_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `topic_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`focus_id`),
  KEY `uid` (`uid`),
  KEY `topic_id` (`topic_id`),
  KEY `topic_uid` (`topic_id`,`uid`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_topic_merge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_id` int(11) DEFAULT '0',
  `target_id` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT '0',
  `time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`),
  KEY `target_id` (`target_id`),
  KEY `uid` (`uid`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_topic_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT '0',
  `item_id` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `type` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `type` (`type`),
  KEY `item_id` (`item_id`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(240) NOT NULL,
  `password` varchar(60) DEFAULT NULL,
  `salt` varchar(60) DEFAULT NULL,
  `password_version` tinyint(1) DEFAULT '0',
  `avatar_file` varchar(128) DEFAULT NULL,
  `sex` tinyint(1) DEFAULT '0',
  `reg_time` int(11) DEFAULT '0',
  `inbox_unread` int(11) DEFAULT '0' COMMENT '未读短信息数量',
  `inbox_recv` tinyint(1) DEFAULT '0' COMMENT '3-所有人可以发给我, 2-拒绝所有人, 1-我关注的人, 0-系统默认',
  `invite_count` int(11) DEFAULT '0' COMMENT '邀请我回答数量',
  `group_id` int(11) DEFAULT '0' COMMENT '用户组ID',
  `flagged` int(11) DEFAULT '0' COMMENT '替代用户组ID',
  `forbidden` tinyint(1) DEFAULT '0',
  `agree_count` int(11) DEFAULT '0',
  `reputation` float DEFAULT '0',
  `currency` int(11) DEFAULT '0',
  `user_update_time` int(11) DEFAULT '0' COMMENT '用户最后发言时间',
  `mod_time` int(11) DEFAULT '0',
  `settings` text,
  `verified` varchar(32) DEFAULT NULL COMMENT '认证头衔',
  `signature` varchar(140) DEFAULT NULL COMMENT '个人签名',
  `public_key` text,
  `private_key` text,
  `extra_data` text COMMENT '额外数据',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `group_id` (`group_id`),
  KEY `flagged` (`flagged`),
  KEY `forbidden` (`forbidden`),
  KEY `reputation` (`reputation`),
  KEY `agree_count` (`agree_count`),
  KEY `currency` (`currency`),
  KEY `user_update_time` (`user_update_time`),
  KEY `mod_time` (`mod_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_users_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT '0' COMMENT '0-系统组 1-声望组 2-特殊组',
  `group_name` text,
  `reputation_lower` float DEFAULT '0',
  `reputation_higer` float DEFAULT '0',
  `reputation_factor` float DEFAULT '0' COMMENT '声望系数',
  `reputation_factor_receive` float NULL DEFAULT NULL COMMENT '接收声望系数 留空则使用普通声望系数',
  `content_reputation_factor` float NULL DEFAULT NULL COMMENT '內容声望系数 留空则使用普通声望系数',
  `permission` text COMMENT '权限设置',
  PRIMARY KEY (`group_id`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_user_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `target_uid` int(11) DEFAULT '0',
  `value` tinyint(1) DEFAULT '0' COMMENT '小于0-屏蔽 大于0-好友',
  `time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `target_uid` (`target_uid`),
  KEY `value` (`value`),
  KEY `time` (`time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_user_notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `data` text,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_scheduled_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) DEFAULT NULL,
  `uid` int(11) DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `time` int(11) DEFAULT '0',
  `data` text,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `uid` (`uid`),
  KEY `parent_id` (`parent_id`),
  KEY `time` (`time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_content_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `thread_type` varchar(32) DEFAULT NULL,
  `thread_id` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `item_id` int(11) DEFAULT '0',
  `child_type` varchar(32) DEFAULT NULL,
  `child_id` int(11) DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `thread_type` (`thread_type`),
  KEY `thread_id` (`thread_id`),
  KEY `item_type` (`item_type`),
  KEY `item_id` (`item_id`),
  KEY `child_type` (`child_type`),
  KEY `child_id` (`child_id`),
  KEY `time` (`time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `recipient_uid` int(11) DEFAULT '0',
  `type` varchar(32) DEFAULT NULL,
  `item_id` int(11) DEFAULT '0',
  `value` tinyint(1) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `recipient_uid` (`recipient_uid`),
  KEY `type` (`type`),
  KEY `item_id` (`item_id`),
  KEY `value` (`value`),
  KEY `add_time` (`add_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `item_type` varchar(32) DEFAULT NULL,
  `item_id` int(11) DEFAULT '0',
  `thread_type` varchar(32) DEFAULT NULL,
  `thread_id` int(11) DEFAULT '0',
  `category_id` int(11) DEFAULT '0',
  `time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `item_type` (`item_type`),
  KEY `item_id` (`item_id`),
  KEY `thread_type` (`thread_type`),
  KEY `thread_id` (`thread_id`),
  KEY `category_id` (`category_id`),
  KEY `time` (`time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_failed_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `type` varchar(32) DEFAULT NULL,
  `time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `time` (`time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_knowledge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(240) DEFAULT NULL,
  `message` text,
  `remarks` text,
  `uid` int(11) DEFAULT '0',
  `last_uid` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `last_uid` (`last_uid`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `admin_uid` int(11) DEFAULT '0',
  `type` varchar(64) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `detail` text,
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `admin_uid` (`admin_uid`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `add_time` (`add_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_pm_conversation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_message_id` int(11) DEFAULT '0',
  `member_count` int(11) DEFAULT '0',
  `uid_1` int(11) DEFAULT '0',
  `uid_2` int(11) DEFAULT '0',
  `uid_3` int(11) DEFAULT '0',
  `uid_4` int(11) DEFAULT '0',
  `uid_5` int(11) DEFAULT '0',
  `unread_1` int(11) DEFAULT '0',
  `unread_2` int(11) DEFAULT '0',
  `unread_3` int(11) DEFAULT '0',
  `unread_4` int(11) DEFAULT '0',
  `unread_5` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `last_message_id` (`last_message_id`),
  KEY `member_count` (`member_count`),
  KEY `uid_1` (`uid_1`),
  KEY `uid_2` (`uid_2`),
  KEY `uid_3` (`uid_3`),
  KEY `uid_4` (`uid_4`),
  KEY `uid_5` (`uid_5`),
  KEY `unread_1` (`unread_1`),
  KEY `unread_2` (`unread_2`),
  KEY `unread_3` (`unread_3`),
  KEY `unread_4` (`unread_4`),
  KEY `unread_5` (`unread_5`),
  KEY `add_time` (`add_time`),
  KEY `update_time` (`update_time`)
) DEFAULT CHARSET=utf8mb4;

--


--
CREATE TABLE `aws_pm_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) DEFAULT '0',
  `sender_uid` int(11) DEFAULT '0',
  `plaintext` text,
  `message_1` text,
  `message_2` text,
  `message_3` text,
  `message_4` text,
  `message_5` text,
  `receipt_1` int(11) DEFAULT '0',
  `receipt_2` int(11) DEFAULT '0',
  `receipt_3` int(11) DEFAULT '0',
  `receipt_4` int(11) DEFAULT '0',
  `receipt_5` int(11) DEFAULT '0',
  `add_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_uid` (`sender_uid`),
  KEY `receipt_1` (`receipt_1`),
  KEY `receipt_2` (`receipt_2`),
  KEY `receipt_3` (`receipt_3`),
  KEY `receipt_4` (`receipt_4`),
  KEY `receipt_5` (`receipt_5`),
  KEY `add_time` (`add_time`)
) DEFAULT CHARSET=utf8mb4;

--
