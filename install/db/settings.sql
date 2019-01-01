-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table panic.aws_category
DROP TABLE IF EXISTS `aws_category`;
CREATE TABLE IF NOT EXISTS `aws_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `icon` varchar(240) DEFAULT NULL,
  `parent_id` int(11) DEFAULT '0',
  `sort` smallint(6) DEFAULT '0',
  `url_token` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `url_token` (`url_token`),
  KEY `title` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table panic.aws_category: 7 rows
/*!40000 ALTER TABLE `aws_category` DISABLE KEYS */;
INSERT INTO `aws_category` (`id`, `title`, `type`, `icon`, `parent_id`, `sort`, `url_token`) VALUES
	(1, '时政', 'question', NULL, 0, 0, NULL),
	(2, '财经', 'question', NULL, 0, 0, NULL),
	(3, '世界', 'question', NULL, 0, 0, NULL),
	(4, '科技', 'question', NULL, 0, 0, NULL),
	(5, '文娱', 'question', NULL, 0, 0, NULL),
	(6, '生活', 'question', NULL, 0, 0, NULL),
	(7, '其他', 'question', NULL, 0, 0, NULL);
/*!40000 ALTER TABLE `aws_category` ENABLE KEYS */;


-- Dumping structure for table panic.aws_system_setting
DROP TABLE IF EXISTS `aws_system_setting`;
CREATE TABLE IF NOT EXISTS `aws_system_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `varname` varchar(240) NOT NULL COMMENT '字段名',
  `value` text COMMENT '变量值',
  PRIMARY KEY (`id`),
  UNIQUE KEY `varname` (`varname`)
) ENGINE=MyISAM AUTO_INCREMENT=232 DEFAULT CHARSET=utf8mb4 COMMENT='系统设置';

