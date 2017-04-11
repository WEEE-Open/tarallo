<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\ItemIncomplete;

final class TreeDAO extends DAO {

    public function addNewToTree(ItemIncomplete $child, ItemIncomplete $parent = null) {
        if ($parent === null) {
            $parent = $child;
        }

        $this->addInTree($parent, $child);
    }

    private $setParentInTreeStatement = null;

    /**
     * addEdge, basically. It's better to use addToTree(), which in turn calls this function.
     *
     * @see addNewToTree
     * @param ItemIncomplete $parent
     * @param ItemIncomplete $child
     */
    private function addInTree(ItemIncomplete $parent, ItemIncomplete $child) {
        $pdo = $this->getPDO();
        if ($this->setParentInTreeStatement === null) {
            $this->setParentInTreeStatement = $pdo->prepare('
			INSERT INTO Tree(AncestorID, DescendantID, Depth)
			SELECT AncestorID, :new1, Depth+1
			FROM Tree
			WHERE DescendantID = :parent
			UNION
			SELECT :new2, :new3, 0;');
        }
        $itemDAO = $this->database->itemDAO();
        $cid = $itemDAO->getItemId($child);

        $this->setParentInTreeStatement->bindValue(':parent', $itemDAO->getItemId($parent), \PDO::PARAM_INT);
        $this->setParentInTreeStatement->bindValue(':new1', $cid, \PDO::PARAM_INT);
        $this->setParentInTreeStatement->bindValue(':new2', $cid, \PDO::PARAM_INT);
        $this->setParentInTreeStatement->bindValue(':new3', $cid, \PDO::PARAM_INT);
        $this->setParentInTreeStatement->execute();
    }

    private $extractFromTreeStatement;

    /**
     * Turn an Item into a root, preserving its subtree
     *
     * @param ItemIncomplete $item the item
     */
    private function setItemAsRoot(ItemIncomplete $item) {
        if($this->extractFromTreeStatement === null) {
            $this->extractFromTreeStatement = $this->getPDO()->prepare('DELETE * FROM Tree
            WHERE (AncestorID IN (SELECT AncestorID FROM Tree WHERE DescendantID = ?)
              AND DescendantID IN (SELECT DescendantID FROM Tree WHERE AncestorID = ?) 
              AND Depth > 0)
            OR (DescendantID = ? AND Depth > 0)');
        }

        $id = $this->database->itemDAO()->getItemId($item);

        $this->extractFromTreeStatement->bindValue(1, $id);
        $this->extractFromTreeStatement->bindValue(2, $id);
        $this->extractFromTreeStatement->bindValue(3, $id);
    }
}