#!/usr/bin/php
<?php

use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\Product;
use WEEEOpen\Tarallo\ProductCode;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

if(!defined('TARALLO_DEVELOPMENT_ENVIRONMENT') || !TARALLO_DEVELOPMENT_ENVIRONMENT) {
	echo 'TARALLO_DEVELOPMENT_ENVIRONMENT is not set or false, set it to true to generate example items. The database will remain empty otherwise.' . PHP_EOL;
	exit(1);
}

$token = 'yoLeCHmEhNNseN0BlG0s3A:ksfPYziGg7ebj0goT0Zc7pbmQEIYvZpRTIkwuscAM_k';
$tokenId = explode(':', $token, 2)[0];

$pdo = new PDO(TARALLO_DB_DSN, TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_CASE => PDO::CASE_NATURAL,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_AUTOCOMMIT => false,
	PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE Tree;");
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE ItemFeature;");
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE ProductFeature;");
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE BulkTable;");
// Cannot TRUNCATE tables that are referenced in a foreign key (doing a normal DELETE automatically is probably too much work)
// Cannot DELETE too, because it deadlocks instantly when trying to clear the **empty** SearchResult table: "General error: 1205 Lock wait timeout exceeded;"
// There are also a few "circular" foreign keys that every other DBMS on the planet can handle correctly but MariaDB/MySQL cannot (there's an explanation in a trigger in database-procedures.sql)
// So here we go again, SET FOREIGN_KEY_CHECKS = 0...
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Audit; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE AuditProduct; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Item; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Product; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE SearchResult; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Search; SET FOREIGN_KEY_CHECKS = 1;");

$pdo->beginTransaction();
$pdo->exec(/** @lang MariaDB */ "DELETE FROM SessionToken WHERE Token = '$tokenId';");
$pdo->exec(/** @lang MariaDB */ "INSERT INTO SessionToken(Token, Hash, Data, Owner) VALUES ('$tokenId', '\$2y\$10\$NiVbBb6pO3ck5ugXGKk2CeFEW83cmfDCCRjcda9f0DhAUoGPL71C6','O:29:\"WEEEOpen\\\Tarallo\\\SessionLocal\":3:{s:11:\"description\";s:10:\"See README\";s:5:\"level\";i:0;s:5:\"owner\";s:8:\"dev.user\";}', 'dev.user');");
$pdo->commit();

$db = new Database(TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, TARALLO_DB_DSN);
$db->sessionDAO()->setAuditUsername('ExampleScript');
$db->productDAO()->addProduct(new Product("Samsong", "KAI39"));
$db->productDAO()->addProduct(new Product("Caste", "Payton", "Brutto"));
$db->productDAO()->addProduct((new Product("AMD", "Opteron 3300", "AJEJE"))
	->addFeature(new Feature('frequency-hertz', 3000000000))
	->addFeature(new Feature('isa', 'x86-64'))
	->addFeature(new Feature('cpu-socket', 'am3'))
	->addFeature(new Feature('internal-name', 'AM1234567BR4Z0'))
	->addFeature(new Feature('type', 'cpu')));
$db->productDAO()->addProduct((new Product("Intel", "Centryno", "SL666"))
	->addFeature(new Feature('frequency-hertz', 1500000000))
	->addFeature(new Feature('isa', 'x86-64'))
	->addFeature(new Feature('cpu-socket', 'lga771'))
	->addFeature(new Feature('type', 'cpu')));
$db->productDAO()->addProduct((new Product("Intel", "Centryno", "SL7AB"))
	->addFeature(new Feature('frequency-hertz', 1500000000))
	->addFeature(new Feature('isa', 'x86-64'))
	->addFeature(new Feature('cpu-socket', 'lga771'))
	->addFeature(new Feature('type', 'cpu')));
$db->productDAO()->addProduct((new Product("Intel", "Centryno", "SL88C"))
	->addFeature(new Feature('frequency-hertz', 1500000000))
	->addFeature(new Feature('isa', 'x86-64'))
	->addFeature(new Feature('cpu-socket', 'lga771'))
	->addFeature(new Feature('type', 'cpu')));
$db->productDAO()->addProduct((new Product("Intel", "MB346789", "v2.0"))
	->addFeature(new Feature('color', 'green'))
	->addFeature(new Feature('cpu-socket', 'lga771'))
	->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
	->addFeature(new Feature('parallel-ports-n', 1))
	->addFeature(new Feature('serial-ports-n', 1))
	->addFeature(new Feature('ps2-ports-n', 3))
	->addFeature(new Feature('usb-ports-n', 4))
	->addFeature(new Feature('ram-form-factor', 'dimm'))
	->addFeature(new Feature('ram-type', 'ddr2'))
	->addFeature(new Feature('type', 'motherboard')));
$db->productDAO()->addProduct((new Product("Foo Bar Industrial Motherboards", "K987AB", "v1.1"))
	->addFeature(new Feature('color', 'red'))
	->addFeature(new Feature('internal-name', 'MS-427682'))
	->addFeature(new Feature('brand-manufacturer', 'Macro Star International'))
	->addFeature(new Feature('cpu-socket', 'lga775'))
	->addFeature(new Feature('motherboard-form-factor', 'atx'))
	->addFeature(new Feature('parallel-ports-n', 1))
	->addFeature(new Feature('serial-ports-n', 1))
	->addFeature(new Feature('ps2-ports-n', 2))
	->addFeature(new Feature('usb-ports-n', 8))
	->addFeature(new Feature('pci-sockets-n', 4))
	->addFeature(new Feature('pcie-sockets-n', 2))
	->addFeature(new Feature('ram-form-factor', 'dimm'))
	->addFeature(new Feature('ram-type', 'ddr2'))
	->addFeature(new Feature('type', 'motherboard')));
$db->productDAO()->addProduct((new Product("eMac", "EZ1600", "boh"))
	->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
	->addFeature(new Feature('color', 'white'))
	->addFeature(new Feature('type', 'case')));
$db->productDAO()->addProduct((new Product("Dill", "PessimPlex DI-360", "SFF"))
	->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
	->addFeature(new Feature('family', 'PessimPlex'))
	->addFeature(new Feature('color', 'grey'))
	->addFeature(new Feature('type', 'case')));
foreach([256, 512, 1024, 2048] as $size) {
	foreach([667, 800] as $freq) {
		$db->productDAO()->addProduct(
			(new Product("Samsung", "S${freq}ABC" . $size, "v1"))
				->addFeature(new Feature('capacity-byte', $size * 1024 * 1024))
				->addFeature(new Feature('frequency-hertz', $freq * 1000 * 1000))
				->addFeature(new Feature('color', 'green'))
				->addFeature(new Feature('ram-ecc', 'no'))
				->addFeature(new Feature('ram-type', 'ddr2'))
				->addFeature(new Feature('ram-form-factor', 'dimm'))
				->addFeature(new Feature('type', 'ram'))
		);
	}
	foreach([667, 800] as $freq) {
		$db->productDAO()->addProduct(
			(new Product("Samsung", "S${freq}ABC" . $size, "v2"))
				->addFeature(new Feature('capacity-byte', $size * 1024 * 1024))
				->addFeature(new Feature('frequency-hertz', $freq * 1000 * 1000))
				->addFeature(new Feature('color', 'blue'))
				->addFeature(new Feature('ram-ecc', 'no'))
				->addFeature(new Feature('ram-type', 'ddr2'))
				->addFeature(new Feature('ram-form-factor', 'dimm'))
				->addFeature(new Feature('type', 'ram'))
		);
	}
}

$polito = (new Item('Polito'))->addFeature(new Feature('type', 'location'));
$chernobyl = (new Item('Chernobyl'))->addFeature(new Feature('type', 'location'))->addFeature(new Feature('color', 'grey'));
$polito->addContent($chernobyl);
$table = (new Item('Table'))->addFeature(new Feature('type', 'location'))->addFeature(new Feature('color', 'white'));
$bluezone = (new Item('BlueZone'))->addFeature(new Feature('type', 'location'))->addFeature(new Feature('color', 'blue'));
$chernobyl->addContent($table);
$rambox = (new Item('RamBox'))->addFeature(new Feature('type', 'location'))->addFeature(new Feature('color', 'red'));
$table->addContent($rambox);

$pc20 = (new Item('PC20'))
	->addFeature(new Feature('brand', 'Dill'))
	->addFeature(new Feature('model', 'PessimPlex DI-360'))
	->addFeature(new Feature('variant', 'SFF'))
	->addFeature(new Feature('owner', 'DISAT'))
	->addFeature(new Feature('working', 'yes'));
$pc90 = (new Item('PC90'))
	->addFeature(new Feature('brand', 'Dill'))
	->addFeature(new Feature('model', 'PessimPlex DI-360'))
	->addFeature(new Feature('variant', 'SFF'))
	->addFeature(new Feature('todo', 'install-os'))
	->addFeature(new Feature('owner', 'DISAT'))
	->addFeature(new Feature('working', 'yes'));
$pc55 = (new Item('PC55'))
	->addFeature(new Feature('brand', 'TI'))
	->addFeature(new Feature('model', 'GreyPC-\'98'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('owner', 'DISAT'))
	->addFeature(new Feature('motherboard-form-factor', 'atx'));
$pc22 = (new Item('PC22'))
	->addFeature(new Feature('brand', 'Dill'))
	->addFeature(new Feature('model', 'PessimPlex DI-360'))
	->addFeature(new Feature('variant', 'SFF'))
	->addFeature(new Feature('color', 'black')) // override
	->addFeature(new Feature('owner', 'DISAT'))
	->addFeature(new Feature('working', 'yes'));
$SCHIFOMACCHINA = (new Item('SCHIFOMACCHINA'))
	->addFeature(new Feature('brand', 'eMac'))
	->addFeature(new Feature('model', 'EZ1600'))
	->addFeature(new Feature('owner', 'Area IT'))
	->addFeature(new Feature('variant', 'boh'));
$SCHIFOMACCHINA->addContent((new Item('B25'))
	->addFeature(new Feature('brand', 'Intel'))
	->addFeature(new Feature('model', 'MB346789'))
	->addFeature(new Feature('variant', 'v2.0'))
	->addFeature(new Feature('working', 'yes'))
	->addContent((new Item('R20'))
		->addFeature(new Feature('brand', 'Samsung'))
		->addFeature(new Feature('model', 'S667ABC512'))
		->addFeature(new Feature('variant', 'v1'))
		->addFeature(new Feature('owner', 'DISAT'))
		->addFeature(new Feature('sn', 'ASD' . strtoupper(substr(crc32(512), 0, 5) . rand(100000, 999999) . dechex(rand(0,255)))))
		->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'))
	)
	->addContent((new Item('R21'))
		->addFeature(new Feature('brand', 'Samsung'))
		->addFeature(new Feature('model', 'S667ABC512'))
		->addFeature(new Feature('variant', 'v1'))
		->addFeature(new Feature('owner', 'DISAT'))
		->addFeature(new Feature('sn', 'ASD' . strtoupper(substr(crc32(512), 0, 5) . rand(100000, 999999) . dechex(rand(0,255)))))
		->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'))
	));

$testpcs = [];
// No variant but a product exists
$testpcs[] = (new Item('PC100'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('brand', 'Samsong'))
	->addFeature(new Feature('model', 'KAI39'))
	->addFeature(new Feature('usb-ports-n', 4))
	->addFeature(new Feature('firewire-ports-n', 1))
	->addFeature(new Feature('color', 'yellowed'))
	->addFeature(new Feature('working', 'yes'))
	->addFeature(new Feature('owner', 'DISAT'));
// No variant, no product
$testpcs[] = (new Item('PC101'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('brand', 'Oildata'))
	->addFeature(new Feature('model', 'OL4278A'))
	->addFeature(new Feature('usb-ports-n', 2))
	->addFeature(new Feature('color', 'red'))
	->addFeature(new Feature('working', 'no'))
	->addFeature(new Feature('owner', 'DAUIN'));
// Variant but no product
$testpcs[] = (new Item('PC102'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('brand', 'Oildata'))
	->addFeature(new Feature('model', 'OL4278A'))
	->addFeature(new Feature('variant', 'rev 2.5'))
	->addFeature(new Feature('usb-ports-n', 2))
	->addFeature(new Feature('color', 'red'))
	->addFeature(new Feature('working', 'no'))
	->addFeature(new Feature('owner', 'DAUIN'));

// RAM(DOM) GENERATOR 2000
for($i = 100; $i < 250; $i++) {
	$ramSize = pow(2, rand(8, 11));
	$freq = ['667', '800'][rand(0, 1)];
	$ram = (new Item('R' . $i))
		->addFeature(new Feature('brand', 'Samsung'))
		->addFeature(new Feature('model', 'S' . $freq . 'ABC' . $ramSize))
		->addFeature(new Feature('variant', 'v' . rand(1,2)))
		->addFeature(new Feature('sn', 'ASD' . strtoupper(substr(crc32($ramSize), 0, 5) . rand(100000, 999999) . dechex(rand(0,255)))))
		->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'));
	$rambox->addContent($ram);
}
for($i = 250; $i < 350; $i++) {
	$ramSize = pow(2, rand(8, 11));
	$ram = (new Item('R' . $i))
		->addFeature(new Feature('brand', 'Samsung'))
		->addFeature(new Feature('model', 'S667ABC' . $ramSize))
		->addFeature(new Feature('variant', 'v1'))
		->addFeature(new Feature('sn', 'ASD' . strtoupper(substr(crc32($ramSize), 0, 5) . rand(100000, 999999) . dechex(rand(0,255)))));
	$rambox->addContent($ram);
}
$ram = (new Item('R69'))
	->addFeature(new Feature('check', 'missing-data'))
	->addFeature(new Feature('notes', 'example RAM with missing data'))
	->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'))
	->addFeature(new Feature('type', 'ram'));
$rambox->addContent($ram);

$ram = (new Item('R70'))
	->addFeature(new Feature('capacity-byte', 1024 * 1024 * 1024))
	->addFeature(new Feature('frequency-hertz', 667 * 1000 * 1000))
	->addFeature(new Feature('color', 'yellow'))
	->addFeature(new Feature('ram-ecc', 'no'))
	->addFeature(new Feature('ram-type', 'ddr2'))
	->addFeature(new Feature('ram-form-factor', 'sodimm'))
	->addFeature(new Feature('type', 'ram'))
	->addFeature(new Feature('notes', 'example RAM without brand/model/variant (1)'));
$ram = (new Item('R70'))
	->addFeature(new Feature('capacity-byte', 1024 * 1024 * 1024))
	->addFeature(new Feature('frequency-hertz', 800 * 1000 * 1000))
	->addFeature(new Feature('color', 'yellow'))
	->addFeature(new Feature('ram-ecc', 'no'))
	->addFeature(new Feature('ram-type', 'ddr2'))
	->addFeature(new Feature('ram-form-factor', 'sodimm'))
	->addFeature(new Feature('type', 'ram'))
	->addFeature(new Feature('notes', 'example RAM without brand/model/variant (2)'));
$rambox->addContent($ram);

$ram666 = (new Item('R666'))
	->addFeature(new Feature('brand', 'Samsung'))
	->addFeature(new Feature('model', 'S667ABC1024'))
	->addFeature(new Feature('variant', 'v1'))
	->addFeature(new Feature('notes', 'example lost RAM'))
	->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'))
	->addFeature(new Feature('type', 'ram'));
$rambox->addContent($ram666);

foreach([777, 778, 779] as $item) {
	$ram = (new Item('R' . $item))
		->addFeature(new Feature('brand', 'Samsung'))
		->addFeature(new Feature('model', 'S667ABC512'))
		->addFeature(new Feature('variant', 'v1'))
		->addFeature(new Feature('sn', 'ASD' . substr(strtoupper(md5('512')), 0, 5) . '123456'))
		->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'));
	$rambox->addContent($ram);
}

$ram = (new Item('R6969'))
	->addFeature(new Feature('brand', 'Samsung'))
	->addFeature(new Feature('model', 'S667ABC512'))
	->addFeature(new Feature('variant', 'v3'))
//	->addFeature(new Feature('color', 'red'))
	->addFeature(new Feature('sn', 'ASD' . substr(strtoupper(md5('512')), 0, 5) . '123456'))
	->addFeature(new Feature('working', 'no'));
$rambox->addContent($ram);

///H.D.D generator (Huge Deficit Disks)
for($i = 100; $i < 250; $i++){
	$hdd = (new Item('HDD' . $i))
		->addFeature(new Feature('brand', 'PrinceStone'))
		->addFeature(new Feature('model', 'Ultrakek'))
		->addFeature(new Feature('variant', 'v2'))
		->addFeature(new Feature('working', rand(0, 1) ? 'yes' : 'no'))
		->addFeature(new Feature('type', 'hdd'))
		->addFeature(new Feature(rand(0, 1) ? 'ide-ports-n' : 'sata-ports-n', rand(1, 4)))
		->addFeature(new Feature('capacity-decibyte', [40, 80, 160, 256, 512, 1024, 2048][rand(0, 6)] * 1000000000))
		->addFeature(new Feature('hdd-form-factor', ['3.5','1.8-5mm', '2.5-9.5mm'][rand(0, 2)]))
		->addFeature(new Feature('spin-rate-rpm', [7200, 5400, 10025][rand(0, 2)]));
	if(rand(0, 1))
		$hdd->addFeature(new Feature('data-erased', 'yes'));
	if($i < 240){
		$hdd->addFeature(new Feature('surface-scan', rand(0, 1) ? 'pass' : 'fail'))
			->addFeature(new Feature('smart-data', ['ok', 'fail', 'old'][rand(0, 2)]));
	}
	$table->addContent($hdd);
}

$lonelyCpu = (new Item('C1'))
	->addFeature(new Feature('brand', 'AMD'))
	->addFeature(new Feature('model', 'Opteron 3300'))
	->addFeature(new Feature('variant', 'AJEJE'))
	->setProduct($db->productDAO()->getProduct(new ProductCode('AMD', 'Opteron 3300', 'AJEJE')));
$table->addContent($lonelyCpu);

foreach($testpcs as $testpc) {
	$bluezone->addContent($testpc);
}
$table->addContent($SCHIFOMACCHINA);
$chernobyl->addContent($pc20)->addContent($pc22)->addContent($pc55)->addContent($pc90)->addContent($bluezone);

$db->itemDAO()->addItem($polito);
$db->itemDAO()->loseItem($ram666);

$multijson = [
	'OilData on the table' => json_decode('[{"type":"I","features":{"brand":"OilData","model":"Boulder 69000","variant":"default","type":"case","working":"yes"},"contents":[{"features":{"brand":"USAStek computer inc.","model":"P5KPL-VM","variant":"default","mac":"00:1a:22:52:a4:be","sn":"MT707BK05303585","type":"motherboard","working":"yes"},"contents":[{"code":"C251","features":{"brand":"Intel","model":"Core 2 Duo E8200","variant":"default","type":"cpu","working":"yes"}},{"features":{"brand":"Samsung","model":"M3 78T2863DZS-CF7","sn":"589442786","variant":"default","type":"ram","working":"yes"}},{"code":"R597","features":{"brand":"Samsung","model":"M3 78T2953EZ3-CF7","sn":"1231847313","variant":"default","type":"ram","working":"yes"}}]}]},{"type":"P","brand":"Samsung","model":"M3 78T2953EZ3-CF7","variant":"default","features":{"capacity-byte":1073741824,"color":"green","frequency-hertz":800000000,"ram-ecc":"no","ram-form-factor":"dimm","ram-timings":"6-6-6-18 as DDR2-800","ram-type":"ddr2","type":"ram"}},{"type":"P","brand":"Intel","model":"Core 2 Duo E8200","variant":"default","features":{"core-n":2,"cpu-socket":"lga775","frequency-hertz":2660000000,"isa":"x86-64","thread-n":2,"type":"cpu"}},{"type":"P","brand":"USAStek computer inc.","model":"P5KPL-VM","variant":"default","features":{"color":"golden","cpu-socket":"lga775","ethernet-ports-1000m-n":1,"ide-ports-n":1,"integrated-graphics-brand":"Intel","integrated-graphics-model":"82G33/G31 Express","key-bios-setup":"Del","mini-jack-ports-n":3,"motherboard-form-factor":"microatx","parallel-ports-n":1,"pci-sockets-n":2,"pcie-sockets-n":2,"ps2-ports-n":2,"psu-connector-cpu":"4pin","psu-connector-motherboard":"atx-24pin","ram-form-factor":"dimm","ram-type":"ddr2","sata-ports-n":4,"serial-ports-n":1,"type":"motherboard","usb-ports-n":4,"vga-ports-n":1}}]', true),
	'That big and heavy case' => json_decode('[{"type":"I","features":{"brand":"LeaderTech","model":"LT-9001","variant":"default","type":"case","working":"yes"},"contents":[{"features":{"brand":"Foo Bar Industrial Motherboards","model":"K987AB","variant":"v1.1","mac":"00:f0:00:b4:12:12","sn":"FBN347325VH29030","type":"motherboard","working":"yes"},"contents":[{"code":"C251","features":{"brand":"Intel","model":"Core 2 Quad Q4000","variant":"default","type":"cpu","working":"yes"}},{"features":{"brand":"Samsung","model":"S667ABC1024","sn":"247823652378","variant":"v1","type":"ram","working":"yes"}}]}]},{"type":"P","brand":"Samsung","model":"S667ABC1024","variant":"v1","features":{"capacity-byte":1073741824,"color":"green","frequency-hertz":667000000,"ram-ecc":"no","ram-form-factor":"dimm","ram-timings":"8-8-8-24 as DDR2-667","ram-type":"ddr2","type":"ram"}},{"type":"P","brand":"Intel","model":"Core 2 Quad Q4000","variant":"default","features":{"core-n":4,"cpu-socket":"lga775","frequency-hertz":3200000000,"isa":"x86-64","thread-n":4,"type":"cpu"}},{"type":"P","brand":"Foo Bar Industrial Motherboards","model":"K987AB","variant":"v1.1","features":{"ethernet-ports-1000m-n":1,"ide-ports-n":1,"mini-jack-ports-n":3,"motherboard-form-factor":"atx","parallel-ports-n":1,"pci-sockets-n":4,"pcie-sockets-n":1,"ps2-ports-n":2,"ram-form-factor":"dimm","ram-type":"ddr2","sata-ports-n":4,"serial-ports-n":1,"type":"motherboard","usb-ports-n":5}}]', true),
	'Intentionally malformed data' => json_decode('[{"type":"I","features":{"model":"PessimPlex 360","type":"case","working":"yes"},"contents":[{"features":{"brand":"Foo Bar Industrial Motherboards","variant":"v1.1","mac":"00:f0:00:b4:99:99","type":"motherboard","working":"yes"},"contents":[{"features":{"working":"yes","note":"Surprise item with no data, but we know it works, because magic!"}}]}]},{"type":"P","brand":"Foo","variant":"default","features":{"color":"green","type":"ram"}},{"type":"P","brand":"Foo","features":{"color":"green","type":"ram"}},{"type":"P","features":{"color":"green","type":"ram"}},{"type":"P","features":{"color":"green"}}]', true),
];
foreach($multijson as $name => $json) {
	foreach($json as $item){
		$type = $item['type'];
		$json = json_encode($item);
		$db->bulkDAO()->addBulk($name, $type, $json);
	}
}

echo 'Example items created, last update ' . date('d-m-Y H:i:s', stat(__FILE__)[9]) . ' UTC' . PHP_EOL;
echo 'Default API token: ' . $token . PHP_EOL;
