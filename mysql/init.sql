-- CyberPhish Database Initialization
-- Creates schema, stored routines, views, and seeds default admin + language

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- ========================================================
--  Tables (ordered for foreign key dependencies)
-- ========================================================

CREATE TABLE IF NOT EXISTS `bei_lang` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `locale` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_managers` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `remember_token` varchar(45) DEFAULT NULL,
  `main` int(21) NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '1',
  `country` int(21) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `bei_users` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `fullname` longtext,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `remember_token` varchar(50) DEFAULT NULL,
  `verify_token` varchar(255) DEFAULT NULL,
  `country` int(21) DEFAULT '0',
  `status` int(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `bei_settings` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `val1` text,
  `val2` text,
  `val3` int(21) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_information` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `content` longtext NOT NULL,
  `alias` text NOT NULL,
  `lang_id` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lang_information_idx` (`lang_id`),
  CONSTRAINT `lang_information` FOREIGN KEY (`lang_id`) REFERENCES `bei_lang` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_news` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `feat_img` text,
  `content` longtext NOT NULL,
  `alias` text NOT NULL,
  `created` datetime NOT NULL,
  `created_by` int(21) NOT NULL,
  `lang_id` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lang_id_news_idx` (`lang_id`),
  CONSTRAINT `lang_id_news` FOREIGN KEY (`lang_id`) REFERENCES `bei_lang` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_lm_category` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `lang_id` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lang_id_key_idx` (`lang_id`),
  CONSTRAINT `lang_id` FOREIGN KEY (`lang_id`) REFERENCES `bei_lang` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `bei_lm_content` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) DEFAULT NULL,
  `title` text NOT NULL,
  `content` longtext NOT NULL,
  `hours` float(100,1) NOT NULL DEFAULT '0.0',
  `lang_id` int(21) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lang_content` (`lang_id`),
  KEY `cat_content` (`cat_id`),
  CONSTRAINT `cat_content` FOREIGN KEY (`cat_id`) REFERENCES `bei_lm_category` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `lang_content` FOREIGN KEY (`lang_id`) REFERENCES `bei_lang` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_lm_content_files` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `content_id` int(21) NOT NULL,
  `name` text NOT NULL,
  `file_name` text NOT NULL,
  `mime_type` varchar(45) NOT NULL,
  `file_size` bigint(255) NOT NULL,
  `embed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `file_content_id_idx` (`content_id`),
  CONSTRAINT `file_content_id` FOREIGN KEY (`content_id`) REFERENCES `bei_lm_content` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_lm_users` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `user_id` int(21) DEFAULT NULL,
  `content_id` int(21) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usr_lm_content_idx` (`content_id`),
  KEY `usr_lm_usr_idx` (`user_id`),
  CONSTRAINT `usr_lm_usr` FOREIGN KEY (`user_id`) REFERENCES `bei_users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_selfeval_question` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) NOT NULL,
  `question` text NOT NULL,
  `q_type` tinyint(4) DEFAULT NULL COMMENT '1 - radio, 2 - checkbox',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_selfeval_option` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `question_id` int(21) NOT NULL,
  `option` text NOT NULL,
  `correct` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - incorrect, 1 - correct',
  `points` float(100,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_knowledge_test` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) NOT NULL,
  `question` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_knowledge_option` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `question_id` int(21) NOT NULL,
  `option` longtext NOT NULL,
  `correct` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `descr` text NOT NULL,
  `goal` text,
  `source` text,
  `actors` text,
  `image` text,
  `choose_type` varchar(45) DEFAULT NULL,
  `attack_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios_category` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios_attributes` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios_in_category` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `cat_id` int(21) NOT NULL,
  `scenario_id` int(21) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios_in_attributes` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `scenario_id` int(21) NOT NULL,
  `attribute_id` int(21) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios_options` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `scenario_id` int(21) NOT NULL,
  `parent_option_id` int(21) NOT NULL,
  `level` int(21) NOT NULL,
  `situation` text NOT NULL,
  `feedback` text,
  `image` text,
  `link` text,
  `option_type` tinyint(4) NOT NULL COMMENT '0 - incorrect (0 points), 1 - semi-incorrect (50 points), 2 - semi-correct (100 points), 3 - correct (200 points)',
  `points` int(21) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_badges` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `user_id` int(21) NOT NULL,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_loginlog` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `user_id` int(21) NOT NULL,
  `login_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_selfeval` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) NOT NULL,
  `test_id` int(21) NOT NULL,
  `public_id` varchar(45) NOT NULL,
  `option_id` int(21) NOT NULL,
  `user_id` int(21) NOT NULL,
  `question_id` int(21) NOT NULL,
  `correct` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - incorrect, 1 - correct',
  `points` int(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_selfeval_test` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `public_id` varchar(45) NOT NULL,
  `cat_id` int(21) NOT NULL,
  `user_id` int(21) NOT NULL,
  `lang_id` int(21) NOT NULL,
  `finished` int(21) NOT NULL DEFAULT '0',
  `started` datetime DEFAULT NULL,
  `ended` datetime DEFAULT NULL,
  `points` int(21) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_knowledge_test` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `public_id` varchar(45) NOT NULL,
  `user_id` int(21) NOT NULL,
  `lang_id` int(21) NOT NULL,
  `finished` tinyint(1) NOT NULL DEFAULT '0',
  `started` datetime DEFAULT NULL,
  `ended` datetime DEFAULT NULL,
  `question_set` longtext NOT NULL,
  `results` int(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_knowledge_options` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) NOT NULL,
  `test_id` int(21) NOT NULL,
  `public_id` varchar(45) NOT NULL,
  `user_id` int(21) NOT NULL,
  `question_id` int(21) NOT NULL,
  `correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_scenarios` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `public_id` varchar(45) NOT NULL,
  `user_id` int(21) NOT NULL,
  `scenario_id` int(21) NOT NULL,
  `lang_id` int(21) NOT NULL,
  `finished` tinyint(4) DEFAULT '0',
  `started` datetime DEFAULT NULL,
  `ended` datetime DEFAULT NULL,
  `points` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_scenarios_options` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `user_id` int(21) NOT NULL,
  `user_scenario_id` int(21) NOT NULL,
  `user_scenario_public_id` text NOT NULL,
  `scenario_id` int(21) NOT NULL,
  `option_id` int(21) NOT NULL,
  `option_type` tinyint(4) DEFAULT '0' COMMENT '0 - incorrect (0 points), 1 - semi-incorrect (50 points), 2 - semi-correct (100 points), 3 - correct (200 points)',
  `points` int(21) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ========================================================
--  Stored function
-- ========================================================

/*!50003 DROP FUNCTION IF EXISTS `bei_user_course_progress` */;
DELIMITER ;;
CREATE FUNCTION `bei_user_course_progress`(userid INT, langid INT) RETURNS int(11)
BEGIN
	SELECT SUM(hours) AS total_hours INTO @total_hours FROM bei_lm_content WHERE bei_lm_content.lang_id=langid;
	SELECT COUNT(*) AS total_self_eval_tests INTO @total_self_eval_tests FROM bei_selfeval_question LEFT JOIN bei_lm_category ON bei_lm_category.id = bei_selfeval_question.cat_id WHERE bei_lm_category.lang_id=langid;
	SELECT COUNT(DISTINCT bei_users_selfeval.test_id) INTO @user_selfeval_tests FROM bei_users_selfeval_test RIGHT JOIN bei_users_selfeval ON bei_users_selfeval.public_id = bei_users_selfeval_test.public_id WHERE bei_users_selfeval_test.lang_id = langid AND bei_users_selfeval_test.user_id=userid AND bei_users_selfeval_test.finished=1 ORDER BY bei_users_selfeval_test.id DESC;
	SELECT SUM(bei_lm_content.hours) AS users_hours INTO @users_hours FROM bei_users LEFT JOIN bei_lm_users ON bei_lm_users.user_id = bei_users.id LEFT JOIN bei_lm_content ON bei_lm_users.content_id = bei_lm_content.id WHERE bei_users.id = userid AND bei_lm_content.lang_id = langid;
	SELECT IF(COUNT(*)>0, 30, 0) INTO @knowledge_test FROM bei_users_knowledge_test WHERE bei_users_knowledge_test.finished=1 AND bei_users_knowledge_test.results >=75 AND bei_users_knowledge_test.user_id = userid AND lang_id = langid;
	SELECT COUNT(*) INTO @all_scenarios FROM bei_scenarios WHERE bei_scenarios.lang_id = langid;
	SELECT COUNT(DISTINCT bei_users_scenarios.scenario_id) INTO @user_scenarios FROM bei_users_scenarios WHERE bei_users_scenarios.lang_id = langid AND bei_users_scenarios.finished=1 AND bei_users_scenarios.user_id = userid;
	SET @total_course = ROUND(@users_hours * 100 / @total_hours, 0) * 0.25;
	SET @total_selfeval = ROUND(@user_selfeval_tests * 100 / @total_self_eval_tests, 0) * 0.2;
	SET @total_scenarios = ROUND(@user_scenarios * 100 / @all_scenarios, 0) * 0.25;
RETURN ROUND(@total_course + @total_selfeval + @knowledge_test + @total_scenarios,2);
END ;;
DELIMITER ;


-- ========================================================
--  Views
-- ========================================================

CREATE OR REPLACE VIEW `view_max_points_per_scenario` AS
  SELECT `bei_users`.`id` AS `id`,
         `bei_users`.`username` AS `username`,
         MAX(`bei_users_scenarios`.`points`) AS `max_points`,
         `bei_users_scenarios`.`scenario_id` AS `scenario_id`,
         `bei_users_scenarios`.`lang_id` AS `lang_id`
  FROM `bei_users`
  LEFT JOIN `bei_users_scenarios` ON `bei_users`.`id` = `bei_users_scenarios`.`user_id`
  WHERE `bei_users_scenarios`.`finished` = 1
  GROUP BY `bei_users`.`id`, `bei_users_scenarios`.`scenario_id`
  ORDER BY `bei_users_scenarios`.`points` DESC;

CREATE OR REPLACE VIEW `view_max_points_per_selfeval_cat` AS
  SELECT `bei_users`.`id` AS `id`,
         `bei_users`.`username` AS `username`,
         MAX(`bei_users_selfeval_test`.`points`) AS `max_points`,
         `bei_users_selfeval_test`.`cat_id` AS `cat_id`,
         `bei_users_selfeval_test`.`lang_id` AS `lang_id`
  FROM `bei_users`
  LEFT JOIN `bei_users_selfeval_test` ON `bei_users`.`id` = `bei_users_selfeval_test`.`user_id`
  WHERE `bei_users_selfeval_test`.`finished` = 1
  GROUP BY `bei_users`.`id`, `bei_users_selfeval_test`.`cat_id`
  ORDER BY `max_points` DESC;


-- ========================================================
--  Seed data
-- ========================================================

-- Default language
INSERT INTO `bei_lang` (`id`, `name`, `locale`) VALUES (3, 'English', 'en');

-- Default admin: admin@knf.vu.lt / admin
INSERT INTO `bei_managers` (`id`, `email`, `password`, `main`, `status`, `country`)
VALUES (1, 'admin@knf.vu.lt', '$2y$10$9ZZCTFYLM9h3BrKdYq8AjOGVX9glYH2L2yDprXIJJ5RnNEX6sriMO', 1, '1', 0);

-- Minimal settings
INSERT INTO `bei_settings` (`id`, `name`, `val1`, `val2`, `val3`) VALUES (2, 'self_eval_pertime', NULL, NULL, 5);


/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
