DROP TABLE IF EXISTS `history`;
CREATE TABLE `history` (
  `SyncId` INTEGER NOT NULL auto_increment,
  `SimId` INTEGER NOT NULL,
  `StartTime` BIGINT default NULL,
  `RxBytes` BIGINT default NULL,
  `TxBytes` BIGINT default NULL,
PRIMARY KEY(`SyncId`)
) ENGINE=InnoDB;
CREATE UNIQUE INDEX index_1 on `history` (SimId, StartTime);

