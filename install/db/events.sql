-- --------------------------------------------------------


SET GLOBAL event_scheduler = ON;



DROP EVENT IF EXISTS `delete_private_messages`;
CREATE EVENT `delete_private_messages`
	ON SCHEDULE
		EVERY 12 HOUR STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 1 HOUR)
	COMMENT '自动删除数天以前的私信'
	DO
		DELETE FROM `aws_pm_message` WHERE `plaintext` IS NULL AND `add_time` < (UNIX_TIMESTAMP() - 86400 * 30);



DROP EVENT IF EXISTS `delete_notifications`;
CREATE EVENT `delete_notifications`
	ON SCHEDULE
		EVERY 12 HOUR STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 2 HOUR)
	COMMENT '自动删除数天以前的通知'
	DO
		DELETE FROM `aws_notification` WHERE `add_time` < (UNIX_TIMESTAMP() - 86400 * 30);



DROP EVENT IF EXISTS `delete_activities`;
CREATE EVENT `delete_activities`
	ON SCHEDULE
		EVERY 12 HOUR STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 3 HOUR)
	COMMENT '自动删除数天以前的动态'
	DO
		DELETE FROM `aws_activity` WHERE `time` < (UNIX_TIMESTAMP() - 86400 * 30);

