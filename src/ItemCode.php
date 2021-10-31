<?php

namespace WEEEOpen\Tarallo;

/**
 * Class ItemIncomplete
 * An item code and that's it. Serializes to a string.
 *
 * @package WEEEOpen\Tarallo
 */
class ItemCode implements \JsonSerializable, ItemWithCode
{
	use ItemTraitCode;
}
