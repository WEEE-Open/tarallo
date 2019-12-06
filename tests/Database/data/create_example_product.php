<?php


namespace WEEEOpen\TaralloTest\Database\data;

use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\Product;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$pdo = new \PDO(TARALLO_DB_DSN, TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, [
	\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
	\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
	\PDO::ATTR_AUTOCOMMIT => false,
	\PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE Tree;");
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE ItemFeature;");
$pdo->exec(/** @lang MariaDB */ "TRUNCATE TABLE ProductFeature;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Audit; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Item; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Product; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE SearchResult; SET FOREIGN_KEY_CHECKS = 1;");
$pdo->exec(/** @lang MariaDB */ "SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Search; SET FOREIGN_KEY_CHECKS = 1;");

$db = new Database(TARALLO_DB_USERNAME, TARALLO_DB_PASSWORD, TARALLO_DB_DSN);
$db->productDAO()->addProduct(new Product("Ciao", "ne"));
$db->productDAO()->addProduct(new Product("Intel", "kakka"));
$db->productDAO()->addProduct(new Product("Samsong", "KAI39"));
$db->productDAO()->addProduct(new Product("Caste", "Payton", "Brutto"));
$db->productDAO()->addProduct(new Product("Centryno", "kakka", "Orosa"));
$db->productDAO()->addProduct(new Product("Intel", "630583"));
$db->productDAO()->addProduct(new Product("Samsong", "kakka", "Robe"));
$db->productDAO()->addProduct(new Product("Strange", "Thing", "Dismone"));
$db->productDAO()->addProduct(new Product("Intel", "Asd"));
$db->productDAO()->addProduct(new Product("Centryno", "kakka", "Grigio"));
$db->productDAO()->addProduct(new Product("Kek", "Asd"));
$db->productDAO()->addProduct((new Product("eMachines", "EZ1600", "boh"))
	->addFeature(new Feature('motherboard-form-factor', 'miniitx'))
	->addFeature(new Feature('color', 'white'))
	->addFeature(new Feature('type', 'case')));

$polito = (new Item('Polito'))->addFeature(new Feature('type', 'location'));
$chernobyl = (new Item('Chernobyl'))->addFeature(new Feature('type', 'location'))->addFeature(new Feature('color', 'grey'));
$polito->addContent($chernobyl);
$table = (new Item('Table'))->addFeature(new Feature('type', 'location'))->addFeature(new Feature('color', 'white'));
$chernobyl->addContent($table);

$pc20 = (new Item('PC20'))
	->addFeature(new Feature('brand', 'Dill'))
	->addFeature(new Feature('model', 'DI-360'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
	->addFeature(new Feature('color', 'black'))
	->addFeature(new Feature('working', 'yes'));
$pc90 = (new Item('PC90'))
	->addFeature(new Feature('brand', 'Dill'))
	->addFeature(new Feature('model', 'DI-360'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
	->addFeature(new Feature('color', 'grey'))
	->addFeature(new Feature('working', 'yes'));
$pc55 = (new Item('PC55'))
	->addFeature(new Feature('brand', 'TI'))
	->addFeature(new Feature('model', 'GreyPC-\'98'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('motherboard-form-factor', 'atx'));
$pc22 = (new Item('PC22'))
	->addFeature(new Feature('brand', 'Dill'))
	->addFeature(new Feature('model', 'DI-360'))
	->addFeature(new Feature('type', 'case'))
	->addFeature(new Feature('motherboard-form-factor', 'proprietary'))
	->addFeature(new Feature('color', 'black'))
	->addFeature(new Feature('working', 'yes'));
$SCHIFOMACCHINA = (new Item('SCHIFOMACCHINA'))
	->addFeature(new Feature('brand', 'eMac'))
	->addFeature(new Feature('model', 'EZ1600'))
	->addFeature(new Feature('variant', 'boh'));
$table->addContent($SCHIFOMACCHINA);
$chernobyl->addContent($pc20)->addContent($pc22)->addContent($pc55)->addContent($pc90);

$db->itemDAO()->addItem($polito);
