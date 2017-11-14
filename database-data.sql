SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

TRUNCATE `Feature`;
INSERT INTO `Feature` (`FeatureID`, `FeatureName`, `FeatureType`) VALUES
	(1, 'brand', 0),
	(2, 'model', 0),
	(3, 'owner', 0),
	(4, 'sn', 0),
	(5, 'mac', 0),
	(6, 'type', 2),
	(7, 'working', 2),
	(8, 'capacity-byte', 1),
	(9, 'frequency-hertz', 1),
	(10, 'diameter-mm', 1),
	(11, 'diagonal-inch', 1),
	(12, 'has-gpu', 2), -- TODO: decide if this makes sens
	(13, 'color', 2),
	(14, 'motherboard-form-factor', 2),
	(15, 'notes', 0),
	(16, 'agp-sockets-n', 1),
	(17, 'arrival-batch', 0),
	(18, 'capacity-byte', 1),
	(19, 'capacity-decibyte', 1),
	(20, 'cib', 0),
	(21, 'core-n', 1),
	(22, 'cpu-socket', 2),
	(23, 'dvi-ports-n', 1),
	(24, 'ethernet-ports-1000m-n', 1),
	(25, 'ethernet-ports-100m-n', 1),
	(26, 'ethernet-ports-10base2-n', 1),
	(27, 'ethernet-ports-10m-n', 1),
	(28, 'hdd-odd-form-factor', 2),
	(29, 'ide-ports-n', 1),
	(31, 'odd-type', 2),
	(32, 'pcie-power', 2),
	(33, 'pcie-sockets-n', 1),
	(34, 'pci-sockets-n', 1),
	(35, 'power-connector', 2),
	(36, 'power-idle-watt', 1),
	(37, 'power-rated-watt', 1),
	(38, 'ps2-ports-n', 1),
	(39, 'psu-ampere', 1),
	(40, 'psu-socket', 2),
	(41, 'psu-volt', 1),
	(42, 'ram-socket', 2),
	(43, 'sata-ports-n', 1),
	(44, 'software', 0),
	(45, 'usb-ports-n', 1),
	(46, 'vga-ports-n', 1),
	(47, 'windows-serial-number', 0),
	(48, 'windows-serial-version', 0);

TRUNCATE `FeatureValue`;
INSERT INTO `FeatureValue` (`FeatureID`, `ValueEnum`, `ValueText`) VALUES
	(6, 0, 'location'),
	(6, 1, 'case'),
	(6, 2, 'motherboard'),
	(6, 3, 'cpu'),
	(6, 4, 'graphics-card'),
	(6, 5, 'ram'),
	(6, 6, 'hdd'),
	(6, 7, 'odd'),
	(6, 8, 'psu'),
	(6, 9, 'audio-card'),
	(6, 10, 'network-card'),
	(6, 11, 'monitor'),
	(6, 12, 'mouse'),
	(6, 13, 'keyboard'),
	(6, 14, 'switch'),
	(6, 15, 'hub'),
	(6, 16, 'modem-router'),
	(7, 0, 'no'),
	(7, 1, 'yes'),
	(7, 2, 'maybe'),
	(12, 0, 'no'),
	(12, 1, 'yes'),
	(13, 0, 'black'),
	(13, 1, 'white'),
	(13, 2, 'green'),
	(13, 3, 'yellow'),
	(13, 4, 'red'),
	(13, 5, 'blue'),
	(13, 6, 'grey'),
	(13, 7, 'darkgrey'),
	(13, 8, 'lightgrey'),
	(13, 9, 'pink'),
	(13, 10, 'transparent'),
	(13, 11, 'brown'),
	(13, 12, 'orange'),
	(13, 13, 'violet'),
	(13, 14, 'sip-brown'),
	(14, 0, 'atx'),
	(14, 1, 'miniatx'),
	(14, 2, 'microatx'),
	(14, 3, 'miniitx'),
	(14, 4, 'proprietary'),
	(14, 5, 'btx'),
	(14, 6, 'flexatx');
	-- (, , ''),

-- TRUNCATE `Codes`;
-- TRUNCATE `Item`;
-- TRUNCATE `ItemFeature`;
-- TRUNCATE `ItemLocationModification`;
-- TRUNCATE `ItemModification`;
-- TRUNCATE `ItemModificationDelete`;
-- TRUNCATE `Modification`;
-- TRUNCATE `Tree`;
-- TRUNCATE `User`;
