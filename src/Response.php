<?php

namespace WEEEOpen\Tarallo;
use JSend\JSendResponse;

class Response {
	public static function sendSuccess($data = null) {
		http_response_code(200);
		$response = new JSendResponse(JSendResponse::SUCCESS, $data);
		$response->respond();
		exit(0);
	}

	public static function sendFail($message = null, $fields = null) {
		http_response_code(200);
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
		$response->respond();
		exit(0);
	}

	public static function sendError($message, $code = null) {
		http_response_code(200);
		$response = new JSendResponse(JSendResponse::ERROR, null, $message, $code);
		$response->respond();
		exit(1);
	}
}
