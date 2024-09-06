DROP TABLE IF EXISTS `external`;
CREATE TABLE `external` (
  `Id` INTEGER NOT NULL auto_increment,
  `PhoneId` INTEGER NOT NULL,
  `StartTime` BIGINT default NULL,
  `RxBytes` BIGINT default NULL,
  `TxBytes` BIGINT default NULL,
  `TotalRxBytes` BIGINT default NULL,
  `TotalTxBytes` BIGINT default NULL,
PRIMARY KEY(`Id`)
) ENGINE=InnoDB;
CREATE UNIQUE INDEX index_1 on `external` (PhoneId, StartTime);

insert into external values(0, 1, 0, 0, 0, 0, 0);
