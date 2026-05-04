CREATE TABLE IF NOT EXISTS `site_settings` (
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `site_settings` (`name`,`value`) VALUES
('home_welcome_title','Welcome to Project-Reborn'),
('home_welcome_text','We are a growing server with 2 realms 1 blizzlike and 1 fun realm instant 255 with much custom content.\nIf you are looking forward to join our team or have any questions, please join our Discord channel or create a topic on the forum!');
