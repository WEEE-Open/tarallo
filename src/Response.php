<?php

namespace WEEEOpen\Tarallo;
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
	 * @param string|null $message - This shouldn't exist, but... a message, same as Error message.
	 * @param string[]|null $fields - Associative array, from a field/key/hash/whatever that was present in the request, to a string containg an explanation of what's wrong there
	 * @see sendError - for errors on the server part
	 */
	public static function sendFail($message = null, $fields = null) {
		$data = [];

		if(is_string($message)) {
			$data = ['message' => $message];
		} else if($message != null) {
			throw new \LogicException('$message must be a string or null');
		}

		if(is_array($fields)) {
			$data = array_merge($fields, $data);
		} else if($fields != null) {
			throw new \LogicException('$fields must be an array or null');
		}

		$response = new JSendResponse(JSendResponse::FAIL, $data);
		http_response_code(200);
		$response->respond();
		exit(0);
	}

	/**
	 * Send error, when the database catches fire or other circumstances where users can't do anything
	 *
	 * @param string $message - Error message
	 * @param null|int $code - Error code
	 * @see sendFail - for errors on the user part
	 */
	public static function sendError($message, $code = null) {
		$response = new JSendResponse(JSendResponse::ERROR, null, $message, $code);
		http_response_code(200);
		$response->respond();
		exit(1);
	}
}
