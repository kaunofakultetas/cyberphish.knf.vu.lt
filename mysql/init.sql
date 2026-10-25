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

-- The database's own default, utf8mb4 like every table below, so a
-- table or column created later without a character set of its own
-- keeps every letter too
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ========================================================
--  Tables (ordered for foreign key dependencies)
-- ========================================================

-- Besides the primary and foreign keys, every table the app
-- searches keeps a key on the columns it finds the rows by —
-- the member and the language, an attempt's public id, a
-- question's category, a simulation step's place — so no
-- lookup reads a whole table; the member tables run to tens
-- of thousands of rows, and the course-progress function and
-- the admin dashboard look rows up per member and language

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `fullname` longtext,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `remember_token` varchar(50) DEFAULT NULL,
  `reset_token` varchar(50) NOT NULL DEFAULT '',
  `verify_token` varchar(255) DEFAULT NULL,
  `country` int(21) DEFAULT '0',
  `status` int(21) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  PRIMARY KEY (`id`),
  KEY `cat_id_idx` (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_selfeval_option` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `question_id` int(21) NOT NULL,
  `option` text NOT NULL,
  `correct` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - incorrect, 1 - correct',
  `points` float(100,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `question_id_idx` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_knowledge_test` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) NOT NULL,
  `question` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cat_id_idx` (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_knowledge_option` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `question_id` int(21) NOT NULL,
  `option` longtext NOT NULL,
  `correct` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `question_id_idx` (`question_id`)
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
  PRIMARY KEY (`id`),
  KEY `lang_id_idx` (`lang_id`)
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
  PRIMARY KEY (`id`),
  KEY `scenario_id_idx` (`scenario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_scenarios_in_attributes` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `scenario_id` int(21) NOT NULL,
  `attribute_id` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scenario_id_idx` (`scenario_id`)
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
  PRIMARY KEY (`id`),
  KEY `scenario_step_idx` (`scenario_id`,`parent_option_id`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_badges` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `lang_id` int(21) NOT NULL,
  `user_id` int(21) NOT NULL,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_lang_idx` (`user_id`,`lang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_loginlog` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `user_id` int(21) NOT NULL,
  `login_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`)
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
  `points` float(100,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `test_id_idx` (`test_id`),
  KEY `public_id_idx` (`public_id`)
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
  PRIMARY KEY (`id`),
  KEY `user_lang_idx` (`user_id`,`lang_id`),
  KEY `public_id_idx` (`public_id`)
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
  PRIMARY KEY (`id`),
  KEY `user_lang_idx` (`user_id`,`lang_id`),
  KEY `public_id_idx` (`public_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bei_users_knowledge_options` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `cat_id` int(21) NOT NULL,
  `test_id` int(21) NOT NULL,
  `public_id` varchar(45) NOT NULL,
  `user_id` int(21) NOT NULL,
  `question_id` int(21) NOT NULL,
  `correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `test_id_idx` (`test_id`)
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
  `simulation_type` tinyint(4) DEFAULT NULL COMMENT 'the mode chosen at the start: 1 - feedback after every choice, 2 - feedback listed at the end; NULL for runs started before it was kept',
  PRIMARY KEY (`id`),
  KEY `user_lang_idx` (`user_id`,`lang_id`),
  KEY `public_id_idx` (`public_id`)
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
  PRIMARY KEY (`id`),
  KEY `public_id_idx` (`user_scenario_public_id`(45))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ========================================================
--  Stored function
-- ========================================================

-- LMUsers::country_progress repeats these four shares,
-- expression for expression, in one statement over every
-- member and language, for the admin dashboard: calling this
-- function per pair made that page take over a second. A
-- change here has to be made there too; AdminDashboardTest
-- holds the two to the same figures.

/*!50003 DROP FUNCTION IF EXISTS `bei_user_course_progress` */;
DELIMITER ;;
CREATE FUNCTION `bei_user_course_progress`(userid INT, langid INT) RETURNS int(11)
BEGIN

	-- The hours of the lessons the course offers: those of the
	-- language filed under a category, the only ones a member page
	-- lists; deleting a category keeps its lessons with no category
	SELECT SUM(hours) AS total_hours INTO @total_hours FROM bei_lm_content WHERE bei_lm_content.lang_id=langid AND bei_lm_content.cat_id IS NOT NULL;

	-- The self-evaluations the course offers: one per category of
	-- the language that a member can take and finish — a lesson to
	-- open it from, the only way a page offers it, and a question a
	-- member can answer (SelfEval::scopeUsable)
	SELECT COUNT(*) INTO @total_self_eval_tests FROM bei_lm_category c WHERE c.lang_id = langid AND EXISTS (SELECT 1 FROM bei_lm_content l WHERE l.cat_id = c.id) AND EXISTS (SELECT 1 FROM bei_selfeval_question q WHERE q.cat_id = c.id AND q.q_type IN (1,2) AND EXISTS (SELECT 1 FROM bei_selfeval_option o WHERE o.question_id = q.id));

	-- The member's finished ones among them, one per category however
	-- often taken
	SELECT COUNT(DISTINCT bei_users_selfeval_test.cat_id) INTO @user_selfeval_tests FROM bei_users_selfeval_test WHERE bei_users_selfeval_test.lang_id = langid AND bei_users_selfeval_test.user_id=userid AND bei_users_selfeval_test.finished=1 AND bei_users_selfeval_test.cat_id IN (SELECT c.id FROM bei_lm_category c WHERE c.lang_id = langid AND EXISTS (SELECT 1 FROM bei_lm_content l WHERE l.cat_id = c.id) AND EXISTS (SELECT 1 FROM bei_selfeval_question q WHERE q.cat_id = c.id AND q.q_type IN (1,2) AND EXISTS (SELECT 1 FROM bei_selfeval_option o WHERE o.question_id = q.id)));

	-- The member's share of them, each lesson once however often its
	-- completion was stored: bei_lm_users has no unique key
	SELECT SUM(bei_lm_content.hours) AS users_hours INTO @users_hours FROM bei_lm_content WHERE bei_lm_content.lang_id = langid AND bei_lm_content.cat_id IS NOT NULL AND bei_lm_content.id IN (SELECT bei_lm_users.content_id FROM bei_lm_users WHERE bei_lm_users.user_id = userid);

	SELECT IF(COUNT(*)>0, 30, 0) INTO @knowledge_test FROM bei_users_knowledge_test WHERE bei_users_knowledge_test.finished=1 AND bei_users_knowledge_test.results >=75 AND bei_users_knowledge_test.user_id = userid AND lang_id = langid;

	-- The simulations the course offers: those of the language a
	-- category of the language lists, the only ones a member page
	-- offers (Scenarios::scopeOffered)
	SELECT COUNT(*) INTO @all_scenarios FROM bei_scenarios WHERE bei_scenarios.lang_id = langid AND EXISTS (SELECT 1 FROM bei_scenarios_in_category ic JOIN bei_scenarios_category c ON c.id = ic.cat_id WHERE ic.scenario_id = bei_scenarios.id AND ic.lang_id = langid AND c.lang_id = langid);

	-- The member's finished ones among them, each once
	SELECT COUNT(DISTINCT bei_users_scenarios.scenario_id) INTO @user_scenarios FROM bei_users_scenarios WHERE bei_users_scenarios.lang_id = langid AND bei_users_scenarios.finished=1 AND bei_users_scenarios.user_id = userid AND EXISTS (SELECT 1 FROM bei_scenarios s JOIN bei_scenarios_in_category ic ON ic.scenario_id = s.id JOIN bei_scenarios_category c ON c.id = ic.cat_id WHERE s.id = bei_users_scenarios.scenario_id AND s.lang_id = langid AND ic.lang_id = langid AND c.lang_id = langid);

	-- A share the language does not offer, or the member has not
	-- begun, is 0 rather than NULL, which would null the sum
	SET @total_course = IFNULL(ROUND(@users_hours * 100 / NULLIF(@total_hours, 0), 0) * 0.25, 0);
	SET @total_selfeval = IFNULL(ROUND(@user_selfeval_tests * 100 / NULLIF(@total_self_eval_tests, 0), 0) * 0.2, 0);
	SET @total_scenarios = IFNULL(ROUND(@user_scenarios * 100 / NULLIF(@all_scenarios, 0), 0) * 0.25, 0);

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

-- Main admin: admin@knf.vu.lt. The hash is of a random password nobody
-- kept, so no fresh install opens with a password anyone can guess. Set
-- one before the first sign-in: make a hash with
--   docker exec cyberphish-apache php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT), "\n";' '<the password>'
-- and store it in bei_managers.password of id 1.
INSERT INTO `bei_managers` (`id`, `email`, `password`, `main`, `status`, `country`)
VALUES (1, 'admin@knf.vu.lt', '$2y$10$5v2fzFVJ3hhVAuom9iD2Q.IrgZp9tXfrp/PCCiN8sH0CsXqtFpxiq', 1, '1', 0);

