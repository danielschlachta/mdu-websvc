DROP TABLE IF EXISTS `sims`;
CREATE TABLE `sims` (
  `SimId` INTEGER NOT NULL auto_increment,
  `SimSerial` VARCHAR(32) default NULL,
  `SimCaption` VARCHAR(32) default NULL,
  `LastChange` BIGINT default NULL,
  `LastUpdate` BIGINT default NULL,
  `Current` BIGINT default NULL,
  `Floor` BIGINT default NULL,
  `HasLimit` TINYINT default NULL,
  `Limit` BIGINT default NULL,
  `HasUsedWarning` TINYINT default NULL,
  `UsedWarning` BIGINT default NULL,
  `UsedLastSeen` BIGINT default NULL,
  `HasRemainWarning` TINYINT default NULL,
  `RemainWarning` BIGINT default NULL,
  `RemainLastSeen` BIGINT default NULL,
PRIMARY KEY  (`SimId`)
) ENGINE=InnoDB;
CREATE UNIQUE INDEX index_1 on `sims` (SimSerial);
