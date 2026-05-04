-- --------------------------------------------------------
-- Host:                         192.168.1.2
-- Server version:               10.1.25-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win32
-- HeidiSQL Version:             9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for warcry
CREATE DATABASE IF NOT EXISTS `warcry` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `warcry`;

-- Dumping structure for table warcry.account_data
CREATE TABLE IF NOT EXISTS `account_data` (
  `id` bigint(20) NOT NULL,
  `displayName` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `silver` int(10) NOT NULL DEFAULT '0',
  `gold` int(10) NOT NULL DEFAULT '0',
  `cooldowns` text COLLATE latin1_general_ci NOT NULL,
  `socialData` text COLLATE latin1_general_ci NOT NULL,
  `birthday` varchar(12) COLLATE latin1_general_ci NOT NULL COMMENT 'MM/DD/YYYY',
  `gender` varchar(10) COLLATE latin1_general_ci NOT NULL,
  `country` varchar(2) COLLATE latin1_general_ci NOT NULL DEFAULT 'US',
  `secretQuestion` tinyint(3) NOT NULL DEFAULT '0',
  `secretAnswer` varchar(40) COLLATE latin1_general_ci NOT NULL,
  `avatar` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `avatarType` tinyint(2) NOT NULL DEFAULT '0',
  `rank` tinyint(2) NOT NULL DEFAULT '0',
  `last_ip` varchar(30) COLLATE latin1_general_ci NOT NULL DEFAULT '0.0.0.0',
  `admin_last_ip` varchar(30) COLLATE latin1_general_ci NOT NULL DEFAULT '0.0.0.0',
  `reg_ip` varchar(30) COLLATE latin1_general_ci NOT NULL DEFAULT '0.0.0.0',
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login2` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `admin_last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `admin_last_login2` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` enum('active','disabled','pending') COLLATE latin1_general_ci NOT NULL DEFAULT 'pending',
  `event` varchar(150) COLLATE latin1_general_ci NOT NULL DEFAULT 'NONE',
  `salt` varchar(40) COLLATE latin1_general_ci NOT NULL,
  `selected_realm` tinyint(2) NOT NULL,
  `bt_milestone` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.account_data: 0 rows
/*!40000 ALTER TABLE `account_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_data` ENABLE KEYS */;

-- Dumping structure for table warcry.acp_permissions
CREATE TABLE IF NOT EXISTS `acp_permissions` (
  `id` bigint(20) NOT NULL,
  `1` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Give Permissions',
  `2` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'News Management',
  `3` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Articles Management',
  `4` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Premium Store Management',
  `5` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Movies',
  `6` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Screenshots',
  `7` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Forums',
  `8` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Forum Categories',
  `9` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Preview Logs',
  `10` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Promo Codes',
  `11` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Preview In-Game Tickets',
  `12` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Preview Bug Reports',
  `13` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Bug Reports',
  `14` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Preview Users',
  `15` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manage Item Store.',
  `16` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Allow changing users rank.',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.acp_permissions: 0 rows
/*!40000 ALTER TABLE `acp_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `acp_permissions` ENABLE KEYS */;

-- Dumping structure for table warcry.armorsets
CREATE TABLE IF NOT EXISTS `armorsets` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `realm` varchar(10) COLLATE latin1_general_ci NOT NULL DEFAULT '-1',
  `category` int(10) NOT NULL DEFAULT '0',
  `price` int(10) NOT NULL DEFAULT '0',
  `tier` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `class` int(10) NOT NULL DEFAULT '0',
  `type` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `items` varchar(500) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.armorsets: 0 rows
/*!40000 ALTER TABLE `armorsets` DISABLE KEYS */;
/*!40000 ALTER TABLE `armorsets` ENABLE KEYS */;

-- Dumping structure for table warcry.armorset_categories
CREATE TABLE IF NOT EXISTS `armorset_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.armorset_categories: 0 rows
/*!40000 ALTER TABLE `armorset_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `armorset_categories` ENABLE KEYS */;

-- Dumping structure for table warcry.articles
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL,
  `short_text` varchar(350) NOT NULL,
  `text` text NOT NULL,
  `views` int(10) NOT NULL DEFAULT '0',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` int(20) NOT NULL DEFAULT '0',
  `image` varchar(150) NOT NULL,
  `comments` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='Contains the Forums.';

-- Dumping data for table warcry.articles: 1 rows
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` (`id`, `title`, `short_text`, `text`, `views`, `added`, `author`, `image`, `comments`) VALUES
	(12, 'Testign we', 'hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw ', 'hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw hahash hasdh ahhahhahah asqt ete te eqq wqw qwqw', 12, '2013-03-03 20:39:51', 2, '21e7c_xp.png', 1);
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;

-- Dumping structure for table warcry.article_comments
CREATE TABLE IF NOT EXISTS `article_comments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` int(20) NOT NULL DEFAULT '0',
  `article` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=135 DEFAULT CHARSET=utf8 COMMENT='Contains the Forums.';

-- Dumping data for table warcry.article_comments: 0 rows
/*!40000 ALTER TABLE `article_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `article_comments` ENABLE KEYS */;

-- Dumping structure for table warcry.bugtracker
CREATE TABLE IF NOT EXISTS `bugtracker` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` int(11) NOT NULL DEFAULT '0',
  `title` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `content` text COLLATE latin1_general_ci NOT NULL,
  `maincategory` tinyint(2) NOT NULL DEFAULT '0',
  `category` tinyint(2) NOT NULL DEFAULT '0',
  `subcategory` tinyint(2) NOT NULL DEFAULT '0',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `priority` tinyint(1) NOT NULL DEFAULT '0',
  `approval` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.bugtracker: 0 rows
/*!40000 ALTER TABLE `bugtracker` DISABLE KEYS */;
/*!40000 ALTER TABLE `bugtracker` ENABLE KEYS */;

-- Dumping structure for table warcry.changelogs
CREATE TABLE IF NOT EXISTS `changelogs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `revision` mediumint(8) unsigned NOT NULL,
  `changelog` tinyint(2) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `author` varchar(150) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='Item System';

-- Dumping data for table warcry.changelogs: 0 rows
/*!40000 ALTER TABLE `changelogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `changelogs` ENABLE KEYS */;

-- Dumping structure for table warcry.coin_activity
CREATE TABLE IF NOT EXISTS `coin_activity` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `source` varchar(150) COLLATE latin1_general_ci NOT NULL DEFAULT 'NONE',
  `sourceType` tinyint(1) NOT NULL DEFAULT '0',
  `coinsType` tinyint(1) NOT NULL DEFAULT '1',
  `exchangeType` tinyint(1) NOT NULL DEFAULT '1',
  `amount` int(10) NOT NULL DEFAULT '0',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.coin_activity: 0 rows
/*!40000 ALTER TABLE `coin_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `coin_activity` ENABLE KEYS */;

-- Dumping structure for table warcry.images
CREATE TABLE IF NOT EXISTS `images` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL DEFAULT 'Undefined',
  `descr` text COLLATE latin1_general_ci NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account` int(10) NOT NULL DEFAULT '0',
  `image` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `type` tinyint(3) NOT NULL DEFAULT '0',
  `status` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`account`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.images: 0 rows
/*!40000 ALTER TABLE `images` DISABLE KEYS */;
/*!40000 ALTER TABLE `images` ENABLE KEYS */;

-- Dumping structure for table warcry.movies
CREATE TABLE IF NOT EXISTS `movies` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL DEFAULT 'Undefined',
  `short_text` varchar(115) COLLATE latin1_general_ci NOT NULL,
  `descr` text COLLATE latin1_general_ci NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account` int(10) NOT NULL DEFAULT '0',
  `dirname` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `image` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `mp4` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `webm` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `ogg` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `youtube` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`account`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.movies: 0 rows
/*!40000 ALTER TABLE `movies` DISABLE KEYS */;
/*!40000 ALTER TABLE `movies` ENABLE KEYS */;

-- Dumping structure for table warcry.news
CREATE TABLE IF NOT EXISTS `news` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) COLLATE latin1_general_ci NOT NULL DEFAULT 'Undefined',
  `image` varchar(150) COLLATE latin1_general_ci NOT NULL DEFAULT 'none',
  `text` text COLLATE latin1_general_ci NOT NULL,
  `shortText` varchar(500) COLLATE latin1_general_ci NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` bigint(20) NOT NULL,
  `authorStr` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT 'Admin',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.news: 1 rows
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` (`id`, `title`, `image`, `text`, `shortText`, `added`, `author`, `authorStr`) VALUES
	(1, 'Welcome!', 'default.png', 'Thanks for using WarCry-CMS\r\n', 'Welcome to our project.', '2017-09-01 11:28:03', 2, 'Shadow');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;

