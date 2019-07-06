<?php


namespace WEEEOpen\Tarallo\Server;

/**
 * Class ItemIncomplete
 *
 * @package WEEEOpen\Tarallo\Server
 */
class ItemIncomplete
	implements \JsonSerializable,
	ItemWithCode,
	ItemWithCodeAndFeatures,
	ItemWithContent,
	ItemWithLocation {
	use ItemTraitOptionalCode;
	use ItemTraitContent;
	use ItemTraitLocation;
	use ItemTraitOptionalFeatures;

	// TODO: something?
}
