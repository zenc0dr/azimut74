-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `mcmraak_sans_meals`;
CREATE TABLE `mcmraak_sans_meals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `mcmraak_sans_meals` (`id`, `code`, `name`) VALUES
(1,	'RO',	'Без питания'),
(2,	'BB',	'Завтрак'),
(3,	'HB',	'2х разовое'),
(4,	'FB',	'3х разовое'),
(5,	'AI',	'Все включено');

-- 2018-01-15 13:23:46
