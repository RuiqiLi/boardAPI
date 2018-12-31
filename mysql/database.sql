create database if not exists boardRPG default charset utf8 collate utf8_general_ci;
use boardRPG;

CREATE TABLE `t_session` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `session_id` char(128) NOT NULL,
  `session_key` varchar(50) NOT NULL,
  `open_id` varchar(50) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE `session_id_UNIQUE` (`session_id`),
  UNIQUE `open_id_UNIQUE` (`open_id`)
) ENGINE = InnoDB;

CREATE TABLE `t_user` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `open_id` varchar(50) NOT NULL,
  `nick_name` varchar(50) NOT NULL,
  `gender` int NOT NULL DEFAULT 0 comment '0：未知、1：男、2：女',
  `city` varchar(15) NOT NULL DEFAULT "",
  `province` varchar(15) NOT NULL DEFAULT "",
  `country` varchar(15) NOT NULL DEFAULT "",
  `avatar_url` varchar(2048) NOT NULL DEFAULT "",
  `union_id` varchar(50) NOT NULL DEFAULT "",
  `real_name` varchar(15) NOT NULL DEFAULT "",
  `is_admin` tinyint NOT NULL DEFAULT 0 comment '1：是；0：否',
  `login_times` int NOT NULL DEFAULT 1,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NOT NULL,
  UNIQUE `open_id_UNIQUE` (`open_id`)
) ENGINE = InnoDB;

CREATE TRIGGER t_before_insert_on_t_user
BEFORE INSERT ON t_user
FOR EACH ROW SET new.updated=now();

CREATE TRIGGER t_before_update_on_t_user
BEFORE UPDATE ON t_user
FOR EACH ROW SET new.updated=now();

CREATE TABLE `t_avalon_room` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `player_num` int NOT NULL,
  `captain_no` int NOT NULL,
  `delay_max` int NOT NULL DEFAULT 4,
  `bad_know_others` tinyint NOT NULL DEFAULT 1 comment '1：是；0：否',
  `captain_can_vote` tinyint NOT NULL DEFAULT 0 comment '1：是；0：否',
  `turn` int NOT NULL DEFAULT 0 comment '0：人不齐未开始；1~5：第1~5次任务；6：刺杀梅林阶段；7：游戏结束',
  `mission_status` varchar(16384) NOT NULL DEFAULT "[]" comment 'json data of mission status',
  `vote_status` varchar(2048) NOT NULL DEFAULT "[]" comment 'json data of vote status',
  `kill_player` int NOT NULL DEFAULT 0,
  `updated` timestamp NOT NULL,
) ENGINE = InnoDB;

CREATE TRIGGER t_before_insert_on_t_avalon_room
BEFORE INSERT ON t_avalon_room
FOR EACH ROW SET new.updated=now();

CREATE TRIGGER t_before_update_on_t_avalon_room
BEFORE UPDATE ON t_avalon_room
FOR EACH ROW SET new.updated=now();

CREATE TABLE `t_user_avalon` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `avalon_room_id` int NOT NULL,
  `user_id` int NOT NULL,
  `player_no` int NOT NULL,
  `user_role` varchar(4) NOT NULL DEFAULT "",
  `can_do_mission` tinyint NOT NULL DEFAULT 0 comment '1：是；0：否',
  INDEX `avalon_room_id_INDEX` (`avalon_room_id`),
  UNIQUE `user_id_INDEX` (`user_id`)
) ENGINE = InnoDB;

delimiter $
CREATE TRIGGER t_after_insert_on_t_user_avalon
AFTER INSERT ON t_user_avalon
FOR EACH ROW
BEGIN
UPDATE t_avalon_room set updated = now() where t_avalon_room.id = new.avalon_room_id;
END$
delimiter ;

CREATE TABLE `t_message` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `type` int NOT NULL DEFAULT 0 comment '0：用户反馈；1：管理员回复',
  `message` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` int NOT NULL DEFAULT 0 comment '0：normal；1：deleted',
  INDEX `user_id_INDEX` (`user_id`)
) ENGINE = InnoDB;

CREATE TABLE `t_game2048` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `score` int NOT NULL DEFAULT 0,
  `max` int NOT NULL DEFAULT 0,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE `user_id_INDEX` (`user_id`),
  INDEX `score_INDEX` (`score`)
) ENGINE = InnoDB;

CREATE TRIGGER t_before_update_on_t_game2048
BEFORE UPDATE ON t_game2048
FOR EACH ROW SET new.updated=now();

CREATE TABLE `t_letter` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` int NOT NULL DEFAULT 0 comment '0：normal；1：deleted'
) ENGINE = InnoDB;
