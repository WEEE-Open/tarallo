SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

TRUNCATE `Feature`;
INSERT INTO `Feature` (`Feature`, `Type`) VALUES
	('brand', 0), -- Brand --
	('model', 0), -- Model --
	('owner', 0), -- Owner --
	('sn', 0), -- Serial number (s/n) --
	('mac', 0), -- MAC address --
	('type', 2), -- Type --
	('working', 2), -- Working --
	('capacity-byte', 1), -- Capacity --
	('frequency-hertz', 1), -- Frequency --
	('diameter-mm', 1), -- Diameter --
	('diagonal-inch', 1), -- Diagonal --
	('isa', 2), -- Architecture --
	('color', 2), -- Color --
	('motherboard-form-factor', 2), -- Form factor (motherboard) --
	('notes', 0), -- Notes --
	('agp-sockets-n', 1), -- Sockets: AGP --
	('arrival-batch', 0), -- Arrival batch --
	('capacity-decibyte', 1), -- Capacity ("decimal" bytes) --
	('cib', 0), -- CIB --
	('core-n', 1), -- Cores --
	('cpu-socket', 2), -- Socket (CPU) --
	('dvi-ports-n', 1), -- Ports: DVI --
	('ethernet-ports-1000m-n', 1), -- Ports: Ethernet (gigabit) --
	('ethernet-ports-100m-n', 1), -- Ports: Ethernet (100M) --
	('ethernet-ports-10base2-bnc-n', 1), -- Ports: Ethernet (10BASE2 BNC) --
	('ethernet-ports-10m-n', 1), -- Ports: Ethernet (10M) --
	('hdd-odd-form-factor', 2), -- Form factor (HDD/ODD) --
	('ide-ports-n', 1), -- Ports: IDE/ATA --
	('odd-type', 2), -- ODD capabilities --
	('pcie-power-pin-n', 1), -- PCIe power pins --
	('pcie-sockets-n', 1), -- Sockets: PCIe --
	('pci-sockets-n', 1), -- Sockets: PCI --
	('power-connector', 2), -- Power connector --
	('power-idle-watt', 1), -- Power consumption (idle) --
	('power-rated-watt', 1), -- Power (rated) --
	('ps2-ports-n', 1), -- Ports: PS/2 --
	('psu-ampere', 3), -- Output current --
	('psu-connector-motherboard', 2), -- Power connector (motherboard) -- use sata-ports-n and pcie-power-pin-n for that stuff
	('psu-volt', 3), -- Output voltage --
	('ram-type', 2), -- RAM type --
	('sata-ports-n', 1), -- Ports: SATA --
	('software', 0), -- Software --
	('usb-ports-n', 1), -- Ports: USB --
	('vga-ports-n', 1), -- Ports: VGA --
	('os-license-code', 0), -- OS license code --
	('os-license-version', 0), -- OS license version --
	('power-idle-pfc', 0), -- PFC (idle) --
	('firewire-ports-n', 1), -- Ports: Firewire --
	('mini-firewire-ports-n', 1), -- Ports: Mini Firewire --
	('serial-ports-n', 1), -- Ports: Serial (DE-9) -- Also known as RS-232 (which apparently is a standard that also works on DB-25 ports, so don't call them like that)
	('parallel-ports-n', 1), -- Ports: Parallel --
	('ram-form-factor', 2), -- Form factor (RAM) --
	('weight-gram', 1), -- Weight --
	('spin-rate-rpm', 1), -- Rotation speed -- "spin rate" sounded cooler, but myabe I should change it...
	('dms-59-ports-n', 1), -- Ports: DMS-59 -- the weird DVI port which is actually 2 DVI ports in one
	('check', 2), -- Needs to be checked --
	('ram-ecc', 2), -- ECC --
	('other-code', 0), -- Other code(s) --
	('hdmi-ports-n', 1), -- Ports: HDMI --
	('scsi-sca2-ports-n', 1), -- Ports: SCSI SCA2 (80 pin) --
	('scsi-db68-ports-n', 1), -- Ports: SCSI DB68 (68 pin) --
	('mini-ide-ports-n', 1), -- Ports: Mini IDE -- Laptop IDE
	('data-erased', 2), -- Erased -- HDD entirely erased
	('surface-scan', 2), -- Surface scan -- Running badblocks on HDDs
	('smart-data', 2), -- S.M.A.R.T. data checked --
	('wireless-receiver', 2), -- Wireless receiver --
	('rj11-ports-n', 1), -- Ports: RJ11 (modem) --
	('ethernet-ports-10base5-aui-n', 1), -- Ports: Ethernet (10BASE5 AUI) --
	('midi-ports-n', 1), -- Ports: MIDI --
	('mini-jack-ports-n', 1), -- Ports: Mini jack --
	('rca-mono-ports-n', 1), -- Ports: RCA Mono --
	('tv-out-ports-n', 1), -- Ports: TV-out --
	('s-video-ports-n', 1), -- Ports: S-Video --
	('s-video-7pin-ports-n', 1), -- Ports: S-Video (7 pin) --
	('composite-video-ports-n', 1), -- Ports: Composite video --
	('serial-db25-ports-n', 1), -- Ports: DB-25 -- larger kind of serial port
	('isa-sockets-n', 1), -- Sockets: ISA --
	('mini-pcie-sockets-n', 1), -- Sockets: Mini PCIe --
	('mini-pci-sockets-n', 1), -- Sockets: Mini PCI --
	('brand-reseller', 0), -- Brand (reseller) -- TODO: turn it the other way round, use "Brand (manufacturer)"
	('psu-form-factor', 2), -- Form factor (PSU) --
	('cib-old', 0), -- CIB (old) --
	('integrated-graphics-brand', 0), -- Integrated graphics brand -- TODO: replace with brand & model, once namespaced features are implemented
	('integrated-graphics-model', 0), -- Integrated graphics model --
	('restrictions', 2), -- Restrictions --
	('displayport-ports-n', 1), -- Ports: DisplayPort --
	('pci-low-profile', 2), -- PCI low profile --
	('psu-connector-cpu', 2), -- Power connector (CPU) --
	('jae-ports-n', 1), -- Ports: JAE (50 pin laptop ODD) -- Old laptop ODDs use a 50-pin connector which is just an IDE with 10 pins for power, and it's apparently called JAE. Basically no information exist on this connector anywhere on the internet...
	('game-ports-n', 1); -- Ports: Game port -- TODO: merge with midi-ports-n? Some of them may have only one function but nobody really cares anyway

TRUNCATE `FeatureEnum`;
INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES
	('type', 'location'), -- Location --
	('type', 'case'), -- Case --
	('type', 'motherboard'), -- Motherboard --
	('type', 'cpu'), -- CPU --
	('type', 'graphics-card'), -- Graphics card --
	('type', 'ram'), -- RAM --
	('type', 'hdd'), -- HDD --
	('type', 'odd'), -- ODD --
	('type', 'psu'), -- PSU --
	('type', 'audio-card'), -- Audio card --
	('type', 'ethernet-card'), -- Ethernet card --
	('type', 'monitor'), -- Monitor --
	('type', 'mouse'), -- Mouse --
	('type', 'keyboard'), -- Keyboard --
	('type', 'network-switch'), -- Network switch --
	('type', 'network-hub'), -- Network hub --
	('type', 'modem-router'), -- Modem/router --
	('type', 'fdd'), -- FDD --
	('type', 'ports-bracket'), -- Bracket with ports --
	('type', 'other-card'), -- Other internal card --
	('type', 'fan-controller'), -- Fan controller (rheobus) --
	('type', 'modem-card'), -- Modem card --
	('type', 'scsi-card'), -- SCSI card --
	('type', 'wifi-card'), -- WiFi card --
	('type', 'bluetooth-card'), -- Bluetooth card --
	('type', 'external-psu'), -- External PSU --
	('type', 'zip-drive'), -- ZIP drive --
	('type', 'printer'), -- Printer --
	('type', 'scanner'), -- Scanner --
	('type', 'inventoried-object'), -- Other (with invetory sticker) --
	('type', 'adapter'), -- Adapter --
	('type', 'usbhub'), -- USB hub --
	('type', 'tv-card'), -- TV tuner card --
	('working', 'no'), -- No --
	('working', 'yes'), -- Yes --
	('working', 'maybe'), -- Maybe (unclear) --
	('isa', 'x86-32'), -- x86, 32 bit --
	('isa', 'x86-64'), -- x86, 64 bit --
	('isa', 'ia-64'), -- IA-64 --
	('isa', 'arm'), -- ARM --
	('color', 'black'), -- Black --
	('color', 'white'), -- White --
	('color', 'green'), -- Green --
	('color', 'yellow'), -- Yellow --
	('color', 'red'), -- Red --
	('color', 'blue'), -- Blue --
	('color', 'grey'), -- Grey --
	('color', 'darkgrey'), -- Dark grey --
	('color', 'lightgrey'), -- Light grey  --
	('color', 'pink'), -- Pink --
	('color', 'transparent'), -- Transparent --
	('color', 'brown'), -- Brown --
	('color', 'orange'), -- Orange --
	('color', 'violet'), -- Violet --
	('color', 'sip-brown'), -- SIP brown --
	('color', 'lightblue'), -- Light blue --
	('color', 'yellowed'), -- Yellowed --
	('color', 'transparent-dark'), -- Transparent (dark) --
	('color', 'golden'), -- Golden --
	('motherboard-form-factor', 'atx'), -- ATX --
	('motherboard-form-factor', 'miniatx'), -- Mini ATX (not standard) --
	('motherboard-form-factor', 'microatx'), -- Micro ATX --
	('motherboard-form-factor', 'miniitx'), -- Mini ITX --
	('motherboard-form-factor', 'proprietary'), -- Proprietary (desktop) --
	('motherboard-form-factor', 'btx'), -- BTX --
	('motherboard-form-factor', 'wtx'), -- WTX --
	('motherboard-form-factor', 'flexatx'), -- Flex ATX --
	('motherboard-form-factor', 'proprietary-laptop'), -- Laptop -- Who knows, maybe distinguishing proprietary motherboards between desktops and laptops will turn out to be useful...
	('motherboard-form-factor', 'eatx'), -- Extended ATX --
	('cpu-socket', 'other-slot'), -- Other (slot) --
	('cpu-socket', 'other-socket'), -- Other (socket) --
	('cpu-socket', 'other-dip'), -- Other (DIP) --
	('cpu-socket', 'g1'), -- G1 --
	('cpu-socket', 'g2'), -- G2 --
	('cpu-socket', 'socket7'), -- Socket 7 --
	('cpu-socket', 'p'), -- P -- which has 478 pins but it's completely different from socket 478
	('cpu-socket', 'am1'), -- AM1 --
	('cpu-socket', 'am2'), -- AM2 --
	('cpu-socket', 'am2plus'), -- AM2+ --
	('cpu-socket', 'am3'), -- AM3 --
	('cpu-socket', 'am3plus'), -- AM3+ --
	('cpu-socket', 'am4'), -- AM4 --
	('cpu-socket', 'fm1'), -- FM1 --
	('cpu-socket', 'fm2'), -- FM2 --
	('cpu-socket', 'fm2plus'), -- FM2+ --
	('cpu-socket', 'g34'), -- G34 --
	('cpu-socket', 'c32'), -- C32 --
	('cpu-socket', 'g3'), -- G3 (rPGA988A) --
	('cpu-socket', 'slot1'), -- Slot 1 --
	('cpu-socket', 'socket370'), -- 370 --
	('cpu-socket', 'socket462a'), -- 462 (Socket A) -- A aka 462
	('cpu-socket', 'socket423'), -- 423 --
	('cpu-socket', 'socket478'), -- 478 (desktop, mPGA478B) --
	-- There are 3 sockets with multiple names and each one is also called socket 479. And they have 478 pins. Mechanically identical, electrically incompatible.
	('cpu-socket', 'socket479a'), -- 479 (mobile, mPGA478A) --
	('cpu-socket', 'socket479c'), -- 479 (mobile, mPGA478C) --
	('cpu-socket', 'socket479m'), -- 479 (mobile, socket M) --
	('cpu-socket', 'socket495'), -- 495 --
	('cpu-socket', 'socket603'), -- 603 --
	('cpu-socket', 'socket615'), -- 615 --
	('cpu-socket', 'socket754'), -- 754 --
	('cpu-socket', 'socket940'), -- 940 --
	('cpu-socket', 'socket939'), -- 939 --
	('cpu-socket', 'lga775'), -- LGA775 (Socket T) --
	('cpu-socket', 'lga771'), -- LGA771 (Socket J) --
	('cpu-socket', 'lga1366'), -- LGA1366 (Socket B) --
	('cpu-socket', 'lga1156'), -- LGA1156 (Socket H1) --
	('cpu-socket', 'lga1248'), -- LGA1248 --
	('cpu-socket', 'lga1567'), -- LGA1567 --
	('cpu-socket', 'lga1155'), -- LGA1155 (Socket H2) --
	('cpu-socket', 'lga2011'), -- LGA2011 (Socket R) --
	('cpu-socket', 'lga1150'), -- LGA1150 (Socket H3) --
	('cpu-socket', 'lga1151'), -- LGA1151 (Socket H4) --
	('cpu-socket', 'lga3647'), -- LGA3647 --
	('cpu-socket', 'lga2066'), -- LGA2066 --
	('hdd-odd-form-factor', '5.25'), -- 5.25 in. --
	('hdd-odd-form-factor', '3.5'), -- 3.5 in. --
	('hdd-odd-form-factor', '2.5'), -- 2.5 in. -- TODO: remove, they're probably all 9.5 mm
	('hdd-odd-form-factor', '2.5-15mm'), -- 2.5 in. (15 mm thick, uncommon) -- second number is the height in millimeters (these are specified as 15 but most common sizes, for both bays and drives, are 7 mm and 9.5 mm: the more you know...)
	('hdd-odd-form-factor', 'm2'), -- M2 --
	('hdd-odd-form-factor', 'm2.2'), -- M2.2 --
	('hdd-odd-form-factor', '2.5-7mm'), -- 2.5 in. (7 mm thick) --
	('hdd-odd-form-factor', '2.5-9.5mm'), -- 2.5 in. (9.5 mm thick) --
	('hdd-odd-form-factor', 'laptop-odd-standard'), -- Laptop ODD (standard) -- to be replaced with actual name if we ever find what it is
	('hdd-odd-form-factor', 'laptop-odd-slim'), -- Laptop ODD (slim, uncommon) -- I have no idea there was a difference: https://superuser.com/a/276241
	('odd-type', 'cd-r'), -- CD-R --
	('odd-type', 'cd-rw'), -- CD-RW --
	('odd-type', 'dvd-r'), -- DVD-R --
	('odd-type', 'dvd-rw'), -- DVD-RW --
	('odd-type', 'bd-r'), -- BD-R --
	('odd-type', 'bd-rw'), -- BD-RW --
	('power-connector', 'other'), -- Other --
	('power-connector', 'c13'), -- C13/C14 -- C13 is the plug and C14 the inlet but they're "paired"
	('power-connector', 'c19'), -- C19/C20 --
	('power-connector', 'barrel'), -- Barrel (standard) --
	('power-connector', 'miniusb'), -- Mini USB --
	('power-connector', 'microusb'), -- Micro USB --
	('power-connector', 'proprietary'), -- Proprietary --
	('power-connector', 'da-2'), -- Dell DA-2 --
	('psu-connector-motherboard', 'proprietary'), -- Proprietary --
	('psu-connector-motherboard', 'at'), -- AT --
	('psu-connector-motherboard', 'atx-20pin'), -- ATX 20 pin --
	('psu-connector-motherboard', 'atx-24pin'), -- ATX 24 pin --
	('psu-connector-motherboard', 'atx-20pin-aux'), -- ATX 20 pin + AUX -- AUX connector looks kind of like an AT connector
	('ram-type', 'simm'), -- SIMM --
	('ram-type', 'edo'), -- EDO --
	('ram-type', 'sdr'), -- SDR --
	('ram-type', 'ddr'), -- DDR --
	('ram-type', 'ddr2'), -- DDR2 --
	('ram-type', 'ddr3'), -- DDR3 --
	('ram-type', 'ddr4'), -- DDR4 --
	('ram-form-factor', 'simm'), -- SIMM --
	('ram-form-factor', 'dimm'), -- DIMM --
	('ram-form-factor', 'sodimm'), -- SODIMM --
	('ram-form-factor', 'minidimm'), -- Mini DIMM --
	('ram-form-factor', 'microdimm'), -- Micro DIMM --
	('ram-form-factor', 'fbdimm'), -- FB-DIMM --
	('check', 'missing-data'), -- Missing data --
	('check', 'wrong-data'), -- Wrong data --
	('check', 'wrong-location'), -- Wrong location/lost --
	('check', 'wrong-content'), -- Wrong content --
	('check', 'missing-content'), -- Missing content --
	('check', 'wrong-data-and-content'), -- Wrong data and content --
	('check', 'wrong-location-and-data'), -- Wrong location and data (and content) --
	('ram-ecc', 'no'), -- No --
	('ram-ecc', 'yes'), -- Yes --
	-- (65, 0, 'no'), --  --
	('data-erased', 'yes'), -- Yes️ -- Just don't add the feature if it hasn't been erased...
	('surface-scan', 'fail'), -- Failed --
	('surface-scan', 'pass'), -- Passed --
	('smart-data', 'fail'), -- Failed --
	('smart-data', 'old'), -- Old -- old and tired HDDs, but still no reallocated sectors or other serious warnings
	('smart-data', 'ok'), -- Ok --
	-- Wireless receiver: located inside, nearby or missing, making the peripheral completely useless since these are always proprietary
	('wireless-receiver', 'inside'), -- Inside the peripheral --
	('wireless-receiver', 'near'), -- Near the peripheral --
	('wireless-receiver', 'missing'), -- Missing --
	('psu-form-factor', 'atx'), -- ATX --
	('psu-form-factor', 'cfx'), -- CFX -- the wide L-shaped ones
	('psu-form-factor', 'lfx'), -- LFX -- long and L-shaped
	('psu-form-factor', 'sfx-lowprofile'), -- SFX Low Profile -- SFX has lots of variants
	('psu-form-factor', 'sfx-topfan'), -- SFX Topfan --
	('psu-form-factor', 'sfx-topfan-reduceddepth'), -- SFX Topfan reduced depth --
	('psu-form-factor', 'sfx'), -- SFX --
	('psu-form-factor', 'sfx-ps3'), -- SFX PS3 --
	('psu-form-factor', 'sfx-l'), -- SFX-L -- Also called "mATX" or "mini ITX", size claimed to be 125×64×140 mm, NOWHERE to be found in the ATX or SFX specification but multiple models exist for sale right now
	('psu-form-factor', 'tfx'), -- TFX -- I don't even know anymore
	('psu-form-factor', 'flexatx'), -- Flex ATX --
	('psu-form-factor', 'proprietary'), -- Proprietary --
	('psu-form-factor', 'eps'), -- EPS --
	('restrictions', 'loan'), -- Loaned (to be returned) -- borrowed items that should be returned to owner, can't be donated
	('restrictions', 'in-use'), -- In use -- items that shouldn't be donated right now because we're using them (e.g. switch, pc used for invetory management, server)
	('restrictions', 'bought'), -- Bought -- items bought with funds from our annual budget, can't be donated at all ever
	('restrictions', 'training'), -- Training/demonstrations -- PCs to be used for training and demonstrations (because they're old, slow and with the case full of scratches, mostly), but still working and that can be potentially donated
	('restrictions', 'ready'), -- Ready -- Completely "restored", cleaned, OS installed, ready for donation, so don't mess them up
	('restrictions', 'other'), -- Other (cannot be donated) -- "other" also means "cannot be donated right now"
	('pci-low-profile', 'no'), -- No --
	('pci-low-profile', 'possibile'), -- Possible (no bracket) -- no low profile metal thing but the card itself is low profile
	('pci-low-profile', 'dual'), -- Yes (both brackets) -- we've got both the full height and low profile thing
	('pci-low-profile', 'yes'), -- Yes (low profile only) --
	('psu-connector-cpu', 'none'), -- None --
	('psu-connector-cpu', '4pin'), -- 4 pin --
	('psu-connector-cpu', '6pin-hp'), -- 6 pin (HP proprietary) -- 2 black, 2 yellow, 1 purple, 1 blue
	('psu-connector-cpu', '6pin'), -- 6 pin (not standard) --
	('psu-connector-cpu', '8pin'), -- 8 pin --
	('psu-connector-cpu', 'proprietary'); -- Proprietary --
-- TRUNCATE `Codes`;
-- TRUNCATE `Item`;
-- TRUNCATE `ItemFeature`;
-- TRUNCATE `ItemLocationModification`;
-- TRUNCATE `ItemModification`;
-- TRUNCATE `ItemModificationDelete`;
-- TRUNCATE `Modification`;
-- TRUNCATE `Tree`;
-- TRUNCATE `User`;
