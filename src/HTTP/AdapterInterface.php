<?php

namespace WEEEOpen\Tarallo\Server\HTTP;


interface AdapterInterface {
	static function go(Request $request): Response;
}
