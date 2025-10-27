<?php

namespace WEEEOpen\Tarallo;

class BaseFeature
{
	public const STRING = 0;
	public const INTEGER = 1;
	public const ENUM = 2;
	public const DOUBLE = 3;
	public const MULTILINE = 4;

	// Change these numbers for a different sorting
	public const GROUP_COMMERCIAL = 1;
	public const GROUP_GENERAL = 2;
	public const GROUP_HDDPROCEDURES = 3;
	public const GROUP_PHYSICAL = 4;
	public const GROUP_FEATURES = 5;
	public const GROUP_PORTS = 6;
	public const GROUP_SOCKETS = 7;
	public const GROUP_POWER = 8;
	public const GROUP_POWERCONNECTORS = 9;
	public const GROUP_CODES = 10;
	public const GROUP_ADMINISTRATIVE = 11;
	public const GROUP_SOFTWARE = 12;

	// BEGIN GENERATED CODE
	public const FEATURES = [
		'brand' => self::STRING,
		'model' => self::STRING,
		'internal-name' => self::STRING,
		'family' => self::STRING,
		'variant' => self::STRING,
		'key-bios-setup' => self::STRING,
		'key-boot-menu' => self::STRING,
		'owner' => self::STRING,
		'sn' => self::STRING,
		'wwn' => self::STRING,
		'mac' => self::STRING,
		'type' => ['location' => true, 'case' => true, 'motherboard' => true, 'cpu' => true, 'graphics-card' => true, 'ram' => true, 'hdd' => true, 'ssd' => true, 'odd' => true, 'psu' => true, 'audio-card' => true, 'ethernet-card' => true, 'monitor' => true, 'mouse' => true, 'keyboard' => true, 'network-switch' => true, 'network-hub' => true, 'modem-router' => true, 'fdd' => true, 'ports-bracket' => true, 'card-reader' => true, 'other-card' => true, 'fan-controller' => true, 'modem-card' => true, 'storage-card' => true, 'wifi-card' => true, 'bluetooth-card' => true, 'external-psu' => true, 'zip-drive' => true, 'printer' => true, 'scanner' => true, 'inventoried-object' => true, 'adapter' => true, 'usbhub' => true, 'tv-card' => true, 'projector' => true, 'smartphone-tablet' => true],
		'working' => ['no' => true, 'yes' => true, 'maybe' => true],
		'capacity-byte' => self::INTEGER,
		'frequency-hertz' => self::INTEGER,
		'diameter-mm' => self::INTEGER,
		'diagonal-inch' => self::DOUBLE,
		'isa' => ['x86-32' => true, 'x86-64' => true, 'ia-64' => true, 'arm' => true],
		'color' => ['black' => true, 'white' => true, 'green' => true, 'darkgreen' => true, 'olivedrab' => true, 'yellow' => true, 'red' => true, 'blue' => true, 'teal' => true, 'grey' => true, 'silver' => true, 'darkgrey' => true, 'lightgrey' => true, 'pink' => true, 'transparent' => true, 'brown' => true, 'orange' => true, 'violet' => true, 'sip-brown' => true, 'lightblue' => true, 'yellowed' => true, 'transparent-dark' => true, 'golden' => true, 'copper' => true, 'weeerde' => true],
		'motherboard-form-factor' => ['atx' => true, 'miniatx' => true, 'microatx' => true, 'miniitx' => true, 'proprietary' => true, 'btx' => true, 'microbtx' => true, 'nanobtx' => true, 'picobtx' => true, 'wtx' => true, 'flexatx' => true, 'proprietary-laptop' => true, 'eatx' => true],
		'notes' => self::MULTILINE,
		'gotcha' => self::STRING,
		'agp-sockets-n' => self::INTEGER,
		'arrival-batch' => self::STRING,
		'capacity-decibyte' => self::INTEGER,
		'cib' => self::STRING,
		'cib-qr' => self::STRING,
		'core-n' => self::INTEGER,
		'thread-n' => self::INTEGER,
		'cpu-socket' => ['other-slot' => true, 'other-socket' => true, 'other-dip' => true, 'g1' => true, 'g2' => true, 'socket3' => true, 'socket7' => true, 's1' => true, 'p' => true, 'am1' => true, 'am2' => true, 'am2plus' => true, 'am3' => true, 'am3plus' => true, 'am4' => true, 'fs1' => true, 'fm1' => true, 'fm2' => true, 'fm2plus' => true, 'f' => true, 'g34' => true, 'c32' => true, 'g3' => true, 'slot1' => true, 'super7' => true, 'socket370' => true, 'socket462a' => true, 'socket423' => true, 'socket478' => true, 'socket479a' => true, 'socket479c' => true, 'socket479m' => true, 'socket495' => true, 'socket563' => true, 'socket603' => true, 'socket604' => true, 'socket615' => true, 'socket754' => true, 'socket940' => true, 'socket939' => true, 'lga775' => true, 'lga771' => true, 'lga1366' => true, 'lga1156' => true, 'lga1248' => true, 'lga1567' => true, 'lga1155' => true, 'lga2011' => true, 'lga1150' => true, 'lga1151' => true, 'lga2066' => true, 'lga3647' => true, 'soldered' => true],
		'dvi-ports-n' => self::INTEGER,
		'ethernet-ports-1000m-n' => self::INTEGER,
		'ethernet-ports-100m-n' => self::INTEGER,
		'ethernet-ports-10base2-bnc-n' => self::INTEGER,
		'ethernet-ports-10m-n' => self::INTEGER,
		'odd-form-factor' => ['5.25' => true, 'laptop-odd-7mm' => true, 'laptop-odd-8.5mm' => true, 'laptop-odd-9.5mm' => true, 'laptop-odd-12.7mm' => true],
		'hdd-form-factor' => ['3.5' => true, '2.5' => true, '1.8' => true, '1' => true, 'm2' => true],
		'height-mm' => self::DOUBLE,
		'ide-ports-n' => self::INTEGER,
		'odd-type' => ['cd-r' => true, 'cd-rw' => true, 'dvd-r' => true, 'dvd-rw' => true, 'bd-r' => true, 'bd-rw' => true],
		'pcie-power-pin-n' => self::INTEGER,
		'pcie-sockets-n' => self::INTEGER,
		'pci-sockets-n' => self::INTEGER,
		'cnr-sockets-n' => self::INTEGER,
		'power-connector' => ['other' => true, 'c5' => true, 'c7' => true, 'c13' => true, 'c19' => true, 'barrel' => true, 'miniusb' => true, 'microusb' => true, 'usb-c' => true, 'proprietary' => true, 'da-2' => true],
		'power-idle-watt' => self::INTEGER,
		'power-rated-watt' => self::INTEGER,
		'ps2-ports-n' => self::INTEGER,
		'psu-ampere' => self::DOUBLE,
		'psu-connector-motherboard' => ['proprietary' => true, 'at' => true, 'atx-20pin' => true, 'atx-24pin' => true, 'atx-24pin-mini' => true, 'atx-20pin-aux' => true],
		'psu-volt' => self::DOUBLE,
		'ram-type' => ['simm' => true, 'edo' => true, 'sdr' => true, 'ddr' => true, 'ddr2' => true, 'ddr3' => true, 'ddr4' => true ],
		'ram-timings' => self::STRING,
		'sata-ports-n' => self::INTEGER,
		'esata-ports-n' => self::INTEGER,
		'msata-ports-n' => self::INTEGER,
		'sas-sata-ports-n' => self::INTEGER,
		'sas-sff-8087-ports-n' => self::INTEGER,
		'sas-sff-8088-ports-n' => self::INTEGER,
		'software' => self::STRING,
		'video-api' => self::STRING,
		'usb-ports-n' => self::INTEGER,
		'usb-c-ports-n' => self::INTEGER,
		'usb-header-n' => self::INTEGER,
		'internal-header-n' => self::INTEGER,
		'vga-ports-n' => self::INTEGER,
		'os-license-code' => self::STRING,
		'os-license-version' => self::STRING,
		'power-idle-pfc' => self::DOUBLE,
		'firewire-ports-n' => self::INTEGER,
		'mini-firewire-ports-n' => self::INTEGER,
		'serial-ports-n' => self::INTEGER,
		'parallel-ports-n' => self::INTEGER,
		'ram-form-factor' => ['simm' => true, 'dimm' => true, 'sodimm' => true, 'minidimm' => true, 'microdimm' => true, 'fbdimm' => true, 'rdimm' => true],
		'weight-gram' => self::INTEGER,
		'spin-rate-rpm' => self::INTEGER,
		'dms-59-ports-n' => self::INTEGER,
		'check' => ['missing-data' => true, 'missing-content' => true, 'wrong-data' => true, 'wrong-content' => true],
		'todo' => ['transplant' => true, 'install-os' => true, 'repair' => true, 'replace-capacitors' => true, 'remove-from-computer' => true, 'replace-broken-parts' => true, 'replace-elec-components' => true, 'add-parts' => true, 'salvage-parts' => true, 'thermal-paste' => true, 'replace-cmos-battery' => true, 'test-and-inventory' => true, 'see-notes' => true],
		'ram-ecc' => ['no' => true, 'yes' => true],
		'other-code' => self::STRING,
		'hdmi-ports-n' => self::INTEGER,
		'scsi-sca2-ports-n' => self::INTEGER,
		'scsi-db68-ports-n' => self::INTEGER,
		'mini-ide-ports-n' => self::INTEGER,
		'data-erased' => ['yes' => true],
		'surface-scan' => ['fail' => true, 'pass' => true],
		'smart-data' => ['fail' => true, 'old' => true, 'ok' => true, 'sus' => true],
		'wireless-receiver' => ['inside' => true, 'near' => true, 'missing' => true],
		'rj11-ports-n' => self::INTEGER,
		'ethernet-ports-10base5-aui-n' => self::INTEGER,
		'midi-ports-n' => self::INTEGER,
		'mini-jack-ports-n' => self::INTEGER,
		'thunderbolt-ports-n' => self::INTEGER,
		'rca-mono-ports-n' => self::INTEGER,
		'tv-out-ports-n' => self::INTEGER,
		's-video-ports-n' => self::INTEGER,
		's-video-7pin-ports-n' => self::INTEGER,
		'composite-video-ports-n' => self::INTEGER,
		'serial-db25-ports-n' => self::INTEGER,
		'isa-sockets-n' => self::INTEGER,
		'mini-pcie-sockets-n' => self::INTEGER,
		'mini-pci-sockets-n' => self::INTEGER,
		'm2-connectors-n' => self::INTEGER,
		'm2-slot-length-mm' => self::DOUBLE,
		'brand-manufacturer' => self::STRING,
		'psu-form-factor' => ['atx' => true, 'cfx' => true, 'lfx' => true, 'sfx-lowprofile' => true, 'sfx-topfan' => true, 'sfx-topfan-reduceddepth' => true, 'sfx' => true, 'sfx-ps3' => true, 'sfx-l' => true, 'tfx' => true, 'flexatx' => true, 'proprietary' => true, 'eps' => true],
		'psu-rails-most-power' => ['12v' => true, '5v' => true, 'balanced' => true],
		'psu-12v-rail-ampere' => self::DOUBLE,
		'cib-old' => self::STRING,
		'integrated-graphics-brand' => self::STRING,
		'integrated-graphics-model' => self::STRING,
		'restrictions' => ['loan' => true, 'in-use' => true, 'bought' => true, 'showcase' => true, 'ready' => true, 'other' => true],
		'displayport-ports-n' => self::INTEGER,
		'mini-displayport-ports-n' => self::INTEGER,
		'micro-hdmi-ports-n' => self::INTEGER,
		'pci-low-profile' => ['no' => true, 'possibile' => true, 'dual' => true, 'yes' => true],
		'psu-connector-cpu' => ['none' => true, '4pin' => true, '6pin-hp' => true, '6pin-hp-brown' => true, '6pin' => true, '8pin' => true, '8pin4pin' => true, '8pin8pin' => true, 'proprietary' => true],
		'sata-power-n' => self::INTEGER,
		'jae-ports-n' => self::INTEGER,
		'game-ports-n' => self::INTEGER,
	];
	public const GROUPS = [
		'brand' => self::GROUP_COMMERCIAL,
		'model' => self::GROUP_COMMERCIAL,
		'internal-name' => self::GROUP_COMMERCIAL,
		'family' => self::GROUP_COMMERCIAL,
		'variant' => self::GROUP_COMMERCIAL,
		'key-bios-setup' => self::GROUP_SOFTWARE,
		'key-boot-menu' => self::GROUP_SOFTWARE,
		'owner' => self::GROUP_ADMINISTRATIVE,
		'sn' => self::GROUP_CODES,
		'wwn' => self::GROUP_CODES,
		'mac' => self::GROUP_CODES,
		'type' => self::GROUP_GENERAL,
		'working' => self::GROUP_GENERAL,
		'capacity-byte' => self::GROUP_FEATURES,
		'frequency-hertz' => self::GROUP_FEATURES,
		'diameter-mm' => self::GROUP_PHYSICAL,
		'diagonal-inch' => self::GROUP_PHYSICAL,
		'isa' => self::GROUP_FEATURES,
		'color' => self::GROUP_PHYSICAL,
		'motherboard-form-factor' => self::GROUP_PHYSICAL,
		'notes' => self::GROUP_GENERAL,
		'gotcha' => self::GROUP_GENERAL,
		'agp-sockets-n' => self::GROUP_SOCKETS,
		'arrival-batch' => self::GROUP_ADMINISTRATIVE,
		'capacity-decibyte' => self::GROUP_FEATURES,
		'cib' => self::GROUP_ADMINISTRATIVE,
		'cib-qr' => self::GROUP_ADMINISTRATIVE,
		'core-n' => self::GROUP_FEATURES,
		'thread-n' => self::GROUP_FEATURES,
		'cpu-socket' => self::GROUP_SOCKETS,
		'dvi-ports-n' => self::GROUP_PORTS,
		'ethernet-ports-1000m-n' => self::GROUP_PORTS,
		'ethernet-ports-100m-n' => self::GROUP_PORTS,
		'ethernet-ports-10base2-bnc-n' => self::GROUP_PORTS,
		'ethernet-ports-10m-n' => self::GROUP_PORTS,
		'odd-form-factor' => self::GROUP_PHYSICAL,
		'hdd-form-factor' => self::GROUP_PHYSICAL,
		'height-mm' => self::GROUP_PHYSICAL,
		'ide-ports-n' => self::GROUP_PORTS,
		'odd-type' => self::GROUP_FEATURES,
		'pcie-power-pin-n' => self::GROUP_POWERCONNECTORS,
		'pcie-sockets-n' => self::GROUP_SOCKETS,
		'pci-sockets-n' => self::GROUP_SOCKETS,
		'cnr-sockets-n' => self::GROUP_SOCKETS,
		'power-connector' => self::GROUP_POWERCONNECTORS,
		'power-idle-watt' => self::GROUP_POWER,
		'power-rated-watt' => self::GROUP_POWER,
		'ps2-ports-n' => self::GROUP_PORTS,
		'psu-ampere' => self::GROUP_POWER,
		'psu-connector-motherboard' => self::GROUP_POWERCONNECTORS,
		'psu-volt' => self::GROUP_POWER,
		'ram-type' => self::GROUP_FEATURES,
		'ram-timings' => self::GROUP_FEATURES,
		'sata-ports-n' => self::GROUP_PORTS,
		'esata-ports-n' => self::GROUP_PORTS,
		'msata-ports-n' => self::GROUP_PORTS,
		'sas-sata-ports-n' => self::GROUP_PORTS,
		'sas-sff-8087-ports-n' => self::GROUP_PORTS,
		'sas-sff-8088-ports-n' => self::GROUP_PORTS,
		'software' => self::GROUP_SOFTWARE,
		'video-api' => self::GROUP_SOFTWARE,
		'usb-ports-n' => self::GROUP_PORTS,
		'usb-c-ports-n' => self::GROUP_PORTS,
		'usb-header-n' => self::GROUP_PORTS,
		'internal-header-n' => self::GROUP_PORTS,
		'vga-ports-n' => self::GROUP_PORTS,
		'os-license-code' => self::GROUP_CODES,
		'os-license-version' => self::GROUP_CODES,
		'power-idle-pfc' => self::GROUP_POWER,
		'firewire-ports-n' => self::GROUP_PORTS,
		'mini-firewire-ports-n' => self::GROUP_PORTS,
		'serial-ports-n' => self::GROUP_PORTS,
		'parallel-ports-n' => self::GROUP_PORTS,
		'ram-form-factor' => self::GROUP_PHYSICAL,
		'weight-gram' => self::GROUP_PHYSICAL,
		'spin-rate-rpm' => self::GROUP_FEATURES,
		'dms-59-ports-n' => self::GROUP_PORTS,
		'check' => self::GROUP_GENERAL,
		'todo' => self::GROUP_GENERAL,
		'ram-ecc' => self::GROUP_FEATURES,
		'other-code' => self::GROUP_CODES,
		'hdmi-ports-n' => self::GROUP_PORTS,
		'scsi-sca2-ports-n' => self::GROUP_PORTS,
		'scsi-db68-ports-n' => self::GROUP_PORTS,
		'mini-ide-ports-n' => self::GROUP_PORTS,
		'data-erased' => self::GROUP_HDDPROCEDURES,
		'surface-scan' => self::GROUP_HDDPROCEDURES,
		'smart-data' => self::GROUP_HDDPROCEDURES,
		'wireless-receiver' => self::GROUP_FEATURES,
		'rj11-ports-n' => self::GROUP_PORTS,
		'ethernet-ports-10base5-aui-n' => self::GROUP_PORTS,
		'midi-ports-n' => self::GROUP_PORTS,
		'mini-jack-ports-n' => self::GROUP_PORTS,
		'thunderbolt-ports-n' => self::GROUP_PORTS,
		'rca-mono-ports-n' => self::GROUP_PORTS,
		'tv-out-ports-n' => self::GROUP_PORTS,
		's-video-ports-n' => self::GROUP_PORTS,
		's-video-7pin-ports-n' => self::GROUP_PORTS,
		'composite-video-ports-n' => self::GROUP_PORTS,
		'serial-db25-ports-n' => self::GROUP_PORTS,
		'isa-sockets-n' => self::GROUP_SOCKETS,
		'mini-pcie-sockets-n' => self::GROUP_SOCKETS,
		'mini-pci-sockets-n' => self::GROUP_SOCKETS,
		'm2-connectors-n' => self::GROUP_PORTS,
		'm2-slot-length-mm' => self::GROUP_PHYSICAL,
		'brand-manufacturer' => self::GROUP_COMMERCIAL,
		'psu-form-factor' => self::GROUP_PHYSICAL,
		'psu-rails-most-power' => self::GROUP_POWER,
		'psu-12v-rail-ampere' => self::GROUP_POWER,
		'cib-old' => self::GROUP_ADMINISTRATIVE,
		'integrated-graphics-brand' => self::GROUP_FEATURES,
		'integrated-graphics-model' => self::GROUP_FEATURES,
		'restrictions' => self::GROUP_GENERAL,
		'displayport-ports-n' => self::GROUP_PORTS,
		'mini-displayport-ports-n' => self::GROUP_PORTS,
		'micro-hdmi-ports-n' => self::GROUP_PORTS,
		'pci-low-profile' => self::GROUP_FEATURES,
		'psu-connector-cpu' => self::GROUP_POWERCONNECTORS,
		'sata-power-n' => self::GROUP_POWERCONNECTORS,
		'jae-ports-n' => self::GROUP_PORTS,
		'game-ports-n' => self::GROUP_PORTS,
	];
	// END GENERATED CODE

