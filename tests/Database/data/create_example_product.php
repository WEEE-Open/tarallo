<?php


namespace WEEEOpen\TaralloTest\Database\data;

use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\Product;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
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
