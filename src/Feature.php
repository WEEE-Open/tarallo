<?php

namespace WEEEOpen\Tarallo\Server;


class Feature {
	const STRING = 0;
	const INTEGER = 1;
	const ENUM = 2;
	const DOUBLE = 3;
	// BEGIN GENERATED CODE
	const features = [
		'brand' => self::STRING,
		'model' => self::STRING,
		'family' => self::STRING,
		'variant' => self::STRING,
		'key-bios-setup' => self::STRING,
		'key-boot-menu' => self::STRING,
		'owner' => self::STRING,
		'sn' => self::STRING,
		'mac' => self::STRING,
		'type' => ['location' => true, 'case' => true, 'motherboard' => true, 'cpu' => true, 'graphics-card' => true, 'ram' => true, 'hdd' => true, 'odd' => true, 'psu' => true, 'audio-card' => true, 'ethernet-card' => true, 'monitor' => true, 'mouse' => true, 'keyboard' => true, 'network-switch' => true, 'network-hub' => true, 'modem-router' => true, 'fdd' => true, 'ports-bracket' => true, 'other-card' => true, 'fan-controller' => true, 'modem-card' => true, 'scsi-card' => true, 'wifi-card' => true, 'bluetooth-card' => true, 'external-psu' => true, 'zip-drive' => true, 'printer' => true, 'scanner' => true, 'inventoried-object' => true, 'adapter' => true, 'usbhub' => true, 'tv-card' => true],
		'working' => ['no' => true, 'yes' => true, 'maybe' => true],
		'capacity-byte' => self::INTEGER,
		'frequency-hertz' => self::INTEGER,
		'diameter-mm' => self::INTEGER,
		'diagonal-inch' => self::INTEGER,
		'isa' => ['x86-32' => true, 'x86-64' => true, 'ia-64' => true, 'arm' => true],
		'color' => ['black' => true, 'white' => true, 'green' => true, 'yellow' => true, 'red' => true, 'blue' => true, 'grey' => true, 'darkgrey' => true, 'lightgrey' => true, 'pink' => true, 'transparent' => true, 'brown' => true, 'orange' => true, 'violet' => true, 'sip-brown' => true, 'lightblue' => true, 'yellowed' => true, 'transparent-dark' => true, 'golden' => true],
		'motherboard-form-factor' => ['atx' => true, 'miniatx' => true, 'microatx' => true, 'miniitx' => true, 'proprietary' => true, 'btx' => true, 'microbtx' => true, 'nanobtx' => true, 'picobtx' => true, 'wtx' => true, 'flexatx' => true, 'proprietary-laptop' => true, 'eatx' => true],
		'notes' => self::STRING,
		'agp-sockets-n' => self::INTEGER,
		'arrival-batch' => self::STRING,
		'capacity-decibyte' => self::INTEGER,
		'cib' => self::STRING,
		'core-n' => self::INTEGER,
		'cpu-socket' => ['other-slot' => true, 'other-socket' => true, 'other-dip' => true, 'g1' => true, 'g2' => true, 'socket3' => true, 'socket7' => true, 'p' => true, 'am1' => true, 'am2' => true, 'am2plus' => true, 'am3' => true, 'am3plus' => true, 'am4' => true, 'fm1' => true, 'fm2' => true, 'fm2plus' => true, 'g34' => true, 'c32' => true, 'g3' => true, 'slot1' => true, 'super7' => true, 'socket370' => true, 'socket462a' => true, 'socket423' => true, 'socket478' => true, 'socket479a' => true, 'socket479c' => true, 'socket479m' => true, 'socket495' => true, 'socket603' => true, 'socket604' => true, 'socket615' => true, 'socket754' => true, 'socket940' => true, 'socket939' => true, 'ufc-bga956' => true, 'lga775' => true, 'lga771' => true, 'lga1366' => true, 'lga1156' => true, 'lga1248' => true, 'lga1567' => true, 'lga1155' => true, 'lga2011' => true, 'lga1150' => true, 'lga1151' => true, 'lga2066' => true, 'lga3647' => true],
		'dvi-ports-n' => self::INTEGER,
		'ethernet-ports-1000m-n' => self::INTEGER,
		'ethernet-ports-100m-n' => self::INTEGER,
		'ethernet-ports-10base2-bnc-n' => self::INTEGER,
		'ethernet-ports-10m-n' => self::INTEGER,
		'hdd-odd-form-factor' => ['5.25' => true, '3.5' => true, '2.5-15mm' => true, '2.5-7mm' => true, '2.5-9.5mm' => true, 'm2' => true, 'm2.2' => true, 'laptop-odd-7mm' => true, 'laptop-odd-8.5mm' => true, 'laptop-odd-9.5mm' => true, 'laptop-odd-12.7mm' => true],
		'ide-ports-n' => self::INTEGER,
		'odd-type' => ['cd-r' => true, 'cd-rw' => true, 'dvd-r' => true, 'dvd-rw' => true, 'bd-r' => true, 'bd-rw' => true],
		'pcie-power-pin-n' => self::INTEGER,
		'pcie-sockets-n' => self::INTEGER,
		'pci-sockets-n' => self::INTEGER,
		'power-connector' => ['other' => true, 'c13' => true, 'c19' => true, 'barrel' => true, 'miniusb' => true, 'microusb' => true, 'proprietary' => true, 'da-2' => true],
		'power-idle-watt' => self::INTEGER,
		'power-rated-watt' => self::INTEGER,
		'ps2-ports-n' => self::INTEGER,
		'psu-ampere' => self::DOUBLE,
		'psu-connector-motherboard' => ['proprietary' => true, 'at' => true, 'atx-20pin' => true, 'atx-24pin' => true, 'atx-24pin-mini' => true, 'atx-20pin-aux' => true],
		'psu-volt' => self::DOUBLE,
		'ram-type' => ['simm' => true, 'edo' => true, 'sdr' => true, 'ddr' => true, 'ddr2' => true, 'ddr3' => true, 'ddr4' => true],
		'sata-ports-n' => self::INTEGER,
		'software' => self::STRING,
		'usb-ports-n' => self::INTEGER,
		'usb-header-n' => self::INTEGER,
		'internal-header-n' => self::INTEGER,
		'vga-ports-n' => self::INTEGER,
		'os-license-code' => self::STRING,
		'os-license-version' => self::STRING,
		'power-idle-pfc' => self::STRING,
		'firewire-ports-n' => self::INTEGER,
		'mini-firewire-ports-n' => self::INTEGER,
		'serial-ports-n' => self::INTEGER,
		'parallel-ports-n' => self::INTEGER,
		'ram-form-factor' => ['simm' => true, 'dimm' => true, 'sodimm' => true, 'minidimm' => true, 'microdimm' => true, 'fbdimm' => true],
		'weight-gram' => self::INTEGER,
		'spin-rate-rpm' => self::INTEGER,
		'dms-59-ports-n' => self::INTEGER,
		'check' => ['missing-data' => true, 'wrong-data' => true, 'wrong-location' => true, 'wrong-content' => true, 'missing-content' => true, 'wrong-data-and-content' => true, 'wrong-location-and-data' => true],
		'ram-ecc' => ['no' => true, 'yes' => true],
		'other-code' => self::STRING,
		'hdmi-ports-n' => self::INTEGER,
		'scsi-sca2-ports-n' => self::INTEGER,
		'scsi-db68-ports-n' => self::INTEGER,
		'mini-ide-ports-n' => self::INTEGER,
		'data-erased' => ['yes' => true],
		'surface-scan' => ['fail' => true, 'pass' => true],
		'smart-data' => ['fail' => true, 'old' => true, 'ok' => true],
		'wireless-receiver' => ['inside' => true, 'near' => true, 'missing' => true],
		'rj11-ports-n' => self::INTEGER,
		'ethernet-ports-10base5-aui-n' => self::INTEGER,
		'midi-ports-n' => self::INTEGER,
		'mini-jack-ports-n' => self::INTEGER,
		'rca-mono-ports-n' => self::INTEGER,
		'tv-out-ports-n' => self::INTEGER,
		's-video-ports-n' => self::INTEGER,
		's-video-7pin-ports-n' => self::INTEGER,
		'composite-video-ports-n' => self::INTEGER,
		'serial-db25-ports-n' => self::INTEGER,
		'isa-sockets-n' => self::INTEGER,
		'mini-pcie-sockets-n' => self::INTEGER,
		'mini-pci-sockets-n' => self::INTEGER,
		'brand-manufacturer' => self::STRING,
		'psu-form-factor' => ['atx' => true, 'cfx' => true, 'lfx' => true, 'sfx-lowprofile' => true, 'sfx-topfan' => true, 'sfx-topfan-reduceddepth' => true, 'sfx' => true, 'sfx-ps3' => true, 'sfx-l' => true, 'tfx' => true, 'flexatx' => true, 'proprietary' => true, 'eps' => true],
		'cib-old' => self::STRING,
		'integrated-graphics-brand' => self::STRING,
		'integrated-graphics-model' => self::STRING,
		'restrictions' => ['loan' => true, 'in-use' => true, 'bought' => true, 'training' => true, 'ready' => true, 'other' => true],
		'displayport-ports-n' => self::INTEGER,
		'pci-low-profile' => ['no' => true, 'possibile' => true, 'dual' => true, 'yes' => true],
		'psu-connector-cpu' => ['none' => true, '4pin' => true, '6pin-hp' => true, '6pin-hp-brown' => true, '6pin' => true, '8pin' => true, 'proprietary' => true],
		'jae-ports-n' => self::INTEGER,
		'game-ports-n' => self::INTEGER,
	];
	// END GENERATED CODE