-- Settings, read by fixed ids: 1 the home page's about text and 3 the
-- knowledge test's questions per category, both JSON the panel's
-- settings page fills in; 2 the number of questions a self-evaluation
-- asks. The page saves with an UPDATE, so every row must exist
INSERT INTO `bei_settings` (`id`, `name`, `val1`, `val2`, `val3`) VALUES
  (1, 'home_about', NULL, NULL, 0),
  (2, 'self_eval_pertime', NULL, NULL, 5),
  (3, 'knowledge_tests', NULL, NULL, 0);


-- ========================================================
--  Grants
-- ========================================================

-- The backup services dump the database as the app's user, the
-- stored function included (mysqldump --routines). MySQL shows a
-- function's body only to its definer, root here, or to a user who
-- may read mysql.proc; to anyone else mysqldump stops at the
-- function with an error, and the dump has neither the function nor
-- the views. So every user granted on this database alone — the
-- image's MYSQL_USER — may read mysql.proc. A database set up before
-- this grant was added needs it by hand.
SET @grant_proc = (SELECT CONCAT('GRANT SELECT ON mysql.proc TO ', GROUP_CONCAT(CONCAT(QUOTE(User), '@', QUOTE(Host))))
  FROM mysql.db WHERE DATABASE() LIKE Db AND User <> '');
SET @grant_proc = IFNULL(@grant_proc, 'DO 0');
PREPARE grant_proc FROM @grant_proc;
EXECUTE grant_proc;
DEALLOCATE PREPARE grant_proc;


/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