	public const ITEM_ONLY_FEATURES = [
		'brand' => true,
		'model' => true,
		'variant' => true,
		'restrictions' => true,
		'working' => true,
		'cib-qr' => true,
		'cib' => true,
		'cib-old' => true,
		'other-code' => true,
		'os-license-version' => true,
		'os-license-code' => true,
		'mac' => true,
		'sn' => true,
		'wwn' => true,
		'arrival-batch' => true,
		'owner' => true,
		'data-erased' => true,
		'surface-scan' => true,
		'smart-data' => true,
		'software' => true,
		'notes' => true,
		'todo' => true,
		'check' => true,
	];

	public static function fileLastModified(): int
	{
		return filemtime(__FILE__);
	}

	/**
	 * Feature name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Feature type
	 *
	 * @var int
	 */
	public $type;

	public function __construct($name)
	{
		self::validateFeatureName($name);
		$this->name = $name;
		$this->type = self::getType($name);
	}

	/**
	 * Obtain group.
	 *
	 * @param string $name Feature name
	 *
	 * @return int
	 */
	public static function getGroup($name)
	{
		if (!isset(self::GROUPS[$name])) {
			throw new \InvalidArgumentException("Cannot get group for feature $name: doesn't exist");
		}
		return self::GROUPS[$name];
	}

