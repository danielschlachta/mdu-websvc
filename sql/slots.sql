DROP TABLE IF EXISTS `slots`;
CREATE TABLE `slots` (
  `SimId` INTEGER NOT NULL,
  `ListId` TINYINT NOT NULL,
  `Slot` TINYINT NOT NULL,
  `StartTime` BIGINT default NULL,
  `RxBytes` BIGINT default NULL,
  `TxBytes` BIGINT default NULL
) ENGINE=InnoDB;
CREATE UNIQUE INDEX index_1 on `slots` (SimId, ListId, Slot);
