SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

TRUNCATE `Feature`;
INSERT INTO `Feature` (`Feature`, `Type`) VALUES
	('brand', 0),
	('model', 0),
	('owner', 0),
	('sn', 0),
	('mac', 0),
	('type', 2),
	('working', 2),
	('capacity-byte', 1),
	('frequency-hertz', 1),
	('diameter-mm', 1),
	('diagonal-inch', 1),
	('isa', 2),
	('color', 2),
	('motherboard-form-factor', 2),
	('notes', 0),
	('agp-sockets-n', 1),
	('arrival-batch', 0),
	('capacity-decibyte', 1),
	('cib', 0),
	('core-n', 1),
	('cpu-socket', 2),
	('dvi-ports-n', 1),
	('ethernet-ports-1000m-n', 1),
	('ethernet-ports-100m-n', 1),
	('ethernet-ports-10base2-bnc-n', 1),
	('ethernet-ports-10m-n', 1),
	('hdd-odd-form-factor', 2),
	('ide-ports-n', 1),
	('odd-type', 2),
	('pcie-power-pin-n', 1),
	('pcie-sockets-n', 1),
	('pci-sockets-n', 1),
	('power-connector', 2),
	('power-idle-watt', 1),
	('power-rated-watt', 1),
	('ps2-ports-n', 1),
	('psu-ampere', 3),
	('psu-connector-motherboard', 2), -- use sata-ports-n and pcie-power-pin-n for that stuff
	('psu-volt', 3),
	('ram-type', 2),
	('sata-ports-n', 1),
	('software', 0),
	('usb-ports-n', 1),
	('vga-ports-n', 1),
	('os-license-code', 0),
	('os-license-version', 0),
	('power-idle-pfc', 0),
	('firewire-ports-n', 1),
	('mini-firewire-ports-n', 1),
	('serial-ports-n', 1), -- DE-9 ports, also known as RS-232 (which apparently is a standard that also works on DB-25 ports, so don't call them like that)
	('parallel-ports-n', 1),
	('ram-form-factor', 2),
	('weight-gram', 1),
	('spin-rate-rpm', 1),
	('dms-59-ports-n', 1), -- the weird DVI port which is actually 2 DVI ports in one
	('check', 2),
	('ram-ecc', 2),
	('other-code', 0),
	('hdmi-ports-n', 1),
	('scsi-sca2-ports-n', 1), -- SCA 2 (80 pin)
	('scsi-db68-ports-n', 1), -- DB68 (68 pin)
	('mini-ide-ports-n', 1), -- Laptop IDE
	('data-erased', 2), -- HDD entirely erased
	('surface-scan', 2), -- Running badblocks on HDDs
	('smart-data', 2), -- S.M.A.R.T.
	('wireless-receiver', 2),
	('rj11-ports-n', 1),
	('ethernet-ports-10base5-aui-n', 1),
	('midi-ports-n', 1),
	('mini-jack-ports-n', 1),
	('rca-mono-ports-n', 1),
	('tv-out-ports-n', 1),
	('s-video-ports-n', 1),
	('s-video-7pin-ports-n', 1),
	('composite-video-ports-n', 1),
	('serial-db25-ports-n', 1), -- DB-25 serial ports
	('isa-sockets-n', 1),
	('mini-pcie-sockets-n', 1),
	('mini-pci-sockets-n', 1),
	('brand-reseller', 0),
	('psu-form-factor', 2),
	('cib-old', 0),
	('restrictions', 2),
	('displayport-ports-n', 1),
	('pci-low-profile', 2),
	('psu-connector-cpu', 2),
	('jae-ports-n', 1), -- Old laptop ODDs use a 50-pin connector which is just an IDE with 10 pins for power, and it's apparently called JAE. Basically no information exist on this connector anywhere on the internet...
	('game-ports-n', 1);

TRUNCATE `FeatureEnum`;
INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES
	('type', 'location'),
	('type', 'case'),
	('type', 'motherboard'),
	('type', 'cpu'),
	('type', 'graphics-card'),
	('type', 'ram'),
	('type', 'hdd'),
	('type', 'odd'),
	('type', 'psu'),
	('type', 'audio-card'),
	('type', 'ethernet-card'),
	('type', 'monitor'),
	('type', 'mouse'),
	('type', 'keyboard'),
	('type', 'network-switch'),
	('type', 'network-hub'),
	('type', 'modem-router'),
	('type', 'fdd'),
	('type', 'ports-bracket'),
	('type', 'other-card'),
	('type', 'heatsink'),
	('type', 'fan'),
	('type', 'fan-controller'),
	('type', 'modem-card'),
	('type', 'scsi-card'),
	('type', 'wifi-card'),
	('type', 'bluetooth-card'),
	('type', 'external-psu'),
	('type', 'zip-drive'),
	('type', 'printer'),
	('type', 'scanner'),
	('type', 'inventoried-object'),
	('type', 'adapter'),
	('type', 'usbhub'),
	('working', 'no'),
	('working', 'yes'),
	('working', 'maybe'),
	('isa', 'x86-32'),
	('isa', 'x86-64'),
	('isa', 'ia-64'),
	('isa', 'arm'),
	('color', 'black'),
	('color', 'white'),
	('color', 'green'),
	('color', 'yellow'),
	('color', 'red'),
	('color', 'blue'),
	('color', 'grey'),
	('color', 'darkgrey'),
	('color', 'lightgrey'),
	('color', 'pink'),
	('color', 'transparent'),
	('color', 'brown'),
	('color', 'orange'),
	('color', 'violet'),
	('color', 'sip-brown'),
	('color', 'lightblue'),
	('color', 'yellowed'),
	('color', 'transparent-dark'),
	('color', 'golden'),
	('motherboard-form-factor', 'atx'),
	('motherboard-form-factor', 'miniatx'),
	('motherboard-form-factor', 'microatx'),
	('motherboard-form-factor', 'miniitx'),
	('motherboard-form-factor', 'proprietary'),
	('motherboard-form-factor', 'btx'),
	('motherboard-form-factor', 'flexatx'),
	('motherboard-form-factor', 'proprietary-laptop'), -- Who knows, maybe distinguishing proprietary motherboards between desktops and laptops will turn out to be useful to...
	('motherboard-form-factor', 'eatx'),
	('cpu-socket', 'other'),
	('cpu-socket', 'other-slot'),
	('cpu-socket', 'other-socket'),
	('cpu-socket', 'other-dip'),
	('cpu-socket', 'g1'),	
	('cpu-socket', 'g2'),
	('cpu-socket', 'socket7'),
	('cpu-socket', 'm'),	
	('cpu-socket', 'p'), -- Socket P, which has 478 pins but it's completely different from socket 478
	('cpu-socket', 'am1'),
	('cpu-socket', 'am2'),
	('cpu-socket', 'am2plus'),
	('cpu-socket', 'am3'),
	('cpu-socket', 'am3plus'),
	('cpu-socket', 'am4'),
	('cpu-socket', 'fm1'),
	('cpu-socket', 'fm2'),
	('cpu-socket', 'fm2plus'),
	('cpu-socket', 'g34'),
	('cpu-socket', 'c32'),
	('cpu-socket', 'g3'), -- aka rPGA988A
	('cpu-socket', 'slot1'),
	('cpu-socket', 'socket370'),
	('cpu-socket', 'socket462a'), -- A aka 462
	('cpu-socket', 'socket423'),
	('cpu-socket', 'socket478'), -- 478 aka mPGA478B
	-- There are 3 sockets with multiple names and each one is also called socket 479. And they have 478 pins. Mechanically identical, electrically incompatible.
	('cpu-socket', 'socket479a'), -- 479 aka mPGA478A
	('cpu-socket', 'socket479c'), -- 479 aka mPGA478C
	('cpu-socket', 'socket479m'), -- 479 aka socket M
	('cpu-socket', 'socket495'),
	('cpu-socket', 'socket603'),
	('cpu-socket', 'socket754'),
	('cpu-socket', 'socket940'),
	('cpu-socket', 'socket939'),
	('cpu-socket', 'lga775'), -- LGA775 aka socket T aka socket775
	('cpu-socket', 'lga771'), -- LGA775 aka socket J
	('cpu-socket', 'lga1366'), -- LGA775 aka socket B
	('cpu-socket', 'lga1156'), -- aka H1
	('cpu-socket', 'lga1248'),
	('cpu-socket', 'lga1567'),
	('cpu-socket', 'lga1155'), -- aka H2
	('cpu-socket', 'lga2011'), -- R
	('cpu-socket', 'lga1150'), -- aka H3
	('cpu-socket', 'lga1151'), -- aka H4
	('cpu-socket', 'lga3647'),
	('cpu-socket', 'lga2066'),
	('hdd-odd-form-factor', '5.25'),
	('hdd-odd-form-factor', '3.5'),
	('hdd-odd-form-factor', '2.5'),
	('hdd-odd-form-factor', '2.5-15mm'), -- second number is the height in millimeters (these are specified as 15 but most common sizes, for both bays and drives, are 7 mm and 9.5 mm: the more you know...)
	('hdd-odd-form-factor', 'm2'),
	('hdd-odd-form-factor', 'm2.2'),
	('hdd-odd-form-factor', '2.5-7mm'),
	('hdd-odd-form-factor', '2.5-9.5mm'),
	('hdd-odd-form-factor', 'laptop-odd-standard'), -- to be replaced with actual name if we ever find what it is
	('hdd-odd-form-factor', 'laptop-odd-slim'), -- I have no idea there was a difference: https://superuser.com/a/276241
	('odd-type', 'cd-r'),
	('odd-type', 'cd-rw'),
	('odd-type', 'dvd-r'),
	('odd-type', 'dvd-rw'),
	('odd-type', 'bd-r'),
	('odd-type', 'bd-rw'),
	('power-connector', 'other'),
	('power-connector', 'c13'), -- C13 is the plug and C14 the inlet but they're "paired"
	('power-connector', 'c19'),
	('power-connector', 'barrel'),
	('power-connector', 'miniusb'),
	('power-connector', 'microusb'),
	('power-connector', 'proprietary'),
	('power-connector', 'da-2'), -- Dell DA-2
	('psu-connector-motherboard', 'proprietary'),
	('psu-connector-motherboard', 'at'),
	('psu-connector-motherboard', 'atx-20pin'),
	('psu-connector-motherboard', 'atx-24pin'),
	('psu-connector-motherboard', 'atx-20pin-aux'), -- AUX connector, which looks kind of like an AT connector
	('ram-type', 'simm'),
	('ram-type', 'edo'),
	('ram-type', 'sdr'),
	('ram-type', 'ddr'),
	('ram-type', 'ddr2'),
	('ram-type', 'ddr3'),
	('ram-type', 'ddr4'),
	('ram-form-factor', 'simm'),
	('ram-form-factor', 'dimm'),
	('ram-form-factor', 'sodimm'),
	('ram-form-factor', 'minidimm'),
	('ram-form-factor', 'microdimm'),
	('ram-form-factor', 'fbdimm'),
	('check', 'missing-data'),
	('check', 'wrong-data'),
	('check', 'wrong-location'),
	('check', 'wrong-content'),
	('check', 'missing-content'),
	('check', 'wrong-data-and-content'),
	('check', 'wrong-location-and-data'),
	('ram-ecc', 'no'),
	('ram-ecc', 'yes'),
	-- (65, 0, 'no'),
	('data-erased', 'yes'), -- Just don't add the feature if it hasn't been erased...
	('surface-scan', 'fail'),
	('surface-scan', 'pass'),
	('smart-data', 'fail'),
	('smart-data', 'old'), -- old and tired HDDs, but still no reallocated sectors or other serious warnings
	('smart-data', 'ok'),
	('wireless-receiver', 'inside'), -- wireless receiver: located inside, nearby or missing, making the peripheral completely useless since these are always proprietary
	('wireless-receiver', 'near'),
	('wireless-receiver', 'missing'),
	('psu-form-factor', 'atx'),
	('psu-form-factor', 'cfx'), -- the wide L-shaped ones
	('psu-form-factor', 'lfx'), -- long and L-shaped
	('psu-form-factor', 'sfx-lowprofile'), -- SFX has lots of variants
	('psu-form-factor', 'sfx-topfan'),
	('psu-form-factor', 'sfx-topfan-reduceddepth'),
	('psu-form-factor', 'sfx'),
	('psu-form-factor', 'sfx-ps3'),
	('psu-form-factor', 'sfx-l'), -- Also called "mATX" or "mini ITX", size claimed to be 125×64×140 mm, NOWHERE to be found in the ATX or SFX specification but multiple models exist for sale right now
	('psu-form-factor', 'tfx'), -- I don't even know anymore
	('psu-form-factor', 'flexatx'),
	('psu-form-factor', 'proprietary'),
	('psu-form-factor', 'eps'),
	('restrictions', 'loan'), -- borrowed items that should be returned to owner, can't be donated
	('restrictions', 'in-use'), -- items that shouldn't be donated right now because we're using them (e.g. switch, pc used for invetory management, server)
	('restrictions', 'bought'), -- items bought with funds from our annual budget, can't be donated at all ever
	('restrictions', 'training'), -- PCs to be used for training and demonstrations (because they're old, slow and with the case full of scratches, mostly), but still working and that can be potentially donated
	('restrictions', 'ready'), -- Completely "restored", cleaned, OS installed, ready for donation, so don't mess them up
	('restrictions', 'other'), -- "other" also means "cannot be donated right now"
	('pci-low-profile', 'no'),
	('pci-low-profile', 'possibile'), -- no low profile metal thing but the card itself is low profile
	('pci-low-profile', 'dual'), -- we've got both the full height and low profile thing
	('pci-low-profile', 'yes'),
	('psu-connector-cpu', 'none'),
	('psu-connector-cpu', '4pin'),
	('psu-connector-cpu', '6pin'), -- well, it exists and it's not a PCIe power connector.
	('psu-connector-cpu', '8pin'),
	('psu-connector-cpu', 'proprietary');
-- TRUNCATE `Codes`;
-- TRUNCATE `Item`;
-- TRUNCATE `ItemFeature`;
-- TRUNCATE `ItemLocationModification`;
-- TRUNCATE `ItemModification`;
-- TRUNCATE `ItemModificationDelete`;
-- TRUNCATE `Modification`;
-- TRUNCATE `Tree`;
-- TRUNCATE `User`;