-- Dumping data for table panic.aws_system_setting: 165 rows
/*!40000 ALTER TABLE `aws_system_setting` DISABLE KEYS */;
INSERT INTO `aws_system_setting` (`id`, `varname`, `value`) VALUES
	(1, 'db_engine', 's:6:"MyISAM";'),
	(2, 'site_name', 's:11:"新·品葱";'),
	(3, 'description', 's:33:"在自由的阳光下各抒己见";'),
	(4, 'keywords', 's:31:"品葱,品蔥,pincong,新品葱";'),
	(5, 'sensitive_words', 's:0:"";'),
	(6, 'def_focus_uids', 's:0:"";'),
	(7, 'answer_edit_time', 's:1:"0";'),
	(8, 'cache_level_high', 's:2:"60";'),
	(9, 'cache_level_normal', 's:3:"600";'),
	(10, 'cache_level_low', 's:4:"1800";'),
	(11, 'unread_flush_interval', 's:3:"100";'),
	(12, 'newer_invitation_num', 's:1:"5";'),
	(13, 'index_per_page', 's:2:"20";'),
	(15, 'img_url', 's:0:"";'),
	(16, 'upload_url', 's:8:"/uploads";'),
	(17, 'upload_dir', 's:9:"./uploads";'),
	(18, 'ui_style', 's:7:"pincong";'),
	(23, 'answer_unique', 's:1:"Y";'),
	(24, 'notifications_per_page', 's:2:"20";'),
	(25, 'contents_per_page', 's:2:"50";'),
	(26, 'hot_question_period', 's:1:"7";'),
	(27, 'category_display_mode', 's:4:"list";'),
	(28, 'recommend_users_number', 's:2:"20";'),
	(30, 'register_valid_type', 's:1:"N";'),
	(31, 'best_answer_day', 's:1:"0";'),
	(32, 'answer_self_question', 's:1:"Y";'),
	(33, 'censoruser', 's:0:"";'),
	(34, 'best_answer_min_count', 's:1:"4";'),
	(36, 'db_version', 's:8:"20160523";'),
	(37, 'statistic_code', 's:0:"";'),
	(38, 'upload_enable', 's:1:"N";'),
	(39, 'answer_length_lower', 's:1:"2";'),
	(40, 'quick_publish', 's:1:"N";'),
	(41, 'register_type', 's:4:"open";'),
	(42, 'question_title_limit', 's:3:"100";'),
	(43, 'register_seccode', 's:1:"Y";'),
	(44, 'admin_login_seccode', 's:1:"Y";'),
	(49, 'request_route_custom', 's:0:"";'),
	(50, 'upload_size_limit', 's:3:"512";'),
	(51, 'upload_avatar_size_limit', 's:3:"512";'),
	(52, 'topic_title_limit', 's:1:"6";'),
	(53, 'url_rewrite_enable', 's:1:"Y";'),
	(54, 'best_agree_min_count', 's:1:"5";'),
	(55, 'site_close', 's:1:"N";'),
	(56, 'close_notice', 's:39:"站点已关闭，管理员请登录。";'),
	(70, 'username_rule', 's:1:"1";'),
	(71, 'username_length_min', 's:1:"2";'),
	(72, 'username_length_max', 's:2:"14";'),
	(73, 'category_enable', 's:1:"Y";'),
	(75, 'nav_menu_show_child', 's:1:"N";'),
	(78, 'allowed_upload_types', 's:16:"jpg,jpeg,png,gif";'),
	(79, 'site_announce', 's:0:"";'),
	(80, 'icp_beian', 's:0:"";'),
	(82, 'today_topics', 's:0:"";'),
	(83, 'welcome_recommend_users', 's:0:"";'),
	(84, 'welcome_message_pm', 's:0:"";'),
	(139, 'id_verification_disabled', 's:1:"Y";'),
	(140, 'image_upload_link', 's:18:"https://imgur.com/";'),
	(85, 'time_style', 's:1:"N";'),
	(87, 'advanced_editor_enable', 's:1:"Y";'),
	(88, 'auto_question_lock_day', 's:1:"0";'),
	(89, 'default_timezone', 's:9:"Etc/GMT-8";'),
	(94, 'new_user_notification_setting', 'a:0:{}'),
	(95, 'user_action_history_fresh_upgrade', 's:1:"Y";'),
	(98, 'question_topics_limit', 's:1:"3";'),
	(104, 'new_question_force_add_topic', 's:1:"N";'),
	(105, 'unfold_question_comments', 's:1:"N";'),
	(106, 'reporting_disabled', 's:1:"Y";'),
	(112, 'admin_notifications', 'a:2:{s:17:"register_approval";s:1:"0";s:15:"verify_approval";s:1:"0";}'),
	(225, 'currency_system_config_question_disagreed', 's:1:"0";'),
	(226, 'currency_system_config_agree_answer', 's:1:"0";'),
	(127, 'enable_help_center', 's:1:"Y";'),
	(129, 'register_agreement', 's:231:"请勿将自身身份与其他网站相关联，切记不要使用与其他网站相同 ID 或者有关联的身份发言。\n\n由于本站不需邮箱即可注册，忘记密码将无法找回，请您妥善保管账号和密码。";'),
	(138, 'content_url_whitelist', 's:210:"https://pincong.rocks/\nhttps://www.pincong.rocks/\nhttps://i.imgur.com/\nhttps://i.redd.it/\nhttps://media.8ch.net/\nhttps://archive.is/\nhttps://web.archive.org/\nhttps://upload.wikimedia.org/\nhttps://pbs.twimg.com/";'),
	(137, 'pm_enabled', 's:1:"N";'),
	(145, 'expiration_private_messages', 's:2:"30";'),
	(146, 'expiration_notifications', 's:2:"30";'),
	(147, 'expiration_integral_logs', 's:2:"30";'),
	(148, 'expiration_user_actions', 's:2:"30";'),
	(149, 'random_seconds_min', 's:4:"3600";'),
	(150, 'random_seconds_max', 's:5:"90000";'),
	(231, 'reward_daily_active_users_currency', 's:0:"";'),
	(230, 'reward_daily_active_users_reputation', 's:0:"";'),
	(229, 'currency_system_config_answer_disagreed', 's:1:"0";'),
	(228, 'currency_system_config_disagree_answer', 's:1:"0";'),
	(227, 'currency_system_config_answer_agreed', 's:1:"0";'),
	(158, 'answer_length_min', 's:2:"10";'),
	(159, 'answer_length_max', 's:4:"5000";'),
	(160, 'comment_length_min', 's:1:"0";'),
	(161, 'comment_length_max', 's:4:"3000";'),
	(162, 'comment_downvote_fold', 's:1:"2";'),
	(163, 'sensitive_words_replacement', 's:0:"";'),
	(164, 'question_downvote_fold', 's:1:"2";'),
	(165, 'answer_downvote_fold', 's:1:"3";'),
	(166, 'article_downvote_fold', 's:1:"2";'),
	(171, 'auto_banning_agree_count', 's:0:"";'),
	(172, 'auto_banning_reputation', 's:0:"";'),
	(173, 'auto_banning_type', 's:3:"AND";'),
	(224, 'currency_system_config_disagree_question', 's:1:"0";'),
	(223, 'currency_system_config_question_agreed', 's:1:"0";'),
	(222, 'currency_system_config_agree_question', 's:1:"0";'),
	(221, 'currency_system_config_article_commented', 's:1:"0";'),
	(220, 'currency_system_config_comment_article', 's:1:"0";'),
	(219, 'currency_system_config_new_article', 's:1:"0";'),
	(218, 'currency_system_config_question_moved_down', 's:1:"0";'),
	(217, 'currency_system_config_move_down_question', 's:1:"0";'),
	(216, 'currency_system_config_question_moved_up', 's:1:"0";'),
	(215, 'currency_system_config_move_up_question', 's:1:"0";'),
	(214, 'currency_system_config_invite_answer', 's:1:"0";'),
	(213, 'currency_system_config_thanks', 's:1:"0";'),
	(212, 'currency_system_config_best_answer', 's:1:"0";'),
	(211, 'currency_system_config_question_answered', 's:1:"0";'),
	(210, 'currency_system_config_answer_question', 's:1:"0";'),
	(209, 'currency_system_config_new_question', 's:1:"0";'),
	(208, 'currency_system_config_register', 's:1:"0";'),
	(207, 'currency_unit', 's:3:"葱";'),
	(206, 'currency_name', 's:9:"游戏币";'),
	(205, 'currency_rule_name', 's:12:"游戏规则";'),
	(202, 'expiration_currency_logs', 's:0:"";'),
	(203, 'expiration_votes', 's:1:"1";'),
	(204, 'time_blurring', 's:1:"Y";');
