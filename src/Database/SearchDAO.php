<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\Product;
use WEEEOpen\Tarallo\ProductCode;
use WEEEOpen\Tarallo\Search;
use WEEEOpen\Tarallo\SearchDiff;
use WEEEOpen\Tarallo\SearchTriplet;
use WEEEOpen\Tarallo\User;

final class SearchDAO extends DAO
{
	private function getCompareArray(SearchTriplet $triplet): array
	{
		$feature = $triplet->getAsFeature();
		$operator = $triplet->getCompare();
		$value = $this->getPDO()->quote($triplet->getValue());
		if ($operator === '*') {
			return ['1', '=', '1'];
		}
		switch ($feature->type) {
			case BaseFeature::STRING:
				switch ($operator) {
					case '=':
					case '<>':
						return ['ValueText', $operator, $value];
					case '~':
						return ['ValueText', 'LIKE', $value];
					case '!~':
						return ['ValueText', 'NOT LIKE', $value];
				}
				break;
			case BaseFeature::INTEGER:
			case BaseFeature::DOUBLE:
				$column = FeatureDAO::getColumn($feature->type);
				switch ($operator) {
					case '>':
					case '<':
					case '>=':
					case '<=':
					case '<>':
					case '=':
						return [$column, $operator, $value];
				}
				break;
			case BaseFeature::ENUM:
				switch ($operator) {
					case '=':
					case '<>':
						return ['ValueEnum', $operator, $value];
				}
				break;
		}
		throw new \InvalidArgumentException("Cannot apply filter $triplet");
	}

	private function getCompare(SearchTriplet $triplet): string
	{
		return implode(' ', $this->getCompareArray($triplet));
	}

	/**
	 * @param int $previousSearchId
	 *
	 * @return int
	 */
	public function getResultsCount(int $previousSearchId): int
	{
		$s = $this->getPDO()->prepare('SELECT ResultsCount FROM Search WHERE Code = ?;');
		$result = $s->execute([$previousSearchId]);
		assert($result !== false, 'get results count');

		try {
			if ($s->rowCount() === 0) {
				throw new \LogicException("Search id $previousSearchId doesn't exist");
			}
			$row = $s->fetch(\PDO::FETCH_NUM);

			return (int) $row[0];
		} finally {
			$s->closeCursor();
		}
	}

	protected function getSearchFilter(Search $search, \PDO $pdo): string
	{
		$subqueries = [];
		$subqueriesNotIn = [];

		$features = $search->getFiltersByType("feature");
		if (!empty($features)) {
			[$subqueries[], $subqueriesNotIn[]] = $this->getFeatureSubqueries($features, $pdo);
		}

		$ancestors = $search->getFiltersByType("c_feature");
		if (!empty($ancestors)) {
			[$subqueries[], $subqueriesNotIn[]] = $this->getAncestorSubqueries($ancestors, $pdo);
		}

		$locations = $search->getFiltersByType("location");
		if (!empty($locations)) {
			[$subqueries[], $subqueriesNotIn[]] = $this->getLocationSubqueries($locations, $pdo);
		}

		$subqueries = array_filter($subqueries);
		$subqueriesNotIn = array_filter($subqueriesNotIn);

		$codeQuery = "";
		$code = $search->getFiltersByType("code")[0] ?? null;
		if ($code !== null) {
			$escaped = $pdo->quote($code);
			$codeQuery = " AND Item.`Code` LIKE $escaped";
		}

		$filter = "1=1";

		foreach ($subqueries as $q) {
			$filter .= " AND Item.`Code` IN ($q)";
		}

		foreach ($subqueriesNotIn as $q) {
			$filter .= " AND Item.`Code` NOT IN ($q)";
		}

		$filter .= $codeQuery;
		return $filter;
	}

	/**
	 * @param Search $search Filters to be applied
	 * @param string $user Search owner
	 *
	 * @return int|null
	 */
	public function searchNew(Search $search, string $user): int
	{
		$pdo = $this->getPDO();

		if ($search->isSortOnly()) {
			throw new \InvalidArgumentException("Sorting only is not allowed for a new search");
		}

		$stmt = $pdo->prepare('INSERT INTO `Search` (`Query`, `Owner`) VALUES (?, ?)');

		$r = $stmt->execute([json_encode($search), $user]);
		assert($r !== false, 'start search');
		$id = $pdo->lastInsertId();

		$filter = $this->getSearchFilter($search, $pdo);
		$query = "
INSERT INTO SearchResult(`Search`, `Item`)
SELECT DISTINCT ?, `Code`
FROM Item
WHERE DeletedAt IS NULL AND $filter";

		//throw new \Exception($query);

		$stmt = $pdo->prepare($query);
		$res = $stmt->execute([$id]);
		assert($res !== null, "execute search");

		$this->sort($search, $id);

		return $id;
	}

