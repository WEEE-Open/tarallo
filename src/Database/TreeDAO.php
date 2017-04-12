<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\ItemIncomplete;

final class TreeDAO extends DAO {
	/**
	 * Add a new Item to the tree. Don't call if it's already in the tree.
	 *
	 * @param ItemIncomplete $child new Item
	 * @param ItemIncomplete|null $parent some existing Item as parent, NULL if it has no parent (root Item)
	 */
    public function addToTree(ItemIncomplete $child, ItemIncomplete $parent = null) {
        $this->addItemAsRoot($child);
        if($parent !== null) {
	        $this->setParent($parent, $child);
        }
    }

	/**
	 * Move an Item, and its descendants, somewhere else.
	 *
	 * @param ItemIncomplete $item Item to move
	 * @param ItemIncomplete|null $newParent some existing Item as parent, NULL if parent should be removed (turn into root Item)
	 */
    public function moveItem(ItemIncomplete $item, ItemIncomplete $newParent = null) {
    	$this->splitSubtree($item);
	    if($newParent !== null) {
		    $this->setParent($newParent, $item);
	    }
    }

    private $removeFromTreeStatement = null;
    public function removeFromTree(ItemIncomplete $item) {
        if($this->removeFromTreeStatement === null) {
            $this->removeFromTreeStatement = $this->getPDO()->prepare('DELETE * FROM Tree
            WHERE DescendantID IN (
            SELECT DescendantID
            FROM Tree
            WHERE AncestorID = ?);');
        }

        $this->removeFromTreeStatement->execute([$this->database->itemDAO()->getItemId($item)]);
    }

    private $addItemAsRootStatement = null;
	/**
	 * Add an Item to Tree, considering it a root. Basically adds a row like (Item, Item, Depth = 0).
	 * Items outside Tree are ignored by almost every query, so this is the most important step to bring an item into existence.
	 *
	 * @param ItemIncomplete $item
	 */
	private function addItemAsRoot(ItemIncomplete $item) {
		$pdo = $this->getPDO();
		if($this->addItemAsRootStatement === null) {
			$this->addItemAsRootStatement = $pdo->prepare('INSERT INTO Tree (AncestorID, DescendantID, Depth) VALUES (?, ?, 0)');
		}
		$id = $this->database->itemDAO()->getItemId($item);
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
	    $pdo = $this->getPDO();
        if ($this->setParentStatement === null) {
            // This is the standard query for subtree insertion, just with a cartesian product which is actually a join, instead of a join. It's exactly the same thing.
            $this->setParentStatement = $pdo->prepare('INSERT INTO Tree (AncestorID, DescendantID, Depth)
			SELECT ltree.AncestorID, rtree.DescendantID, ltree.Depth+rtree.Depth+1
			FROM Tree ltree, Tree rtree 
			WHERE ltree.DescendantID = :parent AND rtree.AncestorID = :new;');
        }
        $itemDAO = $this->database->itemDAO();
        $parentID = $itemDAO->getItemId($parent);
        $childID = $itemDAO->getItemId($child);

	    if($parentID === $childID) {
	    	// Adding an item into itself works. It doesn't add a row with Depth=0, it places an item into itself and creates new useless paths, which doesn't make any sense.
	    	return;
	    }

        $this->setParentStatement->bindValue(':parent', $parentID, \PDO::PARAM_INT);
        $this->setParentStatement->bindValue(':new', $childID, \PDO::PARAM_INT);
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
            $this->extractFromTreeStatement = $this->getPDO()->prepare('DELETE * FROM Tree AS a
			JOIN Tree AS d ON a.DescendantID = d.DescendantID
			LEFT JOIN Tree AS x
			ON x.AncestorID = d.AncestorID AND x.DescendantID = a.AncestorID
			WHERE d.AncestorID = ? AND x.AncestorID IS NULL;');
        }

        $id = $this->database->itemDAO()->getItemId($item);

        $this->extractFromTreeStatement->bindValue(1, $id);
        $this->extractFromTreeStatement->bindValue(2, $id);
        $this->extractFromTreeStatement->bindValue(3, $id);
    }
}