	/**
	 * Check that a name is valid
	 *
	 * @param string $name
	 */
	public static function validateFeatureName($name)
	{
		if (!is_string($name)) {
			throw new \InvalidArgumentException('Feature name must be a string, ' . gettype($name) . ' given');
		}
		if (!isset(self::FEATURES[$name])) {
			throw new \InvalidArgumentException('Unknown feature name: ' . $name);
		}
	}

	/**
	 * Obtain type.
	 *
	 * @param string $name Feature name
	 *
	 * @return int
	 */
	public static function getType(string $name): int
	{
		if (!isset(self::FEATURES[$name])) {
			throw new \InvalidArgumentException("Cannot get type for feature $name: doesn't exist");
		}
		return is_int(self::FEATURES[$name]) ? self::FEATURES[$name] : self::ENUM;
	}

	/**
	 * Get available options in an enum feature
	 *
	 * @param string $name Feature name
	 *
	 * @return boolean[] map from feature value to true, because reasons.
	 */
	public static function getOptions(string $name): array
	{
		if (self::getType($name) !== self::ENUM) {
			throw new \InvalidArgumentException("Feature $name is not an enum");
		}
		return self::FEATURES[$name];
	}


	public function __set($name, $value)
	{
		throw new \LogicException('Feature values are read-only');
	}

	public function __toString()
	{
		return (string) $this->name;
	}
}
