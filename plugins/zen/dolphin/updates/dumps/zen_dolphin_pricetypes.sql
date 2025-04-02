-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `zen_dolphin_pricetypes`;
CREATE TABLE `zen_dolphin_pricetypes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `zen_dolphin_pricetypes` (`id`, `name`, `code`, `sort_order`) VALUES
(1,	'Взрослый',	'взр',	0),
(2,	'Дополнительный',	'доп',	1),
(3,	'Детский',	'реб',	2),
(4,	'Детский дополнительный',	'доп реб',	3),
(5,	'Без места',	'без места',	4);

-- 2020-06-23 07:21:27