-- Dumping structure for table warcry.paymentwall_logs
CREATE TABLE IF NOT EXISTS `paymentwall_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `TransactionAmount` int(10) NOT NULL,
  `TransactionType` int(1) NOT NULL DEFAULT '0',
  `TransactionRefId` varchar(12) COLLATE latin1_general_ci NOT NULL,
  `TransactionQuery` text COLLATE latin1_general_ci NOT NULL,
  `text` text COLLATE latin1_general_ci NOT NULL,
  `type` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.paymentwall_logs: 0 rows
/*!40000 ALTER TABLE `paymentwall_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `paymentwall_logs` ENABLE KEYS */;

-- Dumping structure for table warcry.paypal_logs
CREATE TABLE IF NOT EXISTS `paypal_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `txn_id` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `txn_type` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `amount` int(10) NOT NULL,
  `payer_email` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `receiver_email` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paypal_status` varchar(150) COLLATE latin1_general_ci NOT NULL,
  `query_string` text COLLATE latin1_general_ci NOT NULL,
  `text` text COLLATE latin1_general_ci NOT NULL,
  `type` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.paypal_logs: 0 rows
/*!40000 ALTER TABLE `paypal_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `paypal_logs` ENABLE KEYS */;

-- Dumping structure for table warcry.promo_codes
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `token` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `usage` tinyint(2) NOT NULL DEFAULT '0',
  `reward_type` tinyint(2) NOT NULL DEFAULT '0',
  `reward_value` int(10) NOT NULL DEFAULT '0',
  `format` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.promo_codes: 0 rows
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;

-- Dumping structure for table warcry.promo_codes_usage
CREATE TABLE IF NOT EXISTS `promo_codes_usage` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `token` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `account` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.promo_codes_usage: 0 rows
/*!40000 ALTER TABLE `promo_codes_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes_usage` ENABLE KEYS */;

-- Dumping structure for table warcry.purchase_log
CREATE TABLE IF NOT EXISTS `purchase_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `source` varchar(150) COLLATE latin1_general_ci NOT NULL DEFAULT 'NONE',
  `text` text COLLATE latin1_general_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('ok','error','pending') COLLATE latin1_general_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.purchase_log: 0 rows
/*!40000 ALTER TABLE `purchase_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_log` ENABLE KEYS */;

-- Dumping structure for table warcry.raf_hash
CREATE TABLE IF NOT EXISTS `raf_hash` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `hash` varchar(150) COLLATE latin1_general_ci NOT NULL DEFAULT 'NONE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`account`,`hash`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.raf_hash: 0 rows
/*!40000 ALTER TABLE `raf_hash` DISABLE KEYS */;
/*!40000 ALTER TABLE `raf_hash` ENABLE KEYS */;

-- Dumping structure for table warcry.raf_links
CREATE TABLE IF NOT EXISTS `raf_links` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `recruiter` bigint(20) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cDate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Link completion date.',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `statusText` varchar(250) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.raf_links: 0 rows
/*!40000 ALTER TABLE `raf_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `raf_links` ENABLE KEYS */;

