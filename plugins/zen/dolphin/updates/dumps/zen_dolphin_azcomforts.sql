DROP TABLE IF EXISTS `zen_dolphin_azcomforts`;
CREATE TABLE `zen_dolphin_azcomforts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `zen_dolphin_azcomforts` (`id`, `name`, `sort_order`) VALUES
(1,	'С удобствами (санузел в номере)',	0),
(2,	'На блок (санузел на 2-3 номера)',	1),
(3,	'На этаже (санузел на этаже)',	2),
(4,	'Без удобств (санузел на территории)',	3);
