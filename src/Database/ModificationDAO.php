<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\User;


final class ModificationDAO extends DAO {
	private $currentModificationId = null;

	/**
	 * Mark that an Item was moved. Or deleted.
	 *
	 * @param ItemIncomplete $item - item that has been moved
	 * @param ItemIncomplete|null $to - new parent, or null if deleted
	 *
	 * @throws InvalidParameterException
	 */
	public function setItemMoved(ItemIncomplete $item, ItemIncomplete $to = null) {
		if($to === null) {
			$this->setItemMovedDeleted($item);
		} else {
			$this->setItemMovedTo($item, $to);
		}
	}

	private $itemLocationModifiedStatement = null;

	private function setItemMovedTo(ItemIncomplete $item, ItemIncomplete $to) {
		$itemID   = $this->database->itemDAO()->getItemId($item);
		$parentID = $this->database->itemDAO()->getItemId($to);

		if($this->itemLocationModifiedStatement === null) {
			$this->itemLocationModifiedStatement = $this->getPDO()->prepare('INSERT INTO ItemLocationModification (ModificationID, ItemID, ParentTo) VALUES (?, ?, ?)');
		}
		$this->itemLocationModifiedStatement->bindValue(1, $this->getModificationId(), \PDO::PARAM_INT);
		$this->itemLocationModifiedStatement->bindValue(2, $itemID, \PDO::PARAM_INT);
		$this->itemLocationModifiedStatement->bindValue(3, $parentID, \PDO::PARAM_INT);
		$this->itemLocationModifiedStatement->execute();
	}

	private $itemDeletedStatement = null;

	private function setItemMovedDeleted(ItemIncomplete $item) {
		$itemID = $this->database->itemDAO()->getItemId($item);

		if($this->itemDeletedStatement === null) {
			$this->itemDeletedStatement = $this->getPDO()->prepare('INSERT INTO ItemModificationDelete (ModificationID, ItemID) VALUES (?, ?)');
		}
		$this->itemDeletedStatement->bindValue(1, $this->getModificationId(), \PDO::PARAM_INT);
		$this->itemDeletedStatement->bindValue(2, $itemID, \PDO::PARAM_INT);
		$this->itemDeletedStatement->execute();
	}

	private $itemModifiedStatement = null;

	public function setItemModified(ItemIncomplete $item) {
		$pdo = $this->getPDO();
		if($this->itemModifiedStatement === null) {
			$this->itemModifiedStatement = $pdo->prepare('INSERT INTO ItemModification (ModificationID, ItemID) SELECT ?, ItemID FROM Item WHERE Item.Code = ?');
		}
		$this->itemModifiedStatement->execute([
			$this->getModificationId(),
			$this->database->itemDAO()->getItemId($item)
		]);
	}

	public function modifcationBegin(User $user, $notes = null) {
		// TODO: check user?
		$pdo = $this->getPDO();
		if($pdo->inTransaction()) {
			throw new \LogicException('Trying to start nested transactions in modificationBegin');
		}
		$pdo->beginTransaction();
		$this->currentModificationId = $this->getNewModificationId($user, $notes);
	}

	public function modificationCommit() {
		$this->getPDO()->commit();
		$this->currentModificationId = null;
	}

	public function modificationRollback() {
		$this->getPDO()->rollBack();
		$this->currentModificationId = null;
	}

	private function getNewModificationId(User $user, $notes) {
		// TODO: decouple ModificationDAO from User by passing string instead of User?
		$pdo   = $this->getPDO();
		$stuff = $pdo->prepare('INSERT INTO Modification (UserID, `Date`, Notes) SELECT `User`.UserID, :dat, :notes FROM `User` WHERE `User`.Name = :username');
		$stuff->bindValue(':username', $user->getUsername());
		$stuff->bindValue(':dat', time());
		$stuff->bindValue(':notes', $notes);
		$stuff->execute();

		return $pdo->lastInsertId();
	}

	public function getModificationId() {
		if(!$this->getPDO()->inTransaction()) {
			throw new \LogicException('Trying to read modification ID without an active transaction');
		}
		if($this->currentModificationId === null) {
			throw new \LogicException('Transaction started but no modification ID set (= something went horribly wrong)');
		}

		return $this->currentModificationId;
	}
}