-- Dumping structure for table warcry.realm_stats
CREATE TABLE IF NOT EXISTS `realm_stats` (
  `RealmID` tinyint(2) NOT NULL,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `horde` int(10) NOT NULL DEFAULT '0',
  `alliance` int(10) NOT NULL DEFAULT '0',
  `bloodelfs` int(10) NOT NULL DEFAULT '0',
  `draeneis` int(10) NOT NULL DEFAULT '0',
  `dwarfs` int(10) NOT NULL DEFAULT '0',
  `gnomes` int(10) NOT NULL DEFAULT '0',
  `goblins` int(10) NOT NULL DEFAULT '0',
  `humans` int(10) NOT NULL DEFAULT '0',
  `nightelfs` int(10) NOT NULL DEFAULT '0',
  `orcs` int(10) NOT NULL DEFAULT '0',
  `taurens` int(10) NOT NULL DEFAULT '0',
  `trolls` int(10) NOT NULL DEFAULT '0',
  `undeads` int(10) NOT NULL DEFAULT '0',
  `worgens` int(10) NOT NULL DEFAULT '0',
  `deathknights` int(10) NOT NULL DEFAULT '0',
  `druids` int(10) NOT NULL DEFAULT '0',
  `hunters` int(10) NOT NULL DEFAULT '0',
  `mages` int(10) NOT NULL DEFAULT '0',
  `paladins` int(10) NOT NULL DEFAULT '0',
  `priests` int(10) NOT NULL DEFAULT '0',
  `rogues` int(10) NOT NULL DEFAULT '0',
  `shamans` int(10) NOT NULL DEFAULT '0',
  `warlocks` int(10) NOT NULL DEFAULT '0',
  `warriors` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`RealmID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.realm_stats: 0 rows
/*!40000 ALTER TABLE `realm_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `realm_stats` ENABLE KEYS */;

-- Dumping structure for table warcry.recovery
CREATE TABLE IF NOT EXISTS `recovery` (
  `account` bigint(20) NOT NULL,
  `key` varchar(200) CHARACTER SET utf8 NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `UNIQUE` (`account`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.recovery: 0 rows
/*!40000 ALTER TABLE `recovery` DISABLE KEYS */;
/*!40000 ALTER TABLE `recovery` ENABLE KEYS */;

-- Dumping structure for table warcry.refundable_items
CREATE TABLE IF NOT EXISTS `refundable_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entry` int(10) NOT NULL DEFAULT '0',
  `price` int(10) NOT NULL DEFAULT '0',
  `currency` tinyint(2) NOT NULL DEFAULT '0',
  `character` int(10) NOT NULL DEFAULT '0',
  `account` bigint(10) NOT NULL DEFAULT '0',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(2) NOT NULL DEFAULT '0',
  `timeRefunded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `error` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.refundable_items: 0 rows
/*!40000 ALTER TABLE `refundable_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `refundable_items` ENABLE KEYS */;

-- Dumping structure for table warcry.reserved_emails
CREATE TABLE IF NOT EXISTS `reserved_emails` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `application` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `key` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expire` varchar(100) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`email`,`application`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.reserved_emails: 0 rows
/*!40000 ALTER TABLE `reserved_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `reserved_emails` ENABLE KEYS */;

-- Dumping structure for table warcry.store_activity
CREATE TABLE IF NOT EXISTS `store_activity` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `source` varchar(150) COLLATE latin1_general_ci NOT NULL DEFAULT 'NONE',
  `text` text COLLATE latin1_general_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `itemId` int(11) NOT NULL,
  `money` varchar(150) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.store_activity: 0 rows
/*!40000 ALTER TABLE `store_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `store_activity` ENABLE KEYS */;

-- Dumping structure for table warcry.store_items
CREATE TABLE IF NOT EXISTS `store_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `entry` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `realm` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0',
  `gold` int(10) NOT NULL DEFAULT '10',
  `silver` int(10) NOT NULL DEFAULT '30',
  `class` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `subclass` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `displayid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Quality` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `Flags` bigint(20) NOT NULL DEFAULT '0',
  `InventoryType` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `AllowableClass` int(11) NOT NULL DEFAULT '-1',
  `ItemLevel` smallint(5) unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL DEFAULT '',
  `hits` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `items_index` (`class`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='Item System';

-- Dumping data for table warcry.store_items: 0 rows
/*!40000 ALTER TABLE `store_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `store_items` ENABLE KEYS */;

-- Dumping structure for table warcry.text_captcha
CREATE TABLE IF NOT EXISTS `text_captcha` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionHash` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `question` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `answers` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`questionHash`)
) ENGINE=MyISAM AUTO_INCREMENT=133 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.text_captcha: 132 rows
/*!40000 ALTER TABLE `text_captcha` DISABLE KEYS */;
INSERT INTO `text_captcha` (`id`, `questionHash`, `question`, `answers`) VALUES
	(1, 'ceb76b9adcd6c3871c35f12ffafb7851', 'Which digit is 6th in the number 5293305?', 'cfcd208495d565ef66e7dff9f98764da\nd02c4c4cde7ae76252540d116a40f23a\n'),
	(2, '9a412edb464bdd31e69fbfaef07b3a2b', 'What is the 3rd colour in the list purple, pink, sock, hospital, mosquito and red?', 'bda9643ac6601722a28f238714274da4\n'),
	(3, 'a75505530c0c9043832f0348e0bf4256', 'What is sixty nine thousand nine hundred and thirty one as a number?', '203e3ead1e15e2d2bfc58c7a5e6a0042\n'),
	(4, '305e5f3b73ae51b628a328c24c1952dd', 'Enter the number twenty nine thousand two hundred and eighteen in digits:', '337751565e513506b6400ca2ad6ff5df\n'),
	(5, 'ea0ea865d0d22c47e6c4a1cd2ebb9b02', 'Enter the biggest number of seventy, 34, 83, two or seventeen:', 'fe9fc289c3ff0af142b6d3bead98a923\n6f7b8d41fa8a42bd4ccae708a1470455\n'),
	(6, 'd9651c291f662ce7caa4a037a633d64a', 'If a person is called Charles, what is their name?', 'a5410ee37744c574ba5790034ea08f79\n'),
	(7, '5783c729a04f5ff33fb3738f131bbcf3', 'Which digit is 2nd in the number 3375784?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(8, '66eed7d0025dad703400cc3eec97be20', 'Purple, tracksuit, brown, red, green and black: how many colours in the list?', 'e4da3b7fbbce2345d7772b0674a318d5\n30056e1cab7a61d256fc8edd970d14f5\n'),
	(9, 'b309300f2976175111df2f57f067778a', 'What is the 1st number in the list twenty two, two and twenty eight?', 'b6d767d2f8ed5d21a44b0e5886680cb9\nca5ae332948acff18d263f1d3b02891c\n'),
	(10, 'dcfcc52bc393bcfbb8304eb682c8b12a', 'Enter the number twenty thousand five hundred and twenty three in digits:', '226982ab9123047f22996f01ae642335\n'),
	(11, '78837539dfd63164312408ae90120ce3', 'The 1st colour in yellow, hotel and black is?', 'd487dd0b55dfcacdd920ccbdaeafa351\n'),
	(12, '7c9f20a546b6bd5e42c5f50d628820f8', 'The list school, fruit and face contains how many body parts?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(13, '4205120d3989aa163163739c70620043', 'Brown, ear, shirt, head, red and white: how many colours in the list?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(14, 'e51fb4b967a1699d5379258ce0881b63', 'The name of Mark is?', 'ea82410c7a9991816b5eeeebe195e20a\n'),
	(15, '2c2d8d54dc74acea6bce668dcf313513', 'Which digit is 1st in the number 360254?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(16, '6245bb8db858999f37bc14b419028959', 'What is 1 + four?', 'e4da3b7fbbce2345d7772b0674a318d5\n30056e1cab7a61d256fc8edd970d14f5\n'),
	(17, 'b7676cb778add7a1832870a8d85ec562', 'The list green, hotel and purple contains how many colours?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(18, '98cd0b07babfbecbc6a6205b87ab064c', 'The 1st number from eight, eleven, 25 and thirty three is?', 'c9f0f895fb98ab9159f51fd0297e236d\n24d27c169c2c881eb09a065116f2aa5c\n'),
	(19, '3ae53ee5782e191d38dab46501122f24', 'Which digit is 7th in the number 1678865?', 'e4da3b7fbbce2345d7772b0674a318d5\n30056e1cab7a61d256fc8edd970d14f5\n'),
	(20, '56f307d8c21b507f295b675475920853', 'How many colours in the list horse, coat, pink and tooth?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(21, 'bd2e8f47a5d1a8d8402d557c95df5e9e', 'Enter the number ninety three thousand eight hundred in digits:', 'eaa42cbadae668c5640b6651ef54dbd1\n'),
	(22, 'ca393a0e82cfb82a138698d115ae844f', 'What is the 3rd colour in the list shorts, pink, white, green and black?', '9f27410725ab8cc8854a2769c7a516b8\n'),
	(23, 'e29b50e8a93abd147962b9fdca35f4d4', 'Enter the number eight thousand one hundred and thirty five in digits:', '518fc66deea9d064d0a92eb73e4ea61b\n'),
	(24, '36debd3c9d53e561e00a13088009cab8', 'How many colours in the list brown, fruit and purple?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(25, 'e3234e22ba522ee865fc48ff961c0e41', 'What is the 3rd digit in 2220864?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(26, 'db3afa91ff165461f31994dc1d3ee14c', 'What is Steven\'s name?', '6ed61d4b80bb0f81937b32418e98adca\n'),
	(27, '23352c79e7e96b925e0636f8b95571dd', 'Heart, glove, green, coat and chest: how many colours in the list?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(28, 'a3a7fd9febbf4bcf19b4bb3dffddef17', 'The black butter is what colour?', '1ffd9e753c8054cc61456ac7fac1ac89\n'),
	(29, '54dc56ab6e33751b378fcabd5ab7ea4f', 'The list chest, sweatshirt, penguin and stomach contains how many body parts?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(30, '0be2abd83d36cb6c626c87dc4167deaa', 'Ruth\'s name is?', '81ea66d57d6b827ef722f4f20f8a669c\n'),
	(31, '115bad7512614125531c9c2a0fa03bf0', 'If the house is red, what colour is it?', 'bda9643ac6601722a28f238714274da4\n'),
	(32, '29a11cb7396cd59806554964daacf2a3', 'Thirty eight, fifteen, fifteen, 11, sixty six or 18: which of these is the highest?', '3295c76acbf4caaed33c36b1b5fc2cb1\n32e335843903cc86e517162a943202e2\n'),
	(33, 'ea39e586d25c6fe47c337f23b8dfdb02', 'Enter the number ninety four thousand one hundred and fifty eight in digits:', '563ba7d5a3c877fbc24c7d15f6f46ec3\n'),
	(34, 'bffc22002398b36b1d448be48d8fcaff', 'Sixteen minus 3 = ?', 'c51ce410c124a10e0db5e4b97fc2af39\n422ecc084f2458defc620ecebf2a6448\n'),
	(35, '88ba1dcb84c96dc077947a075735c33b', 'Thomas\' name is?', 'ef6e65efc188e7dffd7335b646a85a21\n'),
	(36, '993f94ae0a2025d90dc89d816cb99611', 'If a person is called John, what is their name?', '527bd5b5d689e2c32ae974c6229ff785\n'),
	(37, 'ad3ac43fe4bf94003a5705ae3f6de388', 'Chris\' name is?', '6b34fe24ac2ff8103f6fce1f0da2ef57\n'),
	(38, 'b2ee0dee5e21b13167d9697a9e6eefb0', 'If a person is called David, what is their name?', '172522ec1028ab781d9dfd17eaca4427\n'),
	(39, 'ae7d25035bbdc2cf471f74b01f23e9bb', 'What number is 3rd in the series thirty three, 3 and 24?', '1ff1de774005f8da13f42943881c655f\nc2ca890c0e490dfef1f20c9048379b1b\n'),
	(40, '8998fc8a2d3aae965ac552a6bdea43db', 'Which of 75, 92 or 96 is the largest?', '26657d5ff9020d2abefe558796b99584\nf587ad36ac902b147b816c4479a1bafc\n'),
	(41, '273d64c7ea4dd87fbcaa7d98a372cb31', 'Ten add 7 is what?', '70efdf2ec9b086079795c442636b55fb\n2e457d2f9f38af419f5a34092cba8438\n'),
	(42, '70158ad19d88aac307df498da288fc6b', 'Enter the number four thousand six hundred and eighteen in digits:', 'b9a8f4af85454f7c56c06f0a39e7ec23\n'),
	(43, '1bd7c8b1cf567b0b17b4839de77b95a3', 'What is 8 + four?', 'c20ad4d76fe97759aa27a0c99bff6710\n15f6f8dc036519d7fe15b39338f6e5db\n'),
	(44, '8b7f43524551d7ec238be3f2e0e88351', 'The number of body parts in the list leg, monkey, duck, elephant and prison is?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(45, '3db47146d9bcfefd97421648ae95f0b9', 'If the church is white, what colour is it?', 'd508fe45cecaf653904a0e774084bb5c\n'),
	(46, 'bf95f246f174d13d1f7b2e8e46d73393', '6 plus eight is what?', 'aab3238922bcc25a6f606eb525ffdc56\n279e962ea623aa2a3a86739622772e1f\n'),
	(47, 'af2c4ed82b214dd5668de4714b8cc76a', 'What is Carol\'s name?', 'a9a0198010a6073db96434f6cc5f22a8\n'),
	(48, 'c66f6500c54396512c5a84a7a10d822c', 'Enter the highest number of 75, forty four, sixty eight or 49:', 'd09bf41544a3365a46c9077ebb5e35c3\n8c40dc7360f4ca6842fe19f8e6f9efd3\n'),
	(49, '3cb4c3fccd6048566ab8894356cb0842', 'If the hospital is blue, what colour is it?', '48d6215903dff56238e52e8891380c8f\n'),
	(50, '93a6c20ac2862a6bb203798c5a8c3b59', 'If a person is called Chris, what is their name?', '6b34fe24ac2ff8103f6fce1f0da2ef57\n'),
	(51, '4d0d2fe5521d3ebb9db9b240c4b93466', 'What number is 4th in the series twenty one, thirty eight, 29 and eleven?', '6512bd43d9caa6e02c990b0a82652dca\n9c8454ddf7aa50116496bac348d7550d\n'),
	(52, '33fff33a240eee90fd9b88239fbc62e8', 'Enter the number seventy nine thousand two hundred and sixty five in digits:', 'ac15984c1be6387e4ba4aa19e947ffa7\n'),
	(53, '3fd99b3aa7c3f1df4c83f58b0ef18edb', '75, 19, 78, ninety three, twenty six or twenty seven: the highest is?', '98dce83da57b0395e163467c9dae521b\n651ad37b6be94369f61ad4ac1b37e078\n'),
	(54, '84f32fb506640ee49df64678ac6f8230', 'Green, purple, black, mouse and dress: how many colours in the list?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(55, 'a9281a1ff8ec07f6d6039095b2dac741', 'The 2nd number from twenty, 3, nine and 32 is?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(56, '4572d4f109332344ad41891fa1f5ff28', 'What number is 3rd in the series 9, thirty nine, twelve, twenty four and 6?', 'c20ad4d76fe97759aa27a0c99bff6710\n15f6f8dc036519d7fe15b39338f6e5db\n'),
	(57, 'b5e260d74c90d199d9ab17be90127798', 'What is two thousand nine hundred and thirteen as a number?', 'b31df16a88ce00fed951f24b46e08649\n'),
	(58, 'edd78c34dc6982dceaad70e9b8e2b294', 'The red dog is what colour?', 'bda9643ac6601722a28f238714274da4\n'),
	(59, 'e6df18a53d97d77b463dd65e99404b85', 'The name of Sandra is?', 'f40a37048732da05928c3d374549c832\n'),
	(60, '2e2ddced54855c6aa13e645f60ce6f52', 'The list library, sock, blue and fruit contains how many colours?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(61, '17560dfa03d6c5e1101495f8d3b8e3a1', 'If the coffee is white, what colour is it?', 'd508fe45cecaf653904a0e774084bb5c\n'),
	(62, 'fb5aab44396fc792cc12014995b1a3cf', 'The colour of a green bank is?', '9f27410725ab8cc8854a2769c7a516b8\n'),
	(63, 'd87b8097c101c0da0d9c24adf5d2a037', 'Susan\'s name is?', 'ac575e3eecf0fa410518c2d3a2e7209f\n'),
	(64, 'e5a1387c2729941393bc1fbfd5b86e31', 'Which digit is 6th in the number 5481744?', 'a87ff679a2f3e71d9181a67b7542122c\n8cbad96aced40b3838dd9f07f6ef5772\n'),
	(65, '70ea087ad182bba0cdbc5228b5f3110c', 'The 1st colour in blue, cat, black and cake is?', '48d6215903dff56238e52e8891380c8f\n'),
	(66, 'c8f3807b0218191b571c0c27365c3362', 'Enter the number eighty seven thousand six hundred and forty two in digits:', 'fb567f305d2c559d77410e28b4b49bf0\n'),
	(67, 'ccebaf393237f209d0e2e4f844b53eb8', 'The number of body parts in the list stomach, bee and apple is?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(68, '182b4a0c32d5fdd2df2802e45bf18a83', 'Brown, blue, penguin, elephant, red and sweatshirt: how many colours in the list?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(69, 'c4903128d0adefda394f4fe0522e10b5', 'What is sixty four thousand nine hundred and twelve as a number?', '1f2f664e68a603b3c54890fbbcd37857\n'),
	(70, '352828d4203a8bca32c7a8af3bf6492d', 'Which digit is 1st in the number 5809024?', 'e4da3b7fbbce2345d7772b0674a318d5\n30056e1cab7a61d256fc8edd970d14f5\n'),
	(71, '163d0ae7817bf31768b374cef1d31032', 'Which digit is 7th in the number 9376993?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(72, '060b79bc64459155cfc26377bd048def', 'Eighty, fifty four, 57, 7, fifty nine or thirty nine: which of these is the largest?', 'f033ab37c30201f73f142449d037028d\nb82bb8f7ebdd7b8d823dcfba622ea1b7\n'),
	(73, 'fe37516b4c7caff90cb3fc72a1fd1b9e', 'How many colours in the list library, pink and yellow?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(74, 'b42f749cdd68a04366e671cb2bc05028', 'Enter the biggest number of forty three, 70, 16, 57, seven or seventy:', '7cbbc409ec990f19c78c75bd1e06f215\n5456a19d6c110eaa6f65778cf4a98478\n'),
	(75, 'cad6b68b4c6563f441e00fc76b9bb127', 'The colour of a brown nose is?', '6ff47afa5dc7daa42cc705a03fca8a9b\n'),
	(76, '46f06e953d4f0843e89badc4737e49e0', '86, one, 24 or 25: which of these is the highest?', '93db85ed909c13838ff95ccfa94cebd9\ne9e234541cdc6ab9a9d59d03c4e02be4\n'),
	(77, '30ffe1906cf501a04a8600e32a571e62', 'Dog, restaurant, sweatshirt, brain and finger: how many body parts in the list?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(78, 'a128f7dcadb10d4abebf7de17f680e54', 'Black, yellow, chips and tooth: the 1st colour is?', '1ffd9e753c8054cc61456ac7fac1ac89\n'),
	(79, 'e8da2ba8ce0fb1a3b7d833b881b5d546', 'The purple church is what colour?', 'bb7aedfa61007447dd6efaf9f37641e3\n'),
	(80, '2cca0cd405f1a0447594377e220b2d99', 'What is eighty seven thousand five hundred and twenty four as a number?', '527d10c49a0e170c47b750c5c9dd850a\n'),
	(81, '531617f7b9809ea9697efd88af525bd3', 'Elephant, nose and milk: how many body parts in the list?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(82, '117de0060c7ae5f7320548332078fe89', 'Enter the biggest number of twenty, forty three, seventeen, eighty six, ten or eighty two:', '93db85ed909c13838ff95ccfa94cebd9\ne9e234541cdc6ab9a9d59d03c4e02be4\n'),
	(83, '21b0edb1cfd7e2e0409769ee2c7f2178', '15, 8, one, thirteen and 26: the 1st number is?', '9bf31c7ff062936a96d3c8bd1f8f2ff3\n92a2132e0190d6e582f13376ddc660d5\n'),
	(84, 'b392579fc4bdeccae6b7859064c181a4', '7, seventy two or thirty two: which of these is the largest?', '32bb90e8976aab5298d5da10fe66f21d\n24e9da0a826bf1ea5d173e605b8e7d32\n'),
	(85, '108d7a7ea5f223a7ea03461ac9dd9928', 'Green, pink and soup: how many colours in the list?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(86, '2760225cf23b0ec1c1073e10fd11f9af', 'Pink, purple, black and coffee: the 3rd colour is?', '1ffd9e753c8054cc61456ac7fac1ac89\n'),
	(87, '56fae2d97e2114ddb5bd8425eabd41d9', 'Brian\'s name is?', 'cbd44f8b5b48a51f7dab98abcdf45d4e\n'),
	(88, '29febfc21cf2d09a506a681fe8deecb5', 'The number of body parts in the list hand, bank, shorts, horse and shirt is?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(89, '8c03c54848bebcac9517f9b17de8de98', 'The 5th colour in purple, brown, hospital, green, blue and black is?', '1ffd9e753c8054cc61456ac7fac1ac89\n'),
	(90, '0fe020d32b5a56dcde0326706c7033e6', 'Elizabeth\'s name is?', '4af09080574089cbece43db636e2025f\n'),
	(91, 'a384782ee348186103a3b67049daefa9', 'If the soup is purple, what colour is it?', 'bb7aedfa61007447dd6efaf9f37641e3\n'),
	(92, '934a6cf91c16d7a8d3e5241b545993f0', 'The list elbow, hand and ant contains how many body parts?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(93, '1758d0487c7308edb65eef959b7e1d2e', 'Twenty eight, 25, 6, thirty nine and 28: the 4th number is?', 'd67d8ab4f4c10bf22aa353e27879133c\nf843a7c09355077734a9c7534cfb03ec\n'),
	(94, 'a95d9e48964e035cc919913fe10ecd7f', 'The brown house is what colour?', '6ff47afa5dc7daa42cc705a03fca8a9b\n'),
	(95, '4662d1b0949988386d8015750043651a', 'Rice, soup, cat, prison, black and green: the 2nd colour is?', '9f27410725ab8cc8854a2769c7a516b8\n'),
	(96, 'f8084126185272a16b9ad7c8652a37b7', 'The 3rd number from thirty two, thirty nine and 35 is?', '1c383cd30b7c298ab50293adfecb7b18\nd8a1e6b3abf174b09407948ad6a8f099\n'),
	(97, 'fa3f6d436b00c5a6110b881e44571aad', 'If a person is called Ruth, what is their name?', '81ea66d57d6b827ef722f4f20f8a669c\n'),
	(98, '10c3567441eb9cf91e2f95fb7d7285c8', 'John\'s name is?', '527bd5b5d689e2c32ae974c6229ff785\n'),
	(99, 'd4265f36880e93f3a1837d944f0ff731', 'What is the 5th number in the list two, twenty eight, thirty one, twenty four and nineteen?', '1f0e3dad99908345f7439f8ffabdffc4\n1d56cec552bf111de57687e4b5f8c795\n'),
	(100, '4b9114f151e7758355ae0c80dfa68447', 'Of the numbers sixty, 8, ninety nine or seventy, which is the largest?', 'ac627ab1ccbdb62ec96e702f07f6425b\n2ae4ce28ddad5e9cf9125c25795d0be0\n'),
	(101, '3ea335658da8d718ed968c6a1e77f2dd', 'What number is 2nd in the series eleven, 35 and 27?', '1c383cd30b7c298ab50293adfecb7b18\nd8a1e6b3abf174b09407948ad6a8f099\n'),
	(102, 'f20b5a563dd2b5b67e44c0faf4a7f04a', 'What is the 1st number in the list thirteen, 32, twenty eight, twenty five and 22?', 'c51ce410c124a10e0db5e4b97fc2af39\n422ecc084f2458defc620ecebf2a6448\n'),
	(103, 'ca1a63b334733d15c5f3a0bc3891ac66', 'Of the numbers seventy three, forty three, five, forty six, fifty two or 72, which is the highest?', 'd2ddea18f00665ce8623e36bd4e3c7c5\n389b435b7ae31188449dd2b43f25a339\n'),
	(104, '36f5bbaa0890db69d801b5eddd9a3f35', 'Which of 61, sixty eight, 68, 67, 40 or fifty eight is the biggest?', 'a3f390d88e4c41f2747bfa2f1b5f87db\nf92e5b0180df749f878b302f4ced4f7a\n'),
	(105, 'c5160160a64937b596d970ba1629a65e', 'Bread, bank and nose: how many body parts in the list?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(106, '571ba0b8afbdaf6422a72c2e25dd8d0a', '37, 41, 77 or 45: the biggest is?', '28dd2c7955ce926456240b2ff0100bde\n824bdf29673c75fca5b7cde2b3856cfb\n'),
	(107, '6a8ecedd664b2f452e812295a02faa7c', 'Coat, horse, pink, apple, sock and leg: how many colours in the list?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(108, '408be4e1617b31cbb756ca779950777c', 'The number of body parts in the list toe, tracksuit, fruit and snake is?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(109, 'b6c0e55fcabd25b9babb24bd06eebf51', 'Of the numbers 83, eighty seven or eighty one, which is the biggest?', 'c7e1249ffc03eb9ded908c236bd1996d\na3862f91f724b3ba93c0d29d596091aa\n'),
	(110, '34c7db548cf7633c05414363b3e32c15', 'What is five + 8?', 'c51ce410c124a10e0db5e4b97fc2af39\n422ecc084f2458defc620ecebf2a6448\n'),
	(111, '2787528a0ee6851cbbf717f871955028', 'The pink bread is what colour?', '4a0b0dcedd48f780778d1cd1bb8f9877\n'),
	(112, '71060494811293ba737ed2a7b5684395', 'What is 12 - seven?', 'e4da3b7fbbce2345d7772b0674a318d5\n30056e1cab7a61d256fc8edd970d14f5\n'),
	(113, 'd6ab80e329dd5736ffd1ad9e990064a3', 'What day is today, if yesterday was Sunday?', '944ba223a5c1b5f4b495708e7cd5ee37\n197639b278057c519189add5413712e3\n'),
	(114, '59d7b67a1a3d77850b8ac60c78e55782', 'What is 7 + 8?', '9bf31c7ff062936a96d3c8bd1f8f2ff3\n92a2132e0190d6e582f13376ddc660d5\n'),
	(115, '00ba8bb0cdc383950616419ef8977aeb', 'The number of body parts in the list nose, face, house, chest and horse is?', 'eccbc87e4b5ce2fe28308fd9f2a7baf3\n35d6d33467aae9a2e3dccb4b6b027878\n'),
	(116, '0729ca12c421bc2b6c6775dd05226e19', 'Enter the number thirty thousand nine hundred and ninety seven in digits:', '2b8e6d821e9764aab5c68f62979c320b\n'),
	(117, '187a9826919d71b3c555c6b0b403e9ab', 'The number of body parts in the list house, wine, shirt, tracksuit, brain and nose is?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(118, 'eaee49ee15ca896d4c3b1ced4bac31d8', 'Edward\'s name is?', 'a53f3929621dba1306f8a61588f52f55\n'),
	(119, 'd78ed235cfd7620ef11b6bd3ea574958', 'What is William\'s name?', 'fd820a2b4461bddd116c1518bc4b0f77\n'),
	(120, 'e6da9a88682e6fbd4f95e8d456b683c1', 'What is fourteen thousand two hundred and sixty eight as a number?', '81a6f51d90af2c00dfc715c5dc5fe88d\n'),
	(121, '12ec0d656845f5dc1a2e8782f99a2481', 'Which digit is 1st in the number 6719159?', '1679091c5a880faf6fb5e6087eb1b2dc\nf52b5e449a2303c031a0c3a1109360bf\n'),
	(122, 'bb4d3a695d5541f5337e5f3881d19afb', 'What is the 2nd digit in 8810071?', 'c9f0f895fb98ab9159f51fd0297e236d\n24d27c169c2c881eb09a065116f2aa5c\n'),
	(123, '1c2a3795aba1035ec3dd85e03d3ded72', '21, five, 7, twenty eight and 28: the 1st number is?', '3c59dc048e8850243be8079a5c74d079\nf349be2dc6ab3c11bcead8d91fa90360\n'),
	(124, '90dd698be809f98a0d923fd23316a5a8', 'What is fifty three thousand seven hundred and forty nine as a number?', 'f291aa14bddcd70703395223617c0b1a\n'),
	(125, 'ce8670b9659ecabadbd3ab495d5bf8db', 'The name of Chris is?', '6b34fe24ac2ff8103f6fce1f0da2ef57\n'),
	(126, '35b18f18ae592ae535f571748e902c38', 'Egg, soup, stomach, dog and apple: how many body parts in the list?', 'c4ca4238a0b923820dcc509a6f75849b\nf97c5d29941bfb1b2fdab0874906ab82\n'),
	(127, '5b7f7a1aaffdab5e1db3aa092f5fb29a', 'What is twelve thousand nine hundred and seventy seven as digits?', '001b8e3cf76f4e64cbe5be9882db4aa0\n'),
	(128, '2b0a5a7ce869de0c4bf0b0632a9e8a67', 'Carol\'s name is?', 'a9a0198010a6073db96434f6cc5f22a8\n'),
	(129, '603c2e766cf5112cf9a9473ba437e68f', 'The number of body parts in the list leg, arm and bee is?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n'),
	(130, 'e91a9299a92d90febee8af3a33e7858b', 'What is four thousand one hundred and seventy one as digits?', '1bd36c9ae813f304363ae6ac7f48068e\n'),
	(131, '5f05191d42c3d572d9a14f2f7dbd2db3', 'Which of 77, six, sixty five, 6 or eighty two is the largest?', '9778d5d219c5080b9a6a17bef029331c\n5c15ae0267d3efc37d3aeffe826cd023\n'),
	(132, '9834d3c9f70833903da0743e0f184661', 'How many colours in the list cow, yellow and brown?', 'c81e728d9d4c2f636f067f89cc14862c\nb8a9f715dbb64fd5c56e7783c6820a61\n');
/*!40000 ALTER TABLE `text_captcha` ENABLE KEYS */;

-- Dumping structure for table warcry.tokens
CREATE TABLE IF NOT EXISTS `tokens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL,
  `application` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `key` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expire` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `externalData` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.tokens: 0 rows
/*!40000 ALTER TABLE `tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokens` ENABLE KEYS */;

-- Dumping structure for table warcry.votecounter
CREATE TABLE IF NOT EXISTS `votecounter` (
  `account` bigint(20) DEFAULT NULL,
  `year` mediumint(4) DEFAULT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `counter` int(5) DEFAULT '0',
  UNIQUE KEY `UNIQUE` (`account`,`month`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table warcry.votecounter: ~0 rows (approximately)
/*!40000 ALTER TABLE `votecounter` DISABLE KEYS */;
/*!40000 ALTER TABLE `votecounter` ENABLE KEYS */;

-- Dumping structure for table warcry.vote_data
CREATE TABLE IF NOT EXISTS `vote_data` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account` bigint(20) NOT NULL DEFAULT '0',
  `siteid` tinyint(2) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping data for table warcry.vote_data: 0 rows
/*!40000 ALTER TABLE `vote_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `vote_data` ENABLE KEYS */;

-- Dumping structure for table warcry.wcf_categories
CREATE TABLE IF NOT EXISTS `wcf_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `position` tinyint(2) NOT NULL DEFAULT '0',
  `flags` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='Contains the Main Forum categories.';

-- Dumping data for table warcry.wcf_categories: 4 rows
/*!40000 ALTER TABLE `wcf_categories` DISABLE KEYS */;
INSERT INTO `wcf_categories` (`id`, `name`, `position`, `flags`) VALUES
	(1, 'Server Information', 0, 0),
	(2, 'Community', 0, 0),
	(3, 'Wardonic-Reborn 255 (Fun Realm)', 0, 0),
	(4, 'Gundrak (Blizzlike Realm)', 0, 0);
/*!40000 ALTER TABLE `wcf_categories` ENABLE KEYS */;

-- Dumping structure for table warcry.wcf_forums
CREATE TABLE IF NOT EXISTS `wcf_forums` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `category` int(10) NOT NULL DEFAULT '0',
  `name` varchar(250) NOT NULL,
  `description` varchar(500) NOT NULL,
  `class` tinyint(2) NOT NULL DEFAULT '0',
  `topics` int(10) NOT NULL DEFAULT '0',
  `posts` int(10) NOT NULL DEFAULT '0',
  `position` tinyint(2) NOT NULL DEFAULT '0',
  `lasttopic_id` int(10) NOT NULL DEFAULT '0',
  `flags` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='Contains the Forums.';

-- Dumping data for table warcry.wcf_forums: 9 rows
/*!40000 ALTER TABLE `wcf_forums` DISABLE KEYS */;
INSERT INTO `wcf_forums` (`id`, `category`, `name`, `description`, `class`, `topics`, `posts`, `position`, `lasttopic_id`, `flags`) VALUES
	(1, 1, 'Latest News', 'Latest news for the community.', 0, 0, 0, 0, 0, 0),
	(3, 2, 'Introduce Yourself', 'New to our project? Why don\'t you introduce yourself! :)\r\n', 0, 0, 0, 0, 0, 0),
	(2, 1, 'Frequently Asked Questions (FAQ)', '', 0, 0, 0, 0, 0, 0),
	(4, 2, 'General Discussion', 'Discussion about World of Warcraft, Project-Reborn related.\r\n', 0, 0, 0, 0, 0, 0),
	(5, 2, 'Support', 'A place for players to help players with a variety of issues with little to no intervention from Staff.', 0, 0, 0, 0, 0, 0),
	(6, 3, 'Suggestions', 'Got any suggestions? Don\'t hesistate to create a thread.\r\n', 0, 0, 0, 0, 0, 0),
	(7, 3, 'Recruitment', '', 0, 0, 0, 0, 0, 0),
	(8, 4, 'Suggestions', 'Got any suggestions? Don\'t hesistate to create a thread.\r\n', 0, 0, 0, 0, 0, 0),
	(9, 4, 'Recruitment', '', 0, 0, 0, 0, 0, 0);
/*!40000 ALTER TABLE `wcf_forums` ENABLE KEYS */;

-- Dumping structure for table warcry.wcf_posts
CREATE TABLE IF NOT EXISTS `wcf_posts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `topic` int(10) NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL,
  `text` text NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` bigint(20) NOT NULL DEFAULT '0',
  `lastedit_by` int(20) NOT NULL DEFAULT '0',
  `lastedit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_by` int(20) NOT NULL DEFAULT '0',
  `deleted_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `flags` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Contains the Forums.';

-- Dumping data for table warcry.wcf_posts: 0 rows
/*!40000 ALTER TABLE `wcf_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `wcf_posts` ENABLE KEYS */;

-- Dumping structure for table warcry.wcf_topics
CREATE TABLE IF NOT EXISTS `wcf_topics` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `forum` int(10) NOT NULL DEFAULT '0',
  `name` varchar(250) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` bigint(20) NOT NULL DEFAULT '0',
  `views` bigint(20) NOT NULL DEFAULT '0',
  `posts` int(10) NOT NULL DEFAULT '0',
  `lastpost_id` int(10) NOT NULL DEFAULT '0',
  `lastpost_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `flags` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Contains the Forums.';

-- Dumping data for table warcry.wcf_topics: 0 rows
/*!40000 ALTER TABLE `wcf_topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `wcf_topics` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
