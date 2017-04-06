<?php

namespace WEEEOpen\Tarallo\Database;

abstract class DAO {
    protected $database;

    public function __construct(Database $db) {
        $this->database = $db;
    }

    protected function getPDO() {
        return $this->database->getPDO();
    }

    protected static function multipleIn($prefix, $array) {
        $in = 'IN (';
        foreach($array as $k => $v) {
            $in .= $prefix . $k . ', ';
        }
        return substr($in, 0, strlen($in) - 2) . ')'; //remove last ', '
    }
}