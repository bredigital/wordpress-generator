CREATE TABLE IF NOT EXISTS `wpmgr_sitelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `secure` tinyint(1) NOT NULL DEFAULT '0',
  `extensiondays` int(11) NOT NULL DEFAULT '0',
  `emailreminder` tinyint(4) NOT NULL DEFAULT '0',
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` varchar(45) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `deleted_by` varchar(45) DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
