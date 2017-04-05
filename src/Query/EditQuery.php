<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;

class EditQuery extends PostJSONQuery implements \JsonSerializable {
    // getting an abstract class for fields out of GetQUery would be nice, but there's the diamond problem...

    protected function parseContent($array)
    {
        // TODO: Implement parseContent() method.
    }

    public function run($user, Database $db)
    {
        // TODO: Implement run() method.
    }

    function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}