DROP TABLE IF EXISTS `proxy`;
CREATE TABLE `proxy` (
    `id` int(11) NOT NULL auto_increment,
    `ip` varbinary(16) default NULL,
    `mac` bigint unsigned default NULL,
    `username` varchar(64) default NULL,
    `duration` int(11) unsigned default NULL,
    `cache_code` varchar(64) default NULL,
    `status` varchar(64) default NULL,
    `bytes` int(11) unsigned default NULL,
    `method` varchar(64) default NULL,
    `request` text NOT NULL,
    `domain` varchar(128) default NULL,
    `content_type` text default NULL,
    `filter_code` int(11) default NULL,
    `filter_detail` text default NULL,
    `md5` varchar(64) NOT NULL,
    `timestamp` timestamp NULL default NULL,
    `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `md5` (`md5`),
    INDEX(ip),
    INDEX(timestamp),
    INDEX(cache_code),
    INDEX(filter_code),
    INDEX(domain),
    INDEX(request(100)),
    INDEX(username),
    INDEX(username, timestamp)
) ENGINE=innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `proxy_domains`;
CREATE TABLE `proxy_domains` (
    `id` bigint(20) unsigned NOT NULL auto_increment,
    `ip` varbinary(16) default NULL,
    `hostname` varchar(255) default NULL,
    `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ip` (`ip`),
    INDEX(timestamp),
    KEY `hostname` (`hostname`)
) ENGINE=innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
