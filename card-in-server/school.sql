-- phpMyAdmin SQL Dump
-- version 4.1.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2015-08-24 10:27:56
-- 服务器版本： 5.1.73-log
-- PHP Version: 5.5.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `school`
--

-- --------------------------------------------------------

--
-- 表的结构 `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE IF NOT EXISTS `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_no` int(11) NOT NULL,
  `device_id` varchar(12) NOT NULL,
  `flag` int(11) NOT NULL,
  `begin_time` int(11) NOT NULL,
  `end_time` int(11) NOT NULL,
  `pre_end_time` int(11) NOT NULL,
  `fee_flag` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_no` (`student_no`,`device_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

--
-- 转存表中的数据 `devices`
--

INSERT INTO `devices` (`id`, `student_no`, `device_id`, `flag`, `begin_time`, `end_time`, `pre_end_time`, `fee_flag`) VALUES
(1, 20300032, 'J65011', 0, 1440349281, 1440351007, 1440350481, 1),
(2, 20300033, 'J65011', 1, 0, 0, 0, 0),
(3, 20300032, 'J65012', 0, 1440377502, 1440377689, 1440381162, 1),
(4, 20300032, 'J65013', 1, 1440381808, 0, 1440392608, 0),
(5, 20300033, 'J65013', 0, 0, 0, 0, 0),
(7, 20300032, 'J61021', 1, 1440381795, 1440377490, 1440392595, 1),
(8, 20300032, '015011', 0, 1440381802, 1440383217, 1440392602, 1),
(9, 20300032, 'J67021', 0, 0, 1440375345, 0, 1),
(10, 20300032, 'J61091', 0, 0, 1440377493, 0, 1);

-- --------------------------------------------------------

--
-- 表的结构 `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_no` int(11) NOT NULL,
  `device_id` varchar(12) NOT NULL,
  `post_desc` varchar(255) NOT NULL,
  `msg` varchar(255) NOT NULL,
  `reply` varchar(255) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `post_time` int(11) NOT NULL,
  `reply_time` int(11) NOT NULL,
  `post_ip` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- 转存表中的数据 `feedback`
--

INSERT INTO `feedback` (`id`, `student_no`, `device_id`, `post_desc`, `msg`, `reply`, `type`, `post_time`, `reply_time`, `post_ip`) VALUES
(1, 20300032, 'H62011', '漏水', '希望尽快处理', '', 1, 1440312708, 0, '127.0.0.1'),
(2, 20300032, 'H62011', '漏电', '希望尽快处理', '', 1, 1440312781, 0, '127.0.0.1'),
(3, 20300032, '', '漏电', '希望尽快处理', '', 1, 1440312955, 0, '127.0.0.1'),
(4, 20300032, 'J61021', '热水器坏了', '热水器坏了', '', 1, 1440326927, 0, '127.0.0.1'),
(5, 20300032, 'http://www.w', '哈图', '哈图', '', 1, 1440326937, 0, '127.0.0.1');

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `student_no` int(11) NOT NULL COMMENT '学号',
  `password` varchar(20) NOT NULL COMMENT '密码',
  `token` varchar(50) NOT NULL COMMENT '授权token',
  `last_login_time` int(11) NOT NULL COMMENT '最后登录时间',
  `login_times` int(11) NOT NULL COMMENT '登录次数',
  `login_ip` varchar(50) NOT NULL COMMENT '最后登录IP',
  `token_expires` int(11) NOT NULL COMMENT 'token过期时间',
  PRIMARY KEY (`student_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='学生账号信息表';

-- --------------------------------------------------------

--
-- 表的结构 `user_info`
--

DROP TABLE IF EXISTS `user_info`;
CREATE TABLE IF NOT EXISTS `user_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentNo` varchar(20) NOT NULL,
  `sex` tinyint(4) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(30) NOT NULL,
  `school_zone` varchar(20) NOT NULL,
  `from` varchar(100) NOT NULL,
  `graduated` varchar(50) NOT NULL,
  `home_address` varchar(100) NOT NULL,
  `nation` varchar(20) NOT NULL,
  `carrier_account` varchar(500) NOT NULL,
  `wash_setting` varchar(255) NOT NULL,
  `cardNo` varchar(50) NOT NULL,
  `userName` varchar(50) NOT NULL,
  `nickName` varchar(50) NOT NULL,
  `headImg` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `cardBalance` int(11) NOT NULL,
  `monthlyAmt` int(11) NOT NULL,
  `password` varchar(30) NOT NULL,
  `token` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_no` (`studentNo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- 转存表中的数据 `user_info`
--

INSERT INTO `user_info` (`id`, `studentNo`, `sex`, `phone`, `department`, `school_zone`, `from`, `graduated`, `home_address`, `nation`, `carrier_account`, `wash_setting`, `cardNo`, `userName`, `nickName`, `headImg`, `email`, `cardBalance`, `monthlyAmt`, `password`, `token`) VALUES
(2, '000020300032', 1, '18683528961', '建工系', '东区', '四川成都', '石室中学', '成都锦江区某路', '汉', '', '{"delay_close":70,"delay_time":15}', '3584944054', '杨良春', '杨良春', '', '', 17540, 6170, '111111', '000020300032ECB60F92F2198D141799669DCFEA8899'),
(3, '201520152015', 0, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 0, '', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
