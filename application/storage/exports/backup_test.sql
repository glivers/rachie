-- Database Export
-- Database: kaimukunju
-- Generated: 2025-11-27 17:35:18
-- Tables: 4

SET FOREIGN_KEY_CHECKS=0;

--
-- Table: products
--

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No data in table

--
-- Table: tests
--

DROP TABLE IF EXISTS `tests`;

CREATE TABLE `tests` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `test_field` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No data in table

--
-- Table: testusers
--

DROP TABLE IF EXISTS `testusers`;

CREATE TABLE `testusers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No data in table

--
-- Table: users
--

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `username` varchar(255) NOT NULL,
  `id` int(11) unsigned NOT NULL auto_increment,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No data in table

SET FOREIGN_KEY_CHECKS=1;
