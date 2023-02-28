<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\Normalization;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\NotFoundException;

final class TreeDAO extends DAO
{
	public static function moveWithValidation(
		Database $db,
		ItemWithCode $what,
		ItemWithCode $newParent,
		bool $fix,
		bool $validate
	): array {
		// We'll need the full item in any case, not just an ItemIncomplete
		$item = $db->itemDAO()->getItem($what, null, 0);
		$oldParent = $item->getParent();
		// Also the parent, in these cases
		if ($fix || $validate) {
			try {
				$newParent = $db->itemDAO()->getItem($newParent, null, 1);
			} catch (NotFoundException $e) {
				throw new NotFoundException($newParent->getCode());
			}
		}

		// We need product features for fixup and validation
		if ($fix || $validate) {
			$db->productDAO()->getProductsAll($item->getFlatContent());
		}

		if ($fix) {
			$newParent = Normalization::fixupLocation($item, $newParent);
		}

		if ($validate) {
			Normalization::validateLocation($item, $newParent);
		}

		if ($newParent === null) {
			throw new \LogicException(
				'Moving to "null" is not implemented, move an item into itself to make it a root'
			);
		}
		$moved = false;
		// if $newParent === null will ever be supported, add a check here
		// if(from nowhere to somewhere || from somewhere to somewhere else (including itself, which removes parent))
		if (
			($oldParent === null && $newParent->compareCode($item) !== 0)
			|| ($oldParent !== null && $newParent->compareCode($oldParent) !== 0)
		) {
			// Throws NotFoundException (when needed, obv)
			$db->treeDAO()->moveItem($item, $newParent);
			$moved = true;
		}
		return [$oldParent, $newParent, $moved];
	}

	/**
	 * Add a new Item to the tree. Don't call if it's already in the tree.
	 *
	 * @param ItemWithCode $child new Item
	 * @param ItemWithCode|null $parent some existing Item as parent, NULL if it has no parent (root Item)
	 */
	public function addToTree(ItemWithCode $child, ItemWithCode $parent = null)
	{
		if ($parent !== null && !$this->database->itemDAO()->itemVisible($parent)) {
			throw new NotFoundException($parent->getCode());
		}

		if (!$this->database->itemDAO()->itemVisible($child)) {
			throw new NotFoundException($child->getCode());
		}

		$this->addItemAsRoot($child);
		if ($parent !== null) {
			$this->setParent($parent, $child);
		}
	}

	/**
	 * Move an Item, and its descendants, somewhere else.
	 *
	 * @param ItemWithCode $item Item to move
	 * @param ItemWithCode|null $newParent some existing Item as parent, NULL if parent should be removed (turn into
	 *     root Item)
	 */
	public function moveItem(ItemWithCode $item, ItemWithCode $newParent = null)
	{
		if (!$this->database->itemDAO()->itemVisible($newParent)) {
			throw new NotFoundException($newParent->getCode());
		}

		if (!$this->database->itemDAO()->itemVisible($item)) {
			throw new NotFoundException($item->getCode());
		}

		// If it is an ItemCode or something else that doesn't have the LostAt property fetched, unlose it
		// If it's marked as lost and we know it, unlose it
		if (!($item instanceof Item) || ($item instanceof Item && $item->getLostAt() !== null)) {
			$this->database->itemDAO()->unlose($item);
		}

		$this->splitSubtree($item);
		if ($newParent !== null) {
			$this->setParent($newParent, $item);
		}
	}

	/**
	 * Get root parent.
	 * It will not error if the item is the root
	 * 
	 * @param ItemWithCode $item
	 * 
	 * @return array 0 is the root parent, 1 indicates whether the input item is already the parent, returns null if not found
	 */
	public function getRootParent(ItemWithCode $item)
	{
		$statement = $this->getPDO()->prepare('SELECT Ancestor, Depth FROM Tree WHERE Descendant = ? AND Depth=(SELECT max(Depth) FROM Tree WHERE Descendant = ?)');

		try {
			$statement->execute([$item->getCode(), $item->getCode()]);

			$row = $statement->fetch(\PDO::FETCH_ASSOC);

			if ($row == false) {
				return;
			}

			return [new ItemCode($row['Ancestor']), $row['Depth'] == 0];
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Get path to an item and set it.
	 * Item code must be not null, obviously.
	 *
	 * @param Item $item
	 */
	public function getPathTo(Item $item)
	{
		$item->addAncestors($this->getPathToArray($item));
	}

	/**
	 * Get path to an item (item itself excluded).
	 *
	 * @param ItemWithCode $item
	 *
	 * @return ItemWithCode[] 0 is direct parent, 1 is parent's parent, and so on
	 */
	private function getPathToArray(ItemWithCode $item)
	{
		$statement = $this->getPDO()->prepare('SELECT Ancestor FROM Tree WHERE Descendant = ? ORDER BY Depth DESC');

		try {
			$statement->execute([$item->getCode()]);

			$result = [];

			while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$result[] = new ItemCode($row['Ancestor']);
			}

			if (!empty($result)) {
				$lastcode = array_pop($result)->getCode();
				if ($lastcode !== $item->getCode()) {
					throw new \LogicException('Path to ' . $item->getCode() . " terminates at $lastcode instead");
				}
			}

			return $result;
		} finally {
			$statement->closeCursor();
		}
	}

	public function removeFromTree(ItemWithCode $item)
	{
		if (!$this->database->itemDAO()->itemVisible($item)) {
			throw new NotFoundException($item->getCode());
		}

		/* This is readable but doesn't work in MySQL (TODO: implemented in MariaDB 10.3, replace query once it's out):
		 *
		 * DELETE * FROM Tree
		 * WHERE DescendantID IN (
		 * SELECT DescendantID
		 * FROM Tree
		 * WHERE AncestorID = ?)
		 *
		 * This is incomprehensible (I can only HOPE it does the same job) but works in MySQL:
		 */
		$statement = $this->getPDO()->prepare(
			'DELETE Tree.* FROM Tree, Tree AS Pointless
        WHERE Tree.Descendant=Pointless.Descendant
        AND Pointless.Ancestor = ?;'
		);

		try {
			$statement->execute([$item->getCode()]);
		} finally {
			$statement->closeCursor(); // TODO: needed?
		}
	}

	/**
	 * Add an Item to Tree, considering it a root. Basically adds a row like (Item, Item, Depth = 0).
	 * Items outside Tree are ignored by almost every query, so this is the most important step to bring an item into
	 * existence.
	 *
	 * @param ItemWithCode $item
	 */
	private function addItemAsRoot(ItemWithCode $item)
	{
		$pdo = $this->getPDO();
		$statement = $pdo->prepare('INSERT INTO Tree (Ancestor, Descendant, Depth) VALUES (?, ?, 0)');
		$id = $item->getCode();
		$statement->execute([$id, $id]);
	}


	/**
	 * addEdge, basically. It's better to use addToTree(), which in turn calls this function.
	 *
	 * @param ItemWithCode $parent
	 * @param ItemWithCode $child
	 *
	 *@see addToTree
	 *
	 */
	private function setParent(ItemWithCode $parent, ItemWithCode $child)
	{
		$parentID = $parent->getCode();
		$childID = $child->getCode();

		if ($parentID === $childID) {
			// Adding an item into itself "works", unfortunately.
			// It doesn't add a row with Depth=0, it places an item into itself and creates new useless paths, which doesn't make any sense.
			// So we need to check it here
			return;
		}

		$pdo = $this->getPDO();

		// This is the standard query for subtree insertion, just with a cartesian product which is actually a join, instead of a join. It's exactly the same thing.
		$statement = $pdo->prepare(
			'INSERT INTO Tree (Ancestor, Descendant, Depth)
		SELECT ltree.Ancestor, rtree.Descendant, ltree.Depth+rtree.Depth+1
		FROM Tree ltree, Tree rtree 
		WHERE ltree.Descendant = :parent AND rtree.Ancestor = :new;'
		);

		$statement->bindValue(':parent', $parentID, \PDO::PARAM_STR);
		$statement->bindValue(':new', $childID, \PDO::PARAM_STR);
		$statement->execute();
	}

	/**
	 * Turn an Item into a root, preserving its subtree
	 *
	 * @param ItemWithCode $item the item
	 */
	private function splitSubtree(ItemWithCode $item)
	{
		// straight from Bill Karwin's post (https://www.percona.com/blog/2011/02/14/moving-subtrees-in-closure-table/)
		// other solutions exist, but they don't work in MySQL BECAUSE MYSQL, THAT'S WHY.
		$statement = $this->getPDO()->prepare(
			'DELETE a.* FROM Tree AS a
		JOIN Tree AS d ON a.Descendant = d.Descendant
		LEFT JOIN Tree AS x
		ON x.Ancestor = d.Ancestor AND x.Descendant = a.Ancestor
		WHERE d.Ancestor = ? AND x.Ancestor IS NULL;'
		);


		$statement->bindValue(1, $item->getCode(), \PDO::PARAM_STR);
		$statement->execute();
	}
}
