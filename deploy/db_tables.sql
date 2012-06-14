DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `ip` int(10) unsigned NOT NULL default '0',
  `hostname` varchar(255) default NULL,
  `creationdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `hostname` (`hostname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `hostnames`;
CREATE TABLE `hostnames` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `ip` int(10) unsigned NOT NULL default '0',
  `description` varchar(50) NOT NULL default '',
  `isResolved` tinyint(3) unsigned NOT NULL default '0',
  `hostname` varchar(255) default NULL,
  `country_code` char(3) default NULL,
  `country_name` varchar(20) default NULL,
  `city` varchar(20) default NULL,
  `region` varchar(20) default NULL,
  `latitude` float(10,7) NOT NULL default '0.0000000',
  `longitude` float(10,7) NOT NULL default '0.0000000',
  `creationdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `isResolved` (`isResolved`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `proxy`;
CREATE TABLE `proxy` (
  `id` int(11) NOT NULL auto_increment,
  `date_time` timestamp NULL default NULL,
  `duration` int(11) unsigned default NULL,
  `client` int(10) unsigned default NULL,
  `cache_code` varchar(64) default NULL,
  `status` varchar(64) default NULL,
  `bytes` int(11) unsigned default NULL,
  `method` varchar(64) default NULL,
  `request` text NOT NULL,
  `domain` varchar(128) default NULL,
  `rfc931` varchar(64) default NULL,
  `content_type` text default NULL,
  `filter_code` int(11) default NULL,
  `filter_detail` text default NULL,
  `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `md5` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `md5` (`md5`),
  INDEX(client),
  INDEX(date_time),
  INDEX(filter_code),
  INDEX(domain),
  INDEX(request(100)),
  INDEX(rfc931),
  INDEX(rfc931, date_time)
) ENGINE=innodb DEFAULT CHARSET=latin1;
