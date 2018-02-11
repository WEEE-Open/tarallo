<?php

namespace WEEEOpen\Tarallo\Server;

use JSend\JSendResponse;

class Response {
	/**
	 * Send success. Passing an empty array will send null, as per JSend specification.
	 *
	 * @param \JsonSerializable|null - $data
	 */
	public static function sendSuccess($data = null) {
		if(is_array($data) && empty($data)) {
			$data = null;
		}
		$response = new JSendResponse(JSendResponse::SUCCESS, $data);
		http_response_code(200);
		$response->respond();
		exit(0);
	}

	/**
	 * Send fail. When there are missing or invalid parameters in request or similar errors.
	 *
	 * @param string $parameter Which parameter caused the failure
	 * @param string $reason for which reason
	 *
	 * @see sendError - for errors on the server part
	 */
	public static function sendFail($parameter, $reason) {
		http_response_code(400);
		$response = new JSendResponse(JSendResponse::FAIL, [$parameter => $reason]);
		$response->respond();
		exit(0);
	}

	/**
	 * Send error, when the database catches fire or other circumstances where users can't do anything
	 *
	 * @param string $message Error message
	 * @param string|null $code Error code
	 * @param array|null $data Additional data (e.g. stack trace)
	 * @param int $http HTTP status code
	 *
	 * @see sendFail - for errors on the user part
	 */
	public static function sendError($message, $code = null, $data = null, $http = 500) {
		http_response_code($http);
		$response = new JSendResponse(JSendResponse::ERROR, $data, $message, $code);
		$response->respond();
		exit(1);
	}
}
