<?php

namespace WEEEOpen\Tarallo\APIv1;

class JSend {
	const SUCCESS = 'success';
	const ERROR = 'error';
	const FAIL = 'fail';

	public static function error(string $message, $code = null, $data = null): string {
		$result = [
			'status' => 'error',
			'message' => $message
		];
		if($code !== null) {
			$result['code'] = $code;
		}
		if($data !== null) {
			$result['data'] = $data;
		}
		return json_encode($result);
	}

	public static function fail($data = null): string {
		return json_encode([
			'status' => 'fail',
			'data' => $data
		]);
	}

	public static function success($data = null): string {
		return json_encode([
			'status' => 'success',
			'data' => $data
		]);
	}
}
