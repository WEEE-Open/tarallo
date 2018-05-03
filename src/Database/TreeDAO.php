<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\NotFoundException;

final class TreeDAO extends DAO {
	const EXCEPTION_CODE_PARENT = 1;
	const EXCEPTION_CODE_CHILD = 2;
	
	/**
	 * Add a new Item to the tree. Don't call if it's already in the tree.
	 *
	 * @param ItemIncomplete $child new Item
	 * @param ItemIncomplete|null $parent some existing Item as parent, NULL if it has no parent (root Item)
	 */
	public function addToTree(ItemIncomplete $child, ItemIncomplete $parent = null) {
		if($parent !== null && !$this->database->itemDAO()->itemVisible($parent)) {
			throw new NotFoundException(self::EXCEPTION_CODE_PARENT);
		}

		if(!$this->database->itemDAO()->itemVisible($child)) {
			throw new NotFoundException(self::EXCEPTION_CODE_CHILD);
		}

		$this->addItemAsRoot($child);
		if($parent !== null) {
			$this->setParent($parent, $child);
		}
	}

	/**
	 * Move an Item, and its descendants, somewhere else.
	 *
	 * @param ItemIncomplete $item Item to move
	 * @param ItemIncomplete|null $newParent some existing Item as parent, NULL if parent should be removed (turn into
	 *     root Item)
	 */
	public function moveItem(ItemIncomplete $item, ItemIncomplete $newParent = null) {
		if(!$this->database->itemDAO()->itemVisible($newParent)) {
			throw new NotFoundException(self::EXCEPTION_CODE_PARENT);
		}

		if(!$this->database->itemDAO()->itemVisible($item)) {
			throw new NotFoundException(self::EXCEPTION_CODE_CHILD);
		}

		$this->splitSubtree($item);
		if($newParent !== null) {
			$this->setParent($newParent, $item);
		}
	}

	private $getPathToStatement = null;

	/**
	 * Get path to an item and set it.
	 * Item code must be not null, obviously.
	 *
	 * @param Item $item
	 */
	public function getPathTo(Item $item) {
		$item->addAncestors($this->getPathToArray($item));
	}

	/**
	 * Get path to an item (item itself excluded).
	 *
	 * @param ItemIncomplete $item
	 *
	 * @return ItemIncomplete[] 0 is direct parent, 1 is parent's parent, and so on
	 */
	private function getPathToArray(ItemIncomplete $item) {
		if($this->getPathToStatement === null) {
			$this->getPathToStatement = $this->getPDO()->prepare('SELECT Ancestor FROM Tree WHERE Descendant = ? ORDER BY Depth DESC');
		}

		try {
			$this->getPathToStatement->execute([$item->getCode()]);

			$result = [];

			while(($row = $this->getPathToStatement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$result[] = new ItemIncomplete($row['Ancestor']);
			}

			if(!empty($result)) {
				$lastcode = array_pop($result)->getCode();
				if($lastcode !== $item->getCode()) {
					throw new \LogicException('Path to ' . $item->getCode() . " terminates at $lastcode instead");
				}
			}

			return $result;
		} finally {
			$this->getPathToStatement->closeCursor();
		}
	}

	private $removeFromTreeStatement = null;

	public function removeFromTree(ItemIncomplete $item) {
		if(!$this->database->itemDAO()->itemVisible($item)) {
			throw new NotFoundException(self::EXCEPTION_CODE_CHILD);
		}

		if($this->removeFromTreeStatement === null) {
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
			$this->removeFromTreeStatement = $this->getPDO()->prepare('DELETE Tree.* FROM Tree, Tree AS Pointless
            WHERE Tree.Descendant=Pointless.Descendant
            AND Pointless.Ancestor = ?;');
		}

		try {
			$this->removeFromTreeStatement->execute([$item->getCode()]);
		} finally {
			$this->removeFromTreeStatement->closeCursor(); // TODO: needed?
		}
	}

	private $addItemAsRootStatement = null;

	/**
	 * Add an Item to Tree, considering it a root. Basically adds a row like (Item, Item, Depth = 0).
	 * Items outside Tree are ignored by almost every query, so this is the most important step to bring an item into
	 * existence.
	 *
	 * @param ItemIncomplete $item
	 */
	private function addItemAsRoot(ItemIncomplete $item) {
		$pdo = $this->getPDO();
		if($this->addItemAsRootStatement === null) {
			$this->addItemAsRootStatement = $pdo->prepare('INSERT INTO Tree (Ancestor, Descendant, Depth) VALUES (?, ?, 0)');
		}
		$id = $item->getCode();
		$this->addItemAsRootStatement->execute([$id, $id]);
	}

	private $setParentStatement = null;

	/**
	 * addEdge, basically. It's better to use addToTree(), which in turn calls this function.
	 *
	 * @see addToTree
	 *
	 * @param ItemIncomplete $parent
	 * @param ItemIncomplete $child
	 */
	private function setParent(ItemIncomplete $parent, ItemIncomplete $child) {
		$parentID = $parent->getCode();
		$childID = $child->getCode();

		if($parentID === $childID) {
			// Adding an item into itself "works", unfortunately.
			// It doesn't add a row with Depth=0, it places an item into itself and creates new useless paths, which doesn't make any sense.
			// So we need to check it here
			return;
		}

		$pdo = $this->getPDO();

		if($this->setParentStatement === null) {
			// This is the standard query for subtree insertion, just with a cartesian product which is actually a join, instead of a join. It's exactly the same thing.
			$this->setParentStatement = $pdo->prepare('INSERT INTO Tree (Ancestor, Descendant, Depth)
			SELECT ltree.Ancestor, rtree.Descendant, ltree.Depth+rtree.Depth+1
			FROM Tree ltree, Tree rtree 
			WHERE ltree.Descendant = :parent AND rtree.Ancestor = :new;');
		}

		$this->setParentStatement->bindValue(':parent', $parentID, \PDO::PARAM_STR);
		$this->setParentStatement->bindValue(':new', $childID, \PDO::PARAM_STR);
		$this->setParentStatement->execute();
	}

	private $extractFromTreeStatement;

	/**
	 * Turn an Item into a root, preserving its subtree
	 *
	 * @param ItemIncomplete $item the item
	 */
	private function splitSubtree(ItemIncomplete $item) {
		if($this->extractFromTreeStatement === null) {
			// straight from Bill Karwin's post (https://www.percona.com/blog/2011/02/14/moving-subtrees-in-closure-table/)
			// other solutions exist, but they don't work in MySQL BECAUSE MYSQL, THAT'S WHY.
			$this->extractFromTreeStatement = $this->getPDO()->prepare('DELETE a.* FROM Tree AS a
			JOIN Tree AS d ON a.Descendant = d.Descendant
			LEFT JOIN Tree AS x
			ON x.Ancestor = d.Ancestor AND x.Descendant = a.Ancestor
			WHERE d.Ancestor = ? AND x.Ancestor IS NULL;');
		}

		$this->extractFromTreeStatement->bindValue(1, $item->getCode(), \PDO::PARAM_STR);
		$this->extractFromTreeStatement->execute();
	}
}
