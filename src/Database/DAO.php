<?php

namespace WEEEOpen\Tarallo\Database;

abstract class DAO extends Database {
    protected $database;

    protected function __construct(Database $db) {
        $this->database = $db;
    }

    protected function getPDO() {
        return $this->database->getPDO();
    }
}