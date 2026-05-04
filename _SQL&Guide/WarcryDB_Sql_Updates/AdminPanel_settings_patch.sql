CREATE TABLE IF NOT EXISTS `site_settings` (
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `site_settings` (`name`,`value`) VALUES
('site_name','Warcry'),
('copyright','Copyright &copy; <b>Project-Reborn</b>&trade; 2017. All Rights Reserved.'),
('favicon','template/style/images/favicon.ico');
