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
	(12, 'isa', 2),
	(13, 'color', 2),
	(14, 'motherboard-form-factor', 2),
	(15, 'notes', 0),
	(16, 'agp-sockets-n', 1),
	(17, 'arrival-batch', 0),
	(19, 'capacity-decibyte', 1),
	(20, 'cib', 0),
	(21, 'core-n', 1),
	(22, 'cpu-socket', 2),
	(23, 'dvi-ports-n', 1),
	(24, 'ethernet-ports-1000m-n', 1),
	(25, 'ethernet-ports-100m-n', 1),
	(26, 'ethernet-ports-10base2-bnc-n', 1),
	(27, 'ethernet-ports-10m-n', 1),
	(28, 'hdd-odd-form-factor', 2),
	(29, 'ide-ports-n', 1),
	(31, 'odd-type', 2),
	(32, 'pcie-power-pin-n', 1),
	(33, 'pcie-sockets-n', 1),
	(34, 'pci-sockets-n', 1),
	(35, 'power-connector', 2),
	(36, 'power-idle-watt', 1),
	(37, 'power-rated-watt', 1),
	(38, 'ps2-ports-n', 1),
	(39, 'psu-ampere', 1),
	(40, 'psu-socket', 2), -- use sata-ports-n and pcie-power-pin-n for that stuff
	(41, 'psu-volt', 1),
	(42, 'ram-type', 2),
	(43, 'sata-ports-n', 1),
	(44, 'software', 0),
	(45, 'usb-ports-n', 1),
	(46, 'vga-ports-n', 1),
	(47, 'os-serial-number', 0),
	(48, 'os-serial-version', 0),
	(49, 'soldered-in-place', 2), -- TODO: replace with "movable" in Item table (convert back and forth on server only maybe with triggers or modify protocol and client too?)
	(50, 'power-idle-pfc', 0),
	(51, 'firewire-ports-n', 1),
	(52, 'serial-ports-n', 1), -- DE-9 ports, also known as RS-232 (which apparently is a standard that also works on DB-25 ports, so don't call them like that)
	(53, 'parallel-ports-n', 1),
	(54, 'ram-form-factor', 2),
	(55, 'weight-gram', 1),
	(56, 'spin-rate-rpm', 1),
	(57, 'dms-59-ports-n', 1), -- the weird DVI port which is actually 2 DVI ports in one
	(58, 'check', 2),
	(59, 'ram-ecc', 2),
	(60, 'other-code', 0),
	(61, 'hdmi-ports-n', 1),
	(62, 'scsi-sca2-ports-n', 1), -- SCA 2 (80 pin)
	(63, 'scsi-db68-ports-n', 1), -- DB68 (68 pin)
	(64, 'mini-ide-ports-n', 1), -- Laptop IDE
	(65, 'data-erased', 2), -- HDD entirely erased
	(66, 'surface-scan', 2), -- Running badblocks on HDDs
	(67, 'smart-data', 2), -- S.M.A.R.T.
	(68, 'wireless-receiver', 2),
	(69, 'rj11-ports-n', 1),
	(70, 'ethernet-ports-10base5-aui-n', 1),
	(71, 'midi-ports-n', 1),
	(72, 'mini-jack-ports-n', 1),
	(73, 'rca-mono-ports-n', 1),
	(74, 'tv-out-ports-n', 1),
	(75, 's-video-ports-n', 1),
	(76, 'serial-db25-ports-n', 1), -- DB-25 serial ports
	(77, 'isa-sockets-n', 1),
	(78, 'mini-pcie-sockets-n', 1),
	(79, 'brand-reseller', 0),
	(80, 'psu-form-factor', 2),
	(81, 'cib-old', 0),
	(82, 'restrictions', 2),
	(83, 'displayport-ports-n', 1),
	(84, 'pci-low-profile', 2);

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
	(6, 10, 'ethernet-card'),
	(6, 11, 'monitor'),
	(6, 12, 'mouse'),
	(6, 13, 'keyboard'),
	(6, 14, 'network-switch'),
	(6, 15, 'network-hub'),
	(6, 16, 'modem-router'),
	(6, 17, 'fdd'),
	(6, 18, 'ports-bracket'),
	(6, 19, 'other-card'),
	(6, 20, 'heatsink'),
	(6, 21, 'fan'),
	(6, 22, 'fan-controller'),
	(6, 23, 'modem-card'),
	(6, 24, 'scsi-card'),
	(6, 25, 'wifi-card'),
	(6, 26, 'external-psu'),
	(6, 27, 'zip-drive'),
	(6, 28, 'printer'),
	(6, 29, 'scanner'),
	(7, 0, 'no'),
	(7, 1, 'yes'),
	(7, 2, 'maybe'),
	(12, 0, 'x86-32'),
	(12, 1, 'x86-64'),
	(12, 2, 'ia-64'),
	(12, 3, 'arm'),
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
	(13, 15, 'lightblue'),
	(13, 16, 'yellowed'),
	(13, 17, 'transparent-dark'),
	(13, 18, 'golden'),
	(14, 0, 'atx'),
	(14, 1, 'miniatx'),
	(14, 2, 'microatx'),
	(14, 3, 'miniitx'),
	(14, 4, 'proprietary'),
	(14, 5, 'btx'),
	(14, 6, 'flexatx'),
	(14, 7, 'proprietary-laptop'), -- Who knows, maybe distinguishing proprietary motherboards between desktops and laptops will turn out to be useful to...
	(14, 8, 'eatx'),
	(22, 0, 'other'),
	(22, 1, 'other-slot'),
	(22, 2, 'other-socket'),
	(22, 3, 'other-dip'),
	(22, 4, 'g1'),	
	(22, 5, 'g2'),
	(22, 7, 'socket7'),
	(22, 8, 'm'),	
	(22, 9, 'p'), -- Socket P, which has 478 pins but it's completely different from socket 478
	(22, 370, 'socket370'),
	(22, 462, 'socket462a'), -- A aka 462
	(22, 423, 'socket423'),
	(22, 478, 'socket478'), -- 478 aka mPGA478B
	(22, 479, 'socket479'),
	(22, 603, 'socket603'),
	(22, 754, 'socket754'),
	(22, 940, 'socket940'),
	(22, 939, 'socket939'),
	(22, 775, 'lga775'), -- LGA775 aka socket T aka socket775
	(22, 771, 'lga771'), -- LGA775 aka socket J
	(22, 10, 'am1'),
	(22, 11, 'am2'),
	(22, 12, 'am2plus'),
	(22, 13, 'am3'),
	(22, 14, 'am3plus'),
	(22, 15, 'am4'),
	(22, 16, 'fm1'),
	(22, 17, 'fm2'),
	(22, 18, 'fm2plus'),
	(22, 1366, 'lga1366'), -- LGA775 aka socket B
	(22, 1156, 'lga1156'), -- aka H1
	(22, 19, 'g34'),
	(22, 20, 'c32'),
	(22, 1248, 'lga1248'),
	(22, 1567, 'lga1567'),
	(22, 1155, 'lga1155'), -- aka H2
	(22, 2011, 'lga2011'), -- R
	(22, 1150, 'lga1150'), -- aka H3
	(22, 21, 'g3'), -- aka rPGA988A
	(22, 1151, 'lga1151'), -- aka H4
	(22, 3647, 'lga3647'),
	(22, 2066, 'lga2066'),
	(28, 0, '5.25'),
	(28, 1, '3.5'),
	(28, 2, '2.5'),
	(28, 3, '2.5-15mm'), -- second number is the height in millimeters (these are specified as 15 but most common sizes, for both bays and drives, are 7 mm and 9.5 mm: the more you know...)
	(28, 4, 'm2'),
	(28, 5, 'm2.2'),
	(28, 6, '2.5-7mm'),
	(28, 7, '2.5-9.5mm'),
	(28, 8, 'laptop-odd-standard'), -- to be replaced with actual name if we ever find what it is
	(28, 9, 'laptop-odd-slim'), -- I have no idea there was a difference: https://superuser.com/a/276241
	(31, 0, 'cd-r'),
	(31, 1, 'cd-rw'),
	(31, 2, 'dvd-r'),
	(31, 3, 'dvd-rw'),
	(31, 4, 'bd-r'),
	(31, 5, 'bd-rw'),
	(35, 0, 'other'),
	(35, 1, 'c13'), -- C13 is the plug and C14 the inlet but they're "paired"
	(35, 2, 'c19'),
	(35, 3, 'barrel'),
	(35, 4, 'miniusb'),
	(35, 5, 'microusb'),
	(35, 6, 'proprietary'),
	(35, 7, 'da-2'), -- Dell DA-2
	(40, 0, 'other'),
	(40, 1, 'at'),
	(40, 2, 'atx-old'), -- 20 pin
	(40, 4, 'atx12v'), -- 20+4 pin
	(40, 5, 'atx12v-extended'), -- 20+4 pin, that weird extended thing
	(40, 6, 'atx12v-4pin'), -- 20+4 pin, 4 pin for CPU
	(40, 7, 'atx12v-8pin'), -- 20+4 pin, 8 pin for CPU
	(40, 8, 'proprietary'),
	(42, 0, 'simm'),
	(42, 1, 'edo'),
	(42, 2, 'sdr'),
	(42, 3, 'ddr'),
	(42, 4, 'ddr2'),
	(42, 5, 'ddr3'),
	(42, 6, 'ddr4'),
	-- (49, 0, 'no'),
	(49, 1, 'yes'),
	(54, 0, 'simm'),
	(54, 1, 'dimm'),
	(54, 2, 'sodimm'),
	(54, 3, 'minidimm'),
	(54, 4, 'microdimm'),
	(54, 5, 'fbdimm'),
	(58, 0, 'missing-data'),
	(58, 1, 'wrong-data'),
	(58, 2, 'wrong-location'),
	(58, 3, 'wrong-content'),
	(58, 4, 'missing-content'),
	(58, 5, 'wrong-data-and-content'),
	(58, 6, 'wrong-location-and-data'),
	(59, 0, 'no'),
	(59, 1, 'yes'),
	-- (65, 0, 'no'),
	(65, 1, 'yes'), -- Just don't add the feature if it hasn't been erased...
	(66, 0, 'fail'),
	(66, 1, 'pass'),
	(67, 0, 'fail'),
	(67, 1, 'old'), -- old and tired HDDs, but still no reallocated sectors or other serious warnings
	(67, 2, 'ok'),
	(68, 1, 'inside'), -- wireless receiver: located inside, nearby or missing, making the peripheral completely useless since these are always proprietary
	(68, 2, 'near'),
	(68, 0, 'missing'),
	(80, 0, 'atx'),
	(80, 1, 'cfx'), -- the wide L-shaped ones
	(80, 2, 'lfx'), -- long and L-shaped
	(80, 3, 'sfx-lowprofile'), -- SFX has lots of variants
	(80, 4, 'sfx-topfan'),
	(80, 5, 'sfx-topfan-reduceddepth'),
	(80, 6, 'sfx'),
	(80, 7, 'sfx-ps3'),
	(80, 8, 'tfx'), -- I don't even know anymore
	(80, 9, 'flexatx'),
	(80, 10, 'proprietary'),
	(80, 11, 'eps'),
	(82, 1, 'loan'), -- borrowed items that should be returned to owner, can't be donated
	(82, 2, 'in-use'), -- items that shouldn't be donated right now because we're using them (e.g. switch, pc used for invetory management, server)
	(82, 3, 'bought'), -- items bought with funds from our annual budget, can't be donated at all ever
	(82, 4, 'training'), -- PCs to be used for training and demonstrations (because they're old, slow and with the case full of scratches, mostly), but still working and that can be potentially donated
	(82, 5, 'ready'), -- Completely "restored", cleaned, OS installed, ready for donation, so don't mess them up
	(82, 0, 'other'), -- "other" also means "cannot be donated right now"
	(84, 0, 'no'),
	(84, 1, 'possibile'), -- no low profile metal thing but the card itself is low profile
	(84, 2, 'dual'), -- we've got both the full height and low profile thing
	(84, 3, 'yes');
-- TRUNCATE `Codes`;
-- TRUNCATE `Item`;
-- TRUNCATE `ItemFeature`;
-- TRUNCATE `ItemLocationModification`;
-- TRUNCATE `ItemModification`;
-- TRUNCATE `ItemModificationDelete`;
-- TRUNCATE `Modification`;
-- TRUNCATE `Tree`;
-- TRUNCATE `User`;
