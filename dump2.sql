ALTER TABLE `prefix_blacklist` ADD `service` int(11) unsigned NOT NULL;
ALTER TABLE `prefix_blacklist` DROP PRIMARY KEY,
ALTER TABLE `prefix_blacklist` ADD PRIMARY KEY (`content`,`type`,`service`);