/*!40000 ALTER TABLE `aws_system_setting` ENABLE KEYS */;


-- Dumping structure for table panic.aws_users_group
DROP TABLE IF EXISTS `aws_users_group`;
CREATE TABLE IF NOT EXISTS `aws_users_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT '0' COMMENT '0-会员组 1-系统组',
  `custom` tinyint(1) DEFAULT '0' COMMENT '是否自定义',
  `group_name` varchar(50) NOT NULL,
  `reputation_lower` int(11) DEFAULT '0',
  `reputation_higer` int(11) DEFAULT '0',
  `reputation_factor` float DEFAULT '0' COMMENT '威望系数',
  `permission` text COMMENT '权限设置',
  PRIMARY KEY (`group_id`),
  KEY `type` (`type`),
  KEY `custom` (`custom`)
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COMMENT='用户组';

-- Dumping data for table panic.aws_users_group: 10 rows
/*!40000 ALTER TABLE `aws_users_group` DISABLE KEYS */;
INSERT INTO `aws_users_group` (`group_id`, `type`, `custom`, `group_name`, `reputation_lower`, `reputation_higer`, `reputation_factor`, `permission`) VALUES
	(1, 0, 0, '超级管理员', 0, 0, 0, 'a:17:{s:16:"is_administrator";s:1:"1";s:12:"is_moderator";s:1:"1";s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:15:"publish_comment";s:1:"1";s:13:"edit_question";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:12:"edit_article";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:9:"bump_sink";s:1:"1";s:10:"vote_agree";s:1:"1";s:13:"vote_disagree";s:1:"1";s:10:"thank_user";s:1:"1";s:15:"allow_anonymous";s:1:"1";}'),
	(2, 0, 0, '前台管理员', 0, 0, 0, 'a:20:{s:12:"is_moderator";s:1:"1";s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:15:"publish_comment";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:13:"edit_question";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"manage_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:17:"redirect_question";s:1:"1";s:11:"publish_url";s:1:"1";s:12:"edit_article";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:9:"bump_sink";s:1:"1";s:14:"agree_disagree";s:1:"1";s:10:"vote_agree";s:1:"1";s:13:"vote_disagree";s:1:"1";s:10:"thank_user";s:1:"1";}'),
	(3, 0, 0, '未验证会员', 0, 0, 0, 'a:7:{s:16:"publish_question";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:11:"human_valid";s:1:"1";s:19:"question_valid_hour";s:1:"2";s:17:"answer_valid_hour";s:1:"2";s:15:"publish_article";s:1:"1";s:15:"publish_comment";s:1:"1";}'),
	(4, 0, 0, '普通会员', 0, 0, 0, 'a:3:{s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:19:"question_valid_hour";s:2:"10";s:17:"answer_valid_hour";s:2:"10";}'),
	(99, 0, 0, '游客', 0, 0, 0, 'a:8:{s:10:"visit_site";s:1:"1";s:13:"visit_explore";s:1:"1";s:14:"visit_question";s:1:"1";s:11:"visit_topic";s:1:"1";s:13:"visit_feature";s:1:"1";s:12:"visit_people";s:1:"1";s:13:"visit_chapter";s:1:"1";s:11:"answer_show";s:1:"1";}'),
	(100, 1, 0, 'lv0', -2147483648, 0, 0, 'a:7:{s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:11:"human_valid";s:1:"1";s:19:"question_valid_hour";s:1:"5";s:17:"answer_valid_hour";s:1:"5";}'),
	(101, 1, 0, 'lv1', 0, 5, 0, 'a:14:{s:12:"is_moderator";s:1:"1";s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:15:"publish_comment";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:11:"human_valid";s:1:"1";s:19:"question_valid_hour";s:1:"5";s:17:"answer_valid_hour";s:1:"5";s:19:"edit_question_topic";s:1:"1";s:10:"vote_agree";s:1:"1";s:15:"allow_anonymous";s:1:"1";}'),
	(102, 1, 0, 'lv2', 5, 10, 0, 'a:16:{s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:15:"publish_comment";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:10:"edit_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:17:"redirect_question";s:1:"1";s:11:"publish_url";s:1:"1";s:11:"human_valid";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:9:"bump_sink";s:1:"1";s:14:"agree_disagree";s:1:"1";s:10:"vote_agree";s:1:"1";s:10:"thank_user";s:1:"1";}'),
	(103, 1, 0, 'lv3', 10, 30, 0, 'a:17:{s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:15:"publish_comment";s:1:"1";s:21:"publish_approval_time";a:2:{s:5:"start";s:0:"";s:3:"end";s:0:"";}s:10:"edit_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:17:"redirect_question";s:1:"1";s:11:"publish_url";s:1:"1";s:11:"human_valid";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:9:"bump_sink";s:1:"1";s:14:"agree_disagree";s:1:"1";s:10:"vote_agree";s:1:"1";s:13:"vote_disagree";s:1:"1";s:10:"thank_user";s:1:"1";}'),
	(104, 1, 0, 'lv4', 30, 2147483647, 1, 'a:13:{s:16:"publish_question";s:1:"1";s:15:"answer_question";s:1:"1";s:15:"publish_article";s:1:"1";s:15:"comment_article";s:1:"1";s:15:"publish_comment";s:1:"1";s:10:"edit_topic";s:1:"1";s:12:"create_topic";s:1:"1";s:19:"edit_question_topic";s:1:"1";s:9:"bump_sink";s:1:"1";s:10:"vote_agree";s:1:"1";s:13:"vote_disagree";s:1:"1";s:10:"thank_user";s:1:"1";s:15:"allow_anonymous";s:1:"1";}');
/*!40000 ALTER TABLE `aws_users_group` ENABLE KEYS */;


-- Dumping structure for table panic.aws_users
DROP TABLE IF EXISTS `aws_users`;
CREATE TABLE IF NOT EXISTS `aws_users` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户的 UID',
  `user_name` varchar(240) DEFAULT NULL COMMENT '用户名',
  `password` varchar(60) DEFAULT NULL COMMENT '用户密码',
  `salt` varchar(16) DEFAULT NULL COMMENT '用户附加混淆码',
  `avatar_file` varchar(128) DEFAULT NULL COMMENT '头像文件',
  `sex` tinyint(1) DEFAULT NULL COMMENT '性别',
  `reg_time` int(10) DEFAULT '0' COMMENT '注册时间',
  `last_login` int(10) DEFAULT '0' COMMENT '最后登录时间',
  `notification_unread` int(11) NOT NULL DEFAULT '0' COMMENT '未读系统通知',
  `inbox_unread` int(11) NOT NULL DEFAULT '0' COMMENT '未读短信息',
  `inbox_recv` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-所有人可以发给我,1-我关注的人',
  `fans_count` int(10) NOT NULL DEFAULT '0' COMMENT '粉丝数',
  `friend_count` int(10) NOT NULL DEFAULT '0' COMMENT '观众数',
  `invite_count` int(10) NOT NULL DEFAULT '0' COMMENT '邀请我回答数量',
  `article_count` int(10) NOT NULL DEFAULT '0' COMMENT '文章数量',
  `question_count` int(10) NOT NULL DEFAULT '0' COMMENT '问题数量',
  `answer_count` int(10) NOT NULL DEFAULT '0' COMMENT '回答数量',
  `topic_focus_count` int(10) NOT NULL DEFAULT '0' COMMENT '关注话题数量',
  `group_id` int(10) DEFAULT '0' COMMENT '用户组',
  `reputation_group` int(10) DEFAULT '0' COMMENT '威望对应组',
  `forbidden` tinyint(1) DEFAULT '0' COMMENT '是否禁止用户',
  `is_first_login` tinyint(1) DEFAULT '1' COMMENT '首次登录标记',
  `agree_count` int(10) DEFAULT '0' COMMENT '赞同数量',
  `thanks_count` int(10) DEFAULT '0' COMMENT '感谢数量',
  `views_count` int(10) DEFAULT '0' COMMENT '个人主页查看数量',
  `reputation` int(10) DEFAULT '0' COMMENT '威望',
  `reputation_update_time` int(10) DEFAULT '0' COMMENT '威望更新',
  `currency` int(10) DEFAULT '0',
  `user_name_update_time` int(10) DEFAULT '0',
  `verified` varchar(32) DEFAULT NULL,
  `default_timezone` varchar(32) DEFAULT NULL,
  `recent_topics` text,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_name` (`user_name`) USING BTREE,
  KEY `reputation` (`reputation`),
  KEY `reputation_update_time` (`reputation_update_time`),
  KEY `group_id` (`group_id`),
  KEY `agree_count` (`agree_count`),
  KEY `thanks_count` (`thanks_count`),
  KEY `forbidden` (`forbidden`),
  KEY `currency` (`currency`),
  KEY `verified` (`verified`),
  KEY `answer_count` (`answer_count`),
  KEY `last_login` (`last_login`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table panic.aws_users: 0 rows
/*!40000 ALTER TABLE `aws_users` DISABLE KEYS */;
INSERT INTO `aws_users` (`uid`, `user_name`, `password`, `salt`, `avatar_file`, `sex`, `reg_time`, `last_login`, `notification_unread`, `inbox_unread`, `inbox_recv`, `fans_count`, `friend_count`, `invite_count`, `article_count`, `question_count`, `answer_count`, `topic_focus_count`, `group_id`, `reputation_group`, `forbidden`, `is_first_login`, `agree_count`, `thanks_count`, `views_count`, `reputation`, `reputation_update_time`, `currency`, `user_name_update_time`, `verified`, `default_timezone`, `recent_topics`) VALUES
	(1, 'admin', '$2y$10$TaEOhERXEB51Ds8i/iBbq.Qy2v82s3yidCLGz/iv25PJ7hxXnq7r.', 'gzeu', '', 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL);
/*!40000 ALTER TABLE `aws_users` ENABLE KEYS */;


-- Dumping structure for table panic.aws_users_attrib
DROP TABLE IF EXISTS `aws_users_attrib`;
CREATE TABLE IF NOT EXISTS `aws_users_attrib` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` int(11) DEFAULT NULL COMMENT '用户UID',
  `introduction` varchar(240) DEFAULT NULL COMMENT '个人简介',
  `signature` varchar(240) DEFAULT NULL COMMENT '个人签名',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='用户附加属性表';

-- Dumping data for table panic.aws_users_attrib: 0 rows
/*!40000 ALTER TABLE `aws_users_attrib` DISABLE KEYS */;
INSERT INTO `aws_users_attrib` (`id`, `uid`, `introduction`, `signature`) VALUES
	(1, 1, NULL, '');
/*!40000 ALTER TABLE `aws_users_attrib` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