	/**
	 * Feature name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Feature value
	 *
	 * @var string|int|double
	 */
	public $value;

	/**
	 * Feature type
	 *
	 * @var int
	 */
	public $type;

	/**
	 * Feature constructor.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __construct($name, $value) {
		self::validateFeatureName($name);
		self::validateValue($name, $value);
		$this->name = $name;
		$this->value = $value;
		$this->type = self::getType($name);
	}

	/**
	 * Get a feature with value of correct type,
	 * even if you only have it as string
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return Feature
	 */
	public static function ofString($name, $value) {
		self::validateFeatureName($name);
		switch(Feature::getType($name)) {
			case Feature::INTEGER:
				if(!is_numeric($value)) {
					throw new \InvalidArgumentException("Cannot cast feature $name to integer: $value is not numeric");
				}
				$value = (int) $value;
				break;
			case Feature::DOUBLE:
				if(!is_numeric($value)) {
					throw new \InvalidArgumentException("Cannot cast feature $name to double: $value is not numeric");
				}
				$value = (double) $value;
				break;
		}
		return new self($name, $value);
	}

	public function __set($name, $value) {
		throw new \LogicException('Feature values are read-only');
	}

	/**
	 * Check that a name is valid
	 *
	 * @param string $name
	 */
	private static function validateFeatureName($name) {
		if(!is_string($name)) {
			throw new \InvalidArgumentException('Feature name must be a string, ' . gettype($name) . ' given');
		}
		if(!isset(self::features[$name])) {
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
	public static function getType($name) {
		if(!isset(self::features[$name])) {
			throw new \InvalidArgumentException("Cannot get type for feature $name: doesn't exist");
		}
		return is_int(self::features[$name]) ? self::features[$name] : self::ENUM;
	}

	/**
	 * Check that a value is valid
	 *
	 * @param string $name Feature name
	 * @param string|int|double $value Value
	 */
	private static function validateValue($name, $value) {
		$type = self::getType($name);
		switch($type) {
			case self::STRING:
				if(!is_string($value)) {
					throw new \InvalidArgumentException('Feature value for ' . $name . ' must be string, ' . gettype($value) . ' given');
				}
				break;
			case self::INTEGER:
				if(!is_int($value)) {
					throw new \InvalidArgumentException('Feature value for ' . $name . ' must be integer, ' . gettype($value) . ' given');
				}
				if($value < 0) {
					throw new \InvalidArgumentException('Feature value for ' . $name . ' must be a positive integer, ' . $value . ' given');
				}
				break;
			case self::DOUBLE:
				if(!is_double($value)) {
					throw new \InvalidArgumentException('Feature value for ' . $name . ' must be double, ' . gettype($value) . ' given');
				}
				if($value < 0) {
					throw new \InvalidArgumentException('Feature value for ' . $name . ' must be a positive double, ' . $value . ' given');
				}
				break;
			case self::ENUM:
				if(!isset(self::features[$name][$value])) {
					throw new \InvalidArgumentException('Feature value for ' . $name . ' is not among acceptable ones: ' . $value . ' given');
				}
				break;
			default:
				throw new \InvalidArgumentException('Unknown feature type: ' . $type);
		}
	}

	/**
	 * Get available options in an enum feature
	 *
	 * @param Feature $feature
	 *
	 * @return boolean[] map from feature value to true, because reasons.
	 */
	public static function getOptions(Feature $feature): array {
		$name = $feature->name;
		if(self::getType($name) !== self::ENUM) {
			throw new \InvalidArgumentException("Feature $name is not an enum");
		}
		return self::features[$name];
	}

	public function __toString() {
		return (string) $this->value;
	}
}
