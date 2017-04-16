<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\User;

// TODO: call these methods somewhere
final class ModificationDAO extends DAO {
	private $currentModificationId = null;

	public function setItemMoved(ItemIncomplete $item, $from, $to) {
		$from = $this->itemToIdOrNull($from);
		$to = $this->itemToIdOrNull($to);
		$itemID = $this->database->itemDAO()->getItemId($item);

		$pdo = $this->getPDO();
		if($this->itemModifiedStatement === null) {
			$this->itemModifiedStatement = $pdo->prepare('INSERT INTO ItemLocationModification (ModificationID, ItemID, ParentFrom, ParentTo) VALUES (?, ?, ?, ?)');
		}
		$this->itemModifiedStatement->bindValue(1, $this->getModificationId(), \PDO::PARAM_INT);
		$this->itemModifiedStatement->bindValue(2, $itemID, \PDO::PARAM_INT);
		if($from === null) {
			$this->itemModifiedStatement->bindValue(3, null, \PDO::PARAM_NULL);
		} else {
			$this->itemModifiedStatement->bindValue(3, $from, \PDO::PARAM_INT);
		}
		if($to === null) {
			$this->itemModifiedStatement->bindValue(4, null, \PDO::PARAM_NULL);
		} else {
			$this->itemModifiedStatement->bindValue(4, $to, \PDO::PARAM_INT);
		}
		$this->itemModifiedStatement->execute();
	}

	private function itemToIdOrNull($item) {
		if($item === null) {
			return null;
		}

		if($item instanceof ItemIncomplete) {
			return $this->database->itemDAO()->getItemId($item);
		}

		throw new InvalidParameterException('itemToIdOrNull() expects ItemIncomplete or null, ' . gettype($item) . ' given');
	}

	private $itemModifiedStatement = null;

	public function setItemModified(ItemIncomplete $item) {
		$pdo = $this->getPDO();
		if($this->itemModifiedStatement === null) {
			$this->itemModifiedStatement = $pdo->prepare('INSERT INTO ItemModification (ModificationID, ItemID) SELECT ?, ItemID FROM Item WHERE Item.Code = ?');
		}
		$this->itemModifiedStatement->execute([$this->getModificationId(), $this->database->itemDAO()->getItemId($item)]);
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
		$pdo = $this->getPDO();
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