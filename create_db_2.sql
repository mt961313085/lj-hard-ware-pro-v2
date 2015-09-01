CREATE DATABASE IF NOT EXISTS `yunkadb` DEFAULT CHARACTER SET `utf8`;

USE yunkadb

CREATE TABLE IF NOT EXISTS devices_ctrl (
	`id` 			INT(11) NOT NULL AUTO_INCREMENT,
	`dev_id`		VARCHAR(12) UNIQUE NOT NULL,			/* 设备硬件唯一标号，控制设备用 			*/
	`dev_type`		VARCHAR(12) NOT NULL,					/* shower、washer、box						*/
	`dev_locate` 	VARCHAR(12) NOT NULL,					/* 楼号-房号  楼号-楼层号					*/
	`dev_state` 	INT(2) NOT NULL DEFAULT -1,				/* 设备实际状态	0-close  1-open  -1-unknown	*/
	`state_recv_t` 	BIGINT NOT NULL DEFAULT 0,				/*	设备状态更新时间						*/
	
	`ctrl`			VARCHAR(6) NOT NULL,					/* 设备所属控制器							*/

	`price`			DOUBLE NOT NULL,						/* 单价：分/分钟							*/
	`student_no` 	VARCHAR(24) NOT NULL default '-1',		/* 当前占用设备学生号， -1-未占用			*/
	`ins`			VARCHAR(12) NOT NULL DEFAULT 'NONE',	/* 当前设备控制指令，OPEN, 	CLOSE			*/
	`ins_recv_t` 	BIGINT NOT NULL DEFAULT 0,				/* 收到指令时的UTC时间戳					*/
	`ins_send_t` 	BIGINT NOT NULL DEFAULT 0,	
	
	`break_t` 		BIGINT NOT NULL DEFAULT 0,				/* 设备使用时中断时间						*/
	`open_t` 		BIGINT NOT NULL DEFAULT 0,				/* 设备开启时间								*/
	`close_t` 		BIGINT NOT NULL DEFAULT 0,				/* 设备关闭时间								*/
	`pre_close_t` 	BIGINT NOT NULL DEFAULT 1800,			/* 默认30分钟后自动关闭						*/
	`remark` 		VARCHAR(48) DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS fee_record (
	`dev_id` 		VARCHAR(12) NOT NULL,				/* 设备硬件唯一标号						*/
	`student_no` 	INT(11) NOT NULL,					/* 设备使用学生学号						*/
	`open_t` 		BIGINT NOT NULL DEFAULT 0,			/* 设备开启时间							*/
	`close_t` 		BIGINT NOT NULL DEFAULT 0,			/* 设备关闭时间							*/
	`break_t` 		BIGINT NOT NULL DEFAULT 0,			/* 设备使用期间中断时间					*/
	`price` 		INT(11) NOT NULL,					/* 单价									*/
	`sum_t` 		BIGINT NOT NULL DEFAULT 0,			/*	计费总时长							*/
	`fee` 			INT(11) NOT NULL DEFAULT 0,			/* 总费用，单位：分						*/
	`fee_type`		VARCHAR(12),						/* 用于记录何种情况下产生的fee，主要用于调试 */
	`fee_flag` 		INT(11) NOT NULL DEFAULT 0,			/* 是否支付成功 0-未成功  1-成功		*/
	PRIMARY KEY (`student_no`,`open_t`,`dev_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00101', 'shower', 'H1-412', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00102', 'shower', 'H1-413', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00103', 'shower', 'H1-414', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00104', 'shower', 'H1-415', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00105', 'shower', 'H1-416', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00106', 'shower', 'H1-417', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00107', 'shower', 'H1-418', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00108', 'shower', 'H1-419', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00109', 'shower', 'H1-420', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00110', 'shower', 'H1-421', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00111', 'shower', 'H1-422', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00112', 'shower', 'H1-423', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00113', 'shower', 'H1-424', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00114', 'shower', 'H1-425', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00115', 'shower', 'H1-426', '001', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00116', 'shower', 'H1-427', '001', 30 );


INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00203', 'shower', 'H1-713', '002', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00204', 'shower', 'H1-717', '002', 30 );
INSERT INTO devices_ctrl ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00209', 'shower', 'H1-711', '002', 30 );