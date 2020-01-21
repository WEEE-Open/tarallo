<?php


namespace WEEEOpen\Tarallo;

/**
 * Class ItemIncomplete
 *
 * @package WEEEOpen\Tarallo
 */
class ItemIncomplete
	implements \JsonSerializable,
	ItemWithCode,
	ItemWithFeatures,
	ItemWithContent,
	ItemWithLocation {
	use ItemTraitOptionalCode;
	use ItemTraitContent;
	use ItemTraitLocation;
	use ItemTraitOptionalFeatures;
}