	/**
	 * @param Search $search Search being updated
	 * @param SearchDiff $diff Diff to apply
	 *
	 * @return int|null
	 */
	public function searchUpdate(Search $search, SearchDiff $diff): ?int
	{
		$pdo = $this->getPDO();

		if (!$search->getId()) {
			throw new \InvalidArgumentException("Search must have a code set");
		}

		$new_search = $search->applyDiff($diff);

		if (!$diff->isNewOnly() && !$diff->isSortOnly()) {
			// Need to re-do search
			// TODO: Maybe delete the old one?
			return $this->searchNew($new_search, $new_search->getOwner());
		}

		if ($diff->isSortOnly()) {
			$this->sort($new_search, $search->getId());
		} else {
			$filter = (new Search())->applyDiff($diff);
			$filter = $this->getSearchFilter($filter, $pdo);

			$query = "
			DELETE FROM SearchResult
			WHERE `Search` = ?
			AND `Item` NOT IN (SELECT `Code` FROM Item WHERE $filter)";

			$stmt = $pdo->prepare($query);
			$res = $stmt->execute([$search->getId()]);
			assert($res !== null, "filter old results");

			// No need to re-sort
		}

		$stmt = $pdo->prepare("UPDATE Search SET `Query` = ? WHERE `Code` = ?");
		$res = $stmt->execute([json_encode($new_search), $new_search->getId()]);
		assert($res !== null, "update search query");

		return null;
	}

	public function getSearchById(string $id): ?Search
	{
		$pdo = $this->getPDO();

		$stmt = $pdo->prepare(
			"
		SELECT `Query`, `Owner`
		FROM Search
		WHERE `Code` = ?"
		);

		try {
			$result = $stmt->execute([$id]);
			assert($result, "getSearchById");

			$search = $stmt->fetch(\PDO::FETCH_ASSOC);
		} finally {
			$stmt->closeCursor();
		}

		if (!$search) {
			return null;
		}

		$result = Search::fromJson(json_decode($search["Query"], true));
		$result->setOwner($search["Owner"]);
		$result->setId($id);

		return $result;
	}

	public function sortByCode(int $searchId)
	{
		$query = /** @lang MySQL */
			"SELECT `Item` FROM SearchResult WHERE Search = ? ORDER BY CHAR_LENGTH(`Item`) DESC, `Item` DESC;";
		$sortedStatement = $this->getPDO()->prepare($query);

		try {
			$result = $sortedStatement->execute([$searchId]);
			assert($result !== false, 'sorting with codes');

			$sorted = $sortedStatement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$sortedStatement->closeCursor();
		}

		if (!isset($sorted)) {
			throw new \LogicException('Lost sorted items (by code) along the way somehow');
		}
		if (count($sorted) === 0) {
			return;
		}

		$i = 0;
		foreach ($sorted as $result) {
			$this->setItemOrder($searchId, $result['Item'], $i);
			$i++;
		}
	}

	public function sort(Search $search, int $searchId)
	{
		$sorts = $search->getFiltersByType("sort");
		if (empty($sorts)) {
			$this->sortByCode($searchId);

			return;
		}

		//TODO: Handle multisort
		$firstSort = $sorts[0];

		//throw new \Exception(print_r($search->sorts, true));
		$featureName = $firstSort["feature"];
		$direction = $firstSort["direction"] === '+' ? 'ASC' : 'DESC';
		$column = FeatureDAO::getColumn(BaseFeature::getType($featureName));

		self::unsort($searchId);

		$miniquery = /** @lang MySQL */ <<<EOQ
SELECT DISTINCT `Item` AS `Code`
FROM SearchResult
LEFT JOIN (
	SELECT `Code`, $column
	FROM ProductItemFeatureUnified
	WHERE Feature = ?
) AS features ON `Item` = features.`Code`
WHERE Search = ?
ORDER BY ISNULL($column), $column $direction, CHAR_LENGTH(`Code`) DESC, `Code` DESC;
EOQ;

		$sortedStatement = $this->getPDO()->prepare($miniquery);
		try {
			$result = $sortedStatement->execute([$featureName, $searchId]);
			assert($result !== false, 'sorting results');

			$sorted = $sortedStatement->fetchAll(\PDO::FETCH_ASSOC);
		} finally {
			$sortedStatement->closeCursor();
		}

		if (!isset($sorted)) {
			throw new \LogicException('Lost sorted items along the way somehow');
		}
		if (count($sorted) === 0) {
			return;
		}

		$i = 0;
		foreach ($sorted as $result) {
			$this->setItemOrder($searchId, $result['Code'], $i);
			$i++;
		}
	}

	private $setItemOrderStatement = null;

