CREATE TABLE IF NOT EXISTS `SS_intarocrm` (
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
);

INSERT INTO `SS_intarocrm` (`key`, `value`) VALUES
('apiKey', ''),
('url', ''),
('delivery', ''),
('statusses', ''),
('payment', ''),
('params', '');


 ALTER TABLE `SS_users` ADD COLUMN `modules-intarocrm` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `modules`;