<?php

namespace WEEEOpen\Tarallo\Database;

use PDOStatement;
use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\ForbiddenNormalizationException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemTraitFeatures;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\NotFoundException;
use WEEEOpen\Tarallo\Product;
use WEEEOpen\Tarallo\ProductCode;
use WEEEOpen\Tarallo\Normalization;

final class FeatureDAO extends DAO
{
	private const NORMALIZATION_CATEGORY_BRAND = 'brand';
	private const NORMALIZATION_CATEGORY_OWNER = 'owner';
	private const NORMALIZATION_CATEGORY_OS_VERSION = 'os-version';
	private const NORMALIZATION_CATEGORY_KEY = 'key';
	private const NORMALIZATION_CACHE_PREFIX = 'normalization_';
	private const NORMALIZATION_CACHE_TTL = 60 * 60 * 24;

	public static function getNormalizationMapping(): array
	{
		return [
			'brand' => self::NORMALIZATION_CATEGORY_BRAND,
			'brand-manufacturer' => self::NORMALIZATION_CATEGORY_BRAND,
			'integrated-graphics-brand' => self::NORMALIZATION_CATEGORY_BRAND,
			'os-license-version' => self::NORMALIZATION_CATEGORY_OS_VERSION,
			'owner' => self::NORMALIZATION_CATEGORY_OWNER,
			'key-bios-setup' => self::NORMALIZATION_CATEGORY_KEY,
			'key-boot-menu' => self::NORMALIZATION_CATEGORY_KEY,
		];
	}

	/**
	 * Obtain \PDO::PARAM_... constant from feature name
	 *
	 * @param int $type Feature type
	 *
	 * @return int Column name (e.g. ValueText)
	 * @see getType
	 *
	 */
	public static function getPDOType(int $type): int
	{
		switch ($type) {
			case BaseFeature::ENUM:
			case BaseFeature::DOUBLE:
			case BaseFeature::STRING:
				return \PDO::PARAM_STR;
			case BaseFeature::INTEGER:
				return \PDO::PARAM_INT;
			default:
				throw new \LogicException('Unrecognized feature type in getPDOType');
		}
	}

	/**
	 * Obtain database column name (for the ItemFeature table)
	 *
	 * @param int $type Feature type
	 *
	 * @return string Column name (e.g. ValueText)
	 * @see getType
	 *
	 */
	public static function getColumn(int $type): string
	{
		switch ($type) {
			case BaseFeature::STRING:
				return 'ValueText';
			case BaseFeature::INTEGER:
				return 'Value';
			case BaseFeature::ENUM:
				return 'ValueEnum';
			case BaseFeature::DOUBLE:
				return 'ValueDouble';
			default:
				throw new \LogicException('Unrecognized feature type in getColumn');
		}
	}

	/**
	 * Get features from ALL TEH ITEMS/PRODUCTS
	 *
	 * @param ItemWithFeatures[] $items
	 *
	 * @return ItemWithFeatures[]|Item[]|Product[] same array
	 */
	public function getFeaturesAll(array $items)
	{
		foreach ($items as $item) {
			$this->addFeaturesTo($item);
		}

		return $items;
	}

	/**
	 * Add own features to an item/product
	 *
	 * @param ItemWithFeatures $item
	 *
	 * @return ItemWithFeatures|Item|Product same item/product
	 */
	public function addFeaturesTo(ItemWithFeatures $item): ItemWithFeatures
	{
		if ($item instanceof ItemWithCode) {
			// No need to search ProductItemFeature
			$statement = $this->getPDO()->prepare(
				'SELECT Feature, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`
            FROM ItemFeature
            WHERE `Code` = :cod;'
			);

			$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);
		} else {
			/** @var ProductCode $item */
			$statement = $this->getPDO()->prepare(
				'SELECT Feature, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`
            FROM ProductFeature
            WHERE Brand = :b AND Model = :m AND Variant = :v;'
			);

			$statement->bindValue(':b', $item->getBrand(), \PDO::PARAM_STR);
			$statement->bindValue(':m', $item->getModel(), \PDO::PARAM_STR);
			$statement->bindValue(':v', $item->getVariant(), \PDO::PARAM_STR);
		}

		try {
			$statement->execute();
			if ($statement->rowCount() > 0) {
				foreach ($statement as $row) {
					/** @var Item[] $items */
					$item->addFeature(Feature::ofString($row['Feature'], $row['Value']));
				}
			}
		} finally {
			$statement->closeCursor();
		}

		return $item;
	}

	/**
	 * Add features to a product
	 *
	 * @param Product $product
	 *
	 * @return Product same product
	 */
	public function getProductFeatures(Product $product): Product
	{
		// No need to search in ProductItemFeature
		// TODO: memoize results, cache them, do something (getting the same item may query the same product multiple times from the database)
		$statement = $this->getPDO()->prepare(
			'SELECT Feature, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`
            FROM ProductFeature
            WHERE Brand = :b AND Model = :m AND Variant = :v;'
		);

		$statement->bindValue(':b', $product->getBrand(), \PDO::PARAM_STR);
		$statement->bindValue(':m', $product->getModel(), \PDO::PARAM_STR);
		$statement->bindValue(':v', $product->getVariant(), \PDO::PARAM_STR);

		try {
			$statement->execute();
			if ($statement->rowCount() > 0) {
				foreach ($statement as $row) {
					/** @var Item[] $items */
					$product->addFeature(Feature::ofString($row['Feature'], $row['Value']));
				}
			}
		} finally {
			$statement->closeCursor();
		}

		return $product;
	}

	/**
	 * Add a U audit entry for the specified item/product.
	 *
	 * @param ItemWithCode|ProductCode $item Item or product
	 */
	public function addAuditEntry($item)
	{
		if ($item instanceof ItemWithCode) {
			$statement = $this->getPDO()->prepare('INSERT INTO Audit (`Code`, `Change`, `User`) VALUES (?, \'U\', @taralloAuditUsername)');
			$statement->bindValue(1, $item->getCode());
		} else {
			/** @var ProductCode $item */
			$statement = $this->getPDO()->prepare('INSERT INTO AuditProduct (`Brand`, `Model`, `Variant`, `Change`, `User`) VALUES (?, ?, ?, \'U\', @taralloAuditUsername)');
			$statement->bindValue(1, $item->getBrand());
			$statement->bindValue(2, $item->getModel());
			$statement->bindValue(3, $item->getVariant());
		}

		try {
			$success = $statement->execute();
			assert($success, 'add audit table entry for features update');
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Set item features.
	 * Automatically ignores features identical to a product feature, for items.
	 * Automatically ignores updates of features that set the same value.
	 *
	 * @param ItemWithFeatures $item
	 *
	 * @return bool True if anything actually changed (and an U audit entry was generated), false otherwise.
	 */
	public function setFeatures(ItemWithFeatures $item): bool
	{
		// Own features, or else it duplicates product features
		$features = $item->getOwnFeatures();

		if (empty($features)) {
			return false;
		}

		$this->tryNormalizeAll($features);

		$changes = false;
		foreach ($features as $feature) {
			$changed = $this->setFeature($item, $feature);
			$changes = $changes || $changed;
		}

		if ($changes) {
			assert($item instanceof ItemWithCode || $item instanceof ProductCode);
			$this->addAuditEntry($item);
		}

		return $changes;
	}

	/**
	 * Delete features from an item. This generates no audit entries, BTW.
	 *
	 * @param ItemWithFeatures $item
	 * @param string[] $features
	 *
	 * @return bool True if anything was deleted
	 */
	public function deleteFeatures(ItemWithFeatures $item, array $features): bool
	{
		if (empty($features)) {
			return false;
		}
		if ($item instanceof ItemWithCode) {
			// This never fails, even for items that don't exist
			$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ItemFeature WHERE `Code` = :cod AND `Feature`= :f');
			$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);
		} else {
			/** @var ProductCode $item */
			$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ProductFeature WHERE Brand = :brand AND Model = :model AND Variant = :variant AND `Feature`= :f');
			$statement->bindValue(':brand', $item->getBrand(), \PDO::PARAM_STR);
			$statement->bindValue(':model', $item->getModel(), \PDO::PARAM_STR);
			$statement->bindValue(':variant', $item->getVariant(), \PDO::PARAM_STR);
		}

		try {
			$rows = 0;
			foreach ($features as $feature) {
				if (!is_string($feature)) {
					throw new \InvalidArgumentException('Name of feature to be deleted should be a string');
				}

				$statement->bindValue(':f', $feature, \PDO::PARAM_STR);

				$result = $statement->execute();
				assert($result !== false, 'delete feature');
				$rows += $statement->rowCount();
			}
		} finally {
			$statement->closeCursor();
		}
		return $rows !== 0;
	}


	/**
	 * Delete all features from an item
	 *
	 * @param ItemWithCode|ProductCode $item
	 *
	 * @return bool True if anything was deleted, false otherwise
	 */
	public function deleteFeaturesAll($item): bool
	{
		if ($item instanceof ItemWithCode) {
			$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ItemFeature WHERE `Code` = ?');
			$statement->bindValue(1, $item->getCode());
		} else {
			/** @var ProductCode $item */
			$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ProductFeature WHERE `Brand` = ? AND `Model` = ? AND `Variant` = ?');
			$statement->bindValue(1, $item->getBrand());
			$statement->bindValue(2, $item->getModel());
			$statement->bindValue(3, $item->getVariant());
		}

		try {
			$result = $statement->execute();
			assert($result !== false, 'delete all features');
			$rows = $statement->rowCount();
			return $rows !== 0;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Set value for a single feature and update audit table
	 *
	 * @param ItemWithFeatures|Product|ItemTraitFeatures $item
	 * @param Feature $feature
	 *
	 * @return bool Something actually changed or not
	 */
	private function setFeature($item, Feature $feature): bool
	{
		$column = self::getColumn($feature->type);
		$type = self::getPDOType($feature->type);

		if ($item instanceof Product) {
			$statement = $this->setFeaturesQueryForProduct($item, $column);
		} else {
			if (method_exists($item, 'getProduct')) {
				$product = $item->getProduct();
				/** @var Product $product */
				if ($product !== null && $product->getFeatureValue($feature->name) == $feature->value) {
					// Item feature = product feature
					// If feature is being added, this will delete nothing and return false (no changes)
					// If feature is being updated, this will delete old feature and return true (it changed)
					return $this->deleteFeatures($item, [$feature->name]);
				}
			}
			$statement = $this->setFeaturesQueryForItem($item, $column);
		}
		$statement->bindValue(':feature', $feature->name, \PDO::PARAM_STR);
		$statement->bindValue(':val', $feature->value, $type);
		$statement->bindValue(':val2', $feature->value, $type);
		try {
			$result = $statement->execute();
			assert($result !== false, 'set feature');
			return $statement->rowCount() > 0;
		} catch (\PDOException $e) {
			// This error has ever been witnessed when master-master replication breaks, but apparently it's used
			// to signify that there's no foreign key target thing for the primary key other thing.
			// That is: inserting/updating a row for an item that doesn't exist.
			if ($e->getCode() === 'HY000' && $statement->errorInfo()[1] === 1032 && $statement->errorInfo()[2] === 'Can\'t find record in \'ItemFeature\'') {
				throw new NotFoundException();
			} else {
				if ($e->getCode() === '23000' && $statement->errorInfo()[0] === '23000' && $statement->errorInfo()[1] === 1452) {
					throw new NotFoundException();
				}
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * @param ItemWithFeatures $item
	 * @param string $column
	 *
	 * @return bool|PDOStatement
	 */
	private function setFeaturesQueryForItem(ItemWithFeatures $item, string $column)
	{
		assert($item instanceof ItemWithCode);
		$statement = $this->getPDO()->prepare("INSERT INTO ItemFeature (Feature, `Code`, `$column`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `$column`=:val2");
		$statement->bindValue(':item', $item->getCode(), \PDO::PARAM_STR);
		return $statement;
	}

	/**
	 * @param Product $product
	 * @param string $column
	 *
	 * @return bool|PDOStatement
	 */
	private function setFeaturesQueryForProduct(Product $product, string $column)
	{
		$statement = $this->getPDO()->prepare("INSERT INTO ProductFeature (Feature, Brand, Model, Variant, `$column`) VALUES (:feature, :b, :m, :v, :val) ON DUPLICATE KEY UPDATE `$column`=:val2");
		$statement->bindValue(':b', $product->getBrand(), \PDO::PARAM_STR);
		$statement->bindValue(':m', $product->getModel(), \PDO::PARAM_STR);
		$statement->bindValue(':v', $product->getVariant(), \PDO::PARAM_STR);
		return $statement;
	}

	/**
	 * Get all normalization values, for the settings page, not to be used to actually normalized.
	 *
	 * @return array
	 */
	public function getAllNormalizationValues()
	{
		$statement = $this->getPDO()->prepare('SELECT MinimizedKey, NormalizedValue, Category FROM Normalization ORDER BY Category, NormalizedValue');

		try {
			$success = $statement->execute();
			assert($success, 'get all normalized values');

			return $statement->fetchAll(\PDO::FETCH_NUM);
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get all normalization values, for the settings page, not to be used to actually normalized.
	 *
	 * @return array
	 */
	public function getAllNormalizationCategories()
	{
		$statement = $this->getPDO()->prepare('SELECT DISTINCT Category FROM Normalization');

		try {
			$success = $statement->execute();
			assert($success, 'get normalized categories');

			$result = [];
			while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
				$result[] = $row[0];
			}
			return $result;
		} finally {
			$statement->closeCursor();
		}
	}

	public function isNormalizationForbidden(string $minimized, string $category)
	{
		$statement = $this->getPDO()->prepare('SELECT COUNT(*) FROM NormalizationForbidden WHERE MinimizedKey = ? AND Category = ?');

		try {
			$success = $statement->execute([$minimized, $category]);
			assert($success, 'check forbidden normalization');

			$result = [];
			$count = (int) $statement->fetchAll(\PDO::FETCH_NUM)[0][0];

			return $count > 0;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * @throws ForbiddenNormalizationException
	 */
	public function addNormalizedValue(string $wrong, string $value, string $category)
	{
		$minimized = Normalization::minimizeText($wrong);
		$isForbidden = $this->isNormalizationForbidden($minimized, $category);
		if ($isForbidden) {
			throw new ForbiddenNormalizationException();
		}

		$statement = $this->getPDO()->prepare('INSERT INTO Normalization(MinimizedKey, NormalizedValue, Category) VALUES (?, ?, ?)');

		try {
			$success = $statement->execute([$minimized, $value, $category]);
			assert($success, 'add normalized value');

			$this->deleteCache($category);
		} finally {
			$statement->closeCursor();
		}
	}

	public function deleteNormalizedValue(string $minimized)
	{
		$statement = $this->getPDO()->prepare('DELETE FROM Normalization WHERE MinimizedKey = ?');

		try {
			$success = $statement->execute([$minimized]);
			assert($success, 'add normalized value');

			// TODO: delete only one category
			foreach ($this->getAllNormalizationCategories() as $category) {
				$this->deleteCache($category);
			}
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get normalization values from database
	 *
	 * @param string $category Normalization category
	 *
	 * @return array
	 */
	private function getNormalizationValues(string $category)
	{
		$statement = $this->getPDO()->prepare('SELECT MinimizedKey, NormalizedValue FROM Normalization WHERE Category = ?');
		$statement->bindValue(1, $category);

		$result = [];
		try {
			$success = $statement->execute();
			assert($success, 'get normalized values');

			while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
				$result[$row[0]] = $row[1];
			}
		} finally {
			$statement->closeCursor();
		}

		return $result;
	}

	private function apcuGenerator($key)
	{
		return $this->getNormalizationValues(substr($key, strlen(self::NORMALIZATION_CACHE_PREFIX)));
	}

	private function normalizeText(string $text, string $category): ?string
	{
		if (Database::hasApcu()) {
			$success = null;
			$values = apcu_fetch(self::NORMALIZATION_CACHE_PREFIX . $category, $success);
			if (!$success) {
				$values = apcu_entry(self::NORMALIZATION_CACHE_PREFIX . $category, [$this, 'apcuGenerator'], self::NORMALIZATION_CACHE_TTL);
			}
		} else {
			$values = $this->getNormalizationValues($category);
		}

		$textMinimized = Normalization::minimizeText($text);
		if (isset($values[$textMinimized])) {
			return $values[$textMinimized];
		} else {
			return null;
		}
	}

	private function deleteCache(string $category)
	{
		if (Database::hasApcu()) {
			apcu_delete(self::NORMALIZATION_CACHE_PREFIX . $category);
		}
	}

	/**
	 * Normalize items/products from a bulk import
	 *
	 * @param array $stuff One item or product
	 */
	public function tryNormalizeBulkImport(array &$stuff)
	{
		if (isset($stuff['features'])) {
			$normalizeWith = self::getNormalizationMapping();
			foreach ($normalizeWith as $feature => $group) {
				if (isset($stuff['features'][$feature])) {
					$normalized = $this->normalizeText($stuff['features'][$feature], $group);
					if ($normalized !== null) {
						$stuff['features'][$feature] = $normalized;
					}
				}
			}
		}
		if (isset($stuff['contents'])) {
			foreach ($stuff['contents'] as &$otherStuff) {
				$this->tryNormalizeBulkImport($otherStuff);
			}
		}
	}

	/**
	 * Normalize a single string value
	 *
	 * @param string $category Feature name
	 * @param string $value The value
	 *
	 * @return string|null The normalized value if different
	 */
	public function tryNormalizeValue(string $name, string $value): ?string
	{
		$normalizeWith = self::getNormalizationMapping();
		if (isset($normalizeWith[$name])) {
			$normalized = $this->normalizeText($value, $normalizeWith[$name]);
			if ($normalized !== null) {
				if ($normalized !== $value) {
					return $normalized;
				}
			}
		}
		return null;
	}

	/**
	 * Normalize all features
	 *
	 * @param Feature[] $features List of features
	 */
	public function tryNormalizeAll(array &$features, ?bool &$changed = null)
	{
		$normalizeWith = self::getNormalizationMapping();
		foreach ($features as $key => $feature) {
			/** @var Feature $feature */
			if (isset($normalizeWith[$feature->name])) {
				$normalized = $this->tryNormalizeFeature($feature, $normalizeWith[$feature->name]);
				if ($normalized !== null) {
					$features[$key] = $normalized;
					if ($changed !== null) {
						$changed = true;
					}
				}
			}
		}
	}

	private function tryNormalizeFeature(Feature $feature, string $category): ?Feature
	{
		$normalized = $this->normalizeText($feature->value, $category);
		if ($normalized !== null) {
			if ($normalized !== $feature->value) {
				return new Feature($feature->name, $normalized);
			}
		}
		return null;
	}
}