	/**
	 * Set item position in the results table.
	 *
	 * @param int $searchId Search ID
	 * @param string $code Item code
	 * @param int $position Position in the results
	 */
	private function setItemOrder(int $searchId, string $code, int $position)
	{
		if ($this->setItemOrderStatement === null) {
			$this->setItemOrderStatement = $this->getPDO()
				->prepare('UPDATE SearchResult SET `Order` = :pos WHERE Search = :sea AND Item = :cod');
		}

		try {
			$this->setItemOrderStatement->bindValue(':sea', $searchId, \PDO::PARAM_INT);
			$this->setItemOrderStatement->bindValue(':pos', $position, \PDO::PARAM_INT);
			$this->setItemOrderStatement->bindValue(':cod', $code, \PDO::PARAM_STR);
			$result = $this->setItemOrderStatement->execute();
			assert($result !== false, 'move item in search result to position');
		} finally {
			$this->setItemOrderStatement->closeCursor();
		}
	}

	/**
	 * Remove sorting information from search results
	 *
	 * @param int $searchId
	 */
	private function unsort(int $searchId)
	{
		$statement = $this->getPDO()->prepare('UPDATE SearchResult SET `Order` = NULL WHERE Search = ?');

		try {
			$result = $statement->execute([$searchId]);
			assert($result !== false, 'unsort search');
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get results from a previous search, already sorted.
	 *
	 * @param int $id Search ID
	 * @param int $page Current page, starting from 1
	 * @param int $perPage Items per page
	 * @param int|null $depth Depth of each Item tree
	 *
	 * @return Item[]
	 */
	public function getResults(int $id, int $page, int $perPage, ?int $depth = null): array
	{
		$this->refresh($id);

		$statement = /** @lang MySQL */
			'SELECT `Item` FROM SearchResult WHERE Search = :id ORDER BY `Order` LIMIT :offs, :cnt';

		$statement = $this->getPDO()->prepare($statement);
		$items = [];
		$itemDAO = $this->database->itemDAO();

		try {
			$statement->bindValue(':id', $id, \PDO::PARAM_INT);
			$statement->bindValue(':offs', ($page - 1) * $perPage, \PDO::PARAM_INT);
			$statement->bindValue(':cnt', $perPage, \PDO::PARAM_INT);
			$statement->execute();
			foreach ($statement as $result) {
				$items[] = $itemDAO->getItem(new ItemCode($result['Item']), null, $depth);
			}
		} finally {
			$statement->closeCursor();
		}

		return $items;
	}

	/**
	 * Update search expiration date.
	 * Already done via triggers for INSERT, UPDATE and DELETE operations.
	 *
	 * @param int $id
	 */
	private function refresh(int $id)
	{
		// ID is an int, no riks of SQL injection...
		$result = $this->getPDO()->exec("CALL RefreshSearch($id)");
		// may be 0 for number of affected rows, or false on failure
		assert($result !== false, 'update expiration date for search');
	}

	/**
	 * @param SearchTriplet[]|null $triplets
	 * @param \PDO $pdo
	 *
	 * @return string[]
	 */
	protected function getFeatureSubqueries(array $triplets, \PDO $pdo): array
	{
		$template = "
		SELECT `Code`
		FROM ProductItemFeatureUnified
		WHERE ";

		$in = [];
		$notIn = [];
		foreach ($triplets as $t) {
			$escaped = $pdo->quote($t->getAsFeature()->name);
			if ($t->getCompare() === '!') {
				$notIn[] = "(`Feature` = $escaped)";
			} else {
				$compareString = $this->getCompare($t);
				$in[] = "(`Feature` = $escaped AND $compareString)";
			}
		}

		$inQ = $in ? $template . implode(" OR ", $in) . " GROUP BY `Code` HAVING COUNT(*)=" . count($in) : null;

		return [$inQ, $notIn ? $template . implode(" OR ", $notIn) : null];
	}

	/**
	 * @param ItemCode[]|null $triplets
	 * @param \PDO $pdo
	 *
	 * @return string[]
	 */
	protected function getLocationSubqueries(array $locations, \PDO $pdo): array
	{
		$template = "
		SELECT `Descendant`
		FROM Tree
		WHERE ";

		$in = [];
		foreach ($locations as $l) {
			$escaped = $pdo->quote($l);
			$in[] = "(`Ancestor` = $escaped)";
		}

		return [$in ? $template . implode(" OR ", $in) : null, null];
	}

	/**
	 * @param SearchTriplet[]|null $triplets
	 * @param \PDO $pdo
	 *
	 * @return string[]
	 */
	protected function getAncestorSubqueries(array $triplets, \PDO $pdo): array
	{
		$template = "
		SELECT `Descendant`
		FROM ProductItemFeatureUnified, Tree
		WHERE ProductItemFeatureUnified.`Code` = Tree.`Ancestor`
		AND Tree.Depth > 0
		AND ";

		$in = [];
		$notIn = [];
		foreach ($triplets as $t) {
			$escaped = $pdo->quote($t->getAsFeature()->name);
			if ($t->getCompare() === '!') {
				$notIn[] = "(`Feature` = $escaped)";
			} else {
				$compareString = $this->getCompare($t);
				$in[] = "(`Feature` = $escaped AND $compareString)";
			}
		}

		$inQ = $in ? $template . implode(" OR ", $in) . " GROUP BY `Descendant` HAVING COUNT(*)=" . count($in) : null;

		return [$inQ, $notIn ? $template . implode(" OR ", $notIn) : null];
	}

	public function getBrandsLike(string $brand, int $limit = 10): array
	{
		$brand = str_replace(' ', '', $brand);
		$statement = $this->getPDO()
			->prepare("SELECT DISTINCT Brand, LENGTH(REPLACE(Brand, ' ', '')) - ? AS Distance FROM Product WHERE REPLACE(Brand, ' ', '') LIKE ? ORDER BY Distance LIMIT ?");
		try {
			$statement->bindValue(1, strlen($brand), \PDO::PARAM_INT);
			$statement->bindValue(2, "%$brand%", \PDO::PARAM_STR);
			$statement->bindValue(3, $limit, \PDO::PARAM_INT);
			$statement->execute();
			return $statement->fetchAll(\PDO::FETCH_NUM);
		} finally {
			$statement->closeCursor();
		}
	}

	public function getProductsLike(string $search, int $limit = 10): array
	{
		$search = str_replace(' ', '', $search);
		$statement = $this->getPDO()
			// - LENGTH(REPLACE(LOWER(Brand),:brandfilter,'')) + LENGTH(Brand)
			->prepare("SELECT DISTINCT Brand, Model, Variant, LENGTH(CONCAT(REPLACE(Brand, ' ', ''),REPLACE(Model, ' ', ''),REPLACE(IF(Variant = :default,'',Variant), ' ', ''))) - :strlen + IF(Brand LIKE :search2, LENGTH(Brand), 0) AS Distance FROM Product WHERE CONCAT(REPLACE(Brand, ' ', ''),REPLACE(Model, ' ', ''),REPLACE(IF(Variant = :default2,'',Variant), ' ', '')) LIKE :search ORDER BY Distance LIMIT :limit");
		try {
			$statement->bindValue(':default', ProductCode::DEFAULT_VARIANT, \PDO::PARAM_STR);
			$statement->bindValue(':default2', ProductCode::DEFAULT_VARIANT, \PDO::PARAM_STR);
			$statement->bindValue(':strlen', strlen($search), \PDO::PARAM_INT);
			$statement->bindValue(':search', "%$search%", \PDO::PARAM_STR);
			$statement->bindValue(':search2', "%$search%", \PDO::PARAM_STR);
//			$statement->bindValue(':brandfilter', $search, \PDO::PARAM_STR);
			$statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
			$statement->execute();

			$result = [];
			$statement->setFetchMode(\PDO::FETCH_NUM);
			foreach ($statement as $row) {
				$result[] = [new ProductCode($row[0], $row[1], $row[2]), $row[3]];
			}
			return $result;
		} finally {
			$statement->closeCursor();
		}
	}

	public function getFeaturesLike(string $search, bool $product = false, int $limit = 10): array
	{
		if ($product) {
			$statement = $this->getPDO()
				->prepare("SELECT Brand, Model, Variant, Feature, ValueText, LENGTH(ValueText) - :strlen AS Distance FROM ProductFeature WHERE ValueText LIKE :search AND Feature NOT IN ('brand', 'model', 'variant') ORDER BY Distance, Brand, Model, Variant DESC LIMIT :limit");
		} else {
			$statement = $this->getPDO()
				->prepare("SELECT Code, Feature, ValueText, LENGTH(ValueText) - :strlen AS Distance FROM ItemFeature WHERE ValueText LIKE :search AND Feature NOT IN ('brand', 'model', 'variant') ORDER BY Distance, Code DESC LIMIT :limit");
		}
		try {
			$statement->bindValue(':strlen', strlen($search), \PDO::PARAM_INT);
			$statement->bindValue(':search', "%$search%", \PDO::PARAM_STR);
			$statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
			$statement->execute();

			$result = [];
			$statement->setFetchMode(\PDO::FETCH_NUM);
			if ($product) {
				foreach ($statement as $row) {
					$result[] = [new ProductCode($row[0], $row[1], $row[2]), new Feature($row[3], $row[4]), $row[5]];
				}
			} else {
				foreach ($statement as $row) {
					$result[] = [new ItemCode($row[0]), new Feature($row[1], $row[2]), $row[3]];
				}
			}
			return $result;
		} finally {
			$statement->closeCursor();
		}
	}
}
