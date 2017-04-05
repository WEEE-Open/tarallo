<?php

namespace WEEEOpen\Tarallo\Database;

class Database {
    /** @var \PDO */
    private $pdo = null;
    private $userDAO = null;
    private $itemDAO = null;
    private $featureDAO = null;

    protected function getPDO() {
        if($this->pdo === null) {
            $this->connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
        }
        return $this->pdo;
    }

    private function connect($user, $pass, $dsn) {
        try {
            $this->pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_AUTOCOMMIT => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            throw new \Exception('Cannot connect to database: ' . $e->getMessage());
        }
    }

    public function disconnect() {
        $this->pdo = null;
        $this->userDAO = null;
        $this->itemDAO = null;
    }

    public function __destruct() {
        // aaaaaaand this is completely useless.
        $this->disconnect();
    }

    public function userDAO() {
        if($this->userDAO === null) {
            $this->userDAO = new UserDAO($this);
        }
        return $this->userDAO;
    }

    public function itemDAO() {
        if($this->itemDAO === null) {
            $this->itemDAO = new ItemDAO($this);
        }
        return $this->itemDAO;
    }

    public function featureDAO() {
        if($this->featureDAO === null) {
            $this->featureDAO = new FeatureDAO($this);
        }
        return $this->featureDAO;
    }

    private $currentModificationId = null;

    public function modifcationBegin(\WEEEOpen\Tarallo\User $user, $notes = null) {
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

    private function getNewModificationId(\WEEEOpen\Tarallo\User $user, $notes) {
        // TODO: decouple Database from User by passing string instead of User?
        $pdo = $this->getPDO();
        $stuff = $pdo->prepare('INSERT INTO Modification (UserID, `Date`, Notes) SELECT `User`.UserID, :dat, :notes FROM `User` WHERE `User`.Name = :username');
        $stuff->bindValue(':username', $user->getUsername());
        $stuff->bindValue(':dat', time());
        $stuff->bindValue(':notes', $notes);
        $stuff->execute();
        return $pdo->lastInsertId();
    }

    protected function getModificationId() {
        if(!$this->getPDO()->inTransaction()) {
            throw new \LogicException('Trying to read modification ID without an active transaction');
        }
        if($this->currentModificationId === null) {
            throw new \LogicException('Transaction started but no modification ID set (= something went horribly wrong)');
        }
        return $this->currentModificationId;
    }
}