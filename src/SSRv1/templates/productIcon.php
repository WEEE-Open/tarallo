<?php

/** @var String $type */
/** @var String $color */

$url = [
	"adapter" => "/static/icons/adapter.svg",
	"audio-card" => "/static/icons/audio.svg",
	"bluetooth" => "/static/icons/bluetooth.svg",
	//"ports-bracket" => "/static/icons/ports-bracket.svg",
	"cpu" => "/static/icons/cpu.svg",
	"card-reader" => "/static/icons/sd reader.svg",
	"case" => "/static/icons/case.svg",
	"laptop" => "/static/icons/laptop.svg",
	"ethernet-card" => "/static/icons/network.svg",
	"external-psu" => "/static/icons/external psu.svg",
	"fdd" => "/static/icons/floppy drive.svg",
	"fan-controller" => "/static/icons/fan.svg",
	"graphics-card" => "/static/icons/gpu.svg",
	"hdd" => "/static/icons/hdd.svg",
	"keyboard" => "/static/icons/keyboard.svg",
	"location" => "/static/icons/location.svg",
	"modem-card" => "/static/icons/modem.svg",
	"modem-router" => "/static/icons/router.svg",
	"monitor" => "/static/icons/monitor.svg",
	"motherboard" => "/static/icons/motherboard.svg",
	"mouse" => "/static/icons/mouse.svg",
	"network-hub" => "/static/icons/network hub.svg",
	"network-switch" => "/static/icons/network switch.svg",
	"odd" => "/static/icons/dvd drive.svg",
	"inventoried-object" => "/static/icons/inventory.svg",
	"other-card" => "/static/icons/card.svg",
	"psu" => "/static/icons/atx powersuply.svg",
	"printer" => "/static/icons/printer.svg",
	"projector" => "/static/icons/projector.svg",
	"ram" => "/static/icons/ram.svg",
	"ssd" => "/static/icons/ssd.svg",
	"scanner" => "/static/icons/scanner.svg",
	"smartphone-tabled" => "/static/icons/smartphone.svg",
	"storage-card" => "/static/icons/sd card.svg",
	"tv-card" => "/static/icons/tv tuner.svg",
	"usbhub" => "/static/icons/usb hub.svg",
	"wifi-card" => "/static/icons/wifi.svg",
	//"zip-drive" => "/static/icons/zip drive.svg",
	"unknown" => "/static/icons/unknown.svg"
][$type ?? "unknown"] ?? "/static/icons/unknown.svg"
?>
<img src="<?=$url?>" alt="" title="<?=$type?>" class="icon p-2"<?php if (($color ?? "white") == "black") {
	echo ' style="filter:invert(1);"';
		  }?>>