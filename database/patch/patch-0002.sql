-- Initial users for JANUS
INSERT IGNORE INTO `janus__user` (`uid`, `userid`, `type`, `email`, `active`, `update`, `created`, `ip`, `data`, `secret`)
VALUES
(1, 'admin', 'a:8:{i:0;s:5:"admin";i:1;s:10:"operations";i:2;s:11:"secretariat";i:3;s:9:"technical";i:4;s:7:"support";i:5;s:14:"administrative";i:6;s:7:"billing";i:7;s:5:"other";}', NULL, 'yes', '2011-04-12T01:02:43-07:00', '2011-04-12T01:02:43-07:00', '192.168.212.1', NULL, NULL),
(2, 'engine', 'a:8:{i:0;s:5:"admin";i:1;s:10:"operations";i:2;s:11:"secretariat";i:3;s:9:"technical";i:4;s:7:"support";i:5;s:14:"administrative";i:6;s:7:"billing";i:7;s:5:"other";}', NULL, 'yes', '2011-04-12T02:42:25-07:00', '2011-04-12T02:42:25-07:00', '192.168.212.1', NULL, 'engineblock');