CREATE DATABASE IF NOT EXISTS `school_device_db` DEFAULT CHARACTER SET `utf8`;

CREATE TABLE IF NOT EXISTS school_device_db.devices (
	`id` 			INT(11) NOT NULL AUTO_INCREMENT,
	`dev_id`		VARCHAR(12) UNIQUE NOT NULL,			/* 设备硬件唯一标号，控制设备用 			*/
	`dev_type`		VARCHAR(12) NOT NULL,					/* shower、washer、box						*/
	`dev_locate` 	VARCHAR(12) NOT NULL,					/* 楼号-房号  楼号-楼层号					*/
	`dev_state` 	INT(2) NOT NULL DEFAULT -1,				/* 设备实际状态	0-close  1-open  -1-unknown	*/
	`ctrl`			VARCHAR(6) NOT NULL,					/* 设备所属控制器							*/

	`price`			DOUBLE NOT NULL,						/* 单价：分/秒								*/
	`student_no` 	INT(11) NOT NULL default -1,			/* 当前占用设备学生号， -1-未占用			*/
	`order`			VARCHAR(12) NOT NULL DEFAULT 'NONE',	/* 当前设备控制指令，OPEN, 	CLOSE			*/
	`order_recv_t` 	BIGINT NOT NULL DEFAULT 0,				/* 收到指令时的UTC时间戳					*/
	`order_send_t` 	BIGINT NOT NULL DEFAULT 0,	

	`open_t` 		BIGINT NOT NULL DEFAULT 0,				/* 设备开启时间								*/
	`close_t` 		BIGINT NOT NULL DEFAULT 0,				/* 设备关闭时间								*/
	`pre_close_t` 	BIGINT NOT NULL DEFAULT 1800,			/* 默认30分钟后自动关闭						*/
	`remark` 		VARCHAR(48) DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00102', 'shower', 'H1-412', '001', 30 );
INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00103', 'shower', 'H1-413', '001', 30 );
INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00106', 'shower', 'H1-416', '001', 30 );
INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00110', 'shower', 'H1-418', '001', 30 );

INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00203', 'shower', 'H1-713', '002', 30 );
INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00204', 'shower', 'H1-717', '002', 30 );
INSERT INTO school_device_db.devices ( dev_id, dev_type, dev_locate, ctrl, price  ) VALUES ( '00209', 'shower', 'H1-711', '002', 30 );