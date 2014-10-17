CREATE TABLE IF NOT EXISTS `prefix_blacklist` (
  `content` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `date` datetime NOT NULL,
  `result` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`content`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;