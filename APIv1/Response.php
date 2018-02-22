<?php

namespace WEEEOpen\Tarallo\APIv1;

use JSend\InvalidJSendException;
use JSend\JSendResponse;

class Response {
	/** @var int $httpStatus */
	private $httpStatus;
	/** @var JSendResponse $response */
	private $response;

	private function __construct(JSendResponse $response, $http) {
		$this->httpStatus = $http;
		$this->response = $response;
	}

	/**
	 * Send success. Passing an empty array will send null, as per JSend specification.
	 *
	 * @param \JsonSerializable|null - $data
	 *
	 * @return Response
	 */
	public static function ofSuccess($data) {
		return new self(JSendResponse::success($data), 200);
	}

	/**
	 * Send fail. When there are missing or invalid parameters in request or similar errors.
	 *
	 * @param string $parameter Which parameter caused the failure
	 * @param string $reason for which reason
	 *
	 * @return Response
	 * @see ofError - for errors on the server part
	 */
	public static function ofFail($parameter, $reason) {
		return new self(JSendResponse::fail([$parameter => $reason]), 400);
	}

	/** @noinspection PhpDocMissingThrowsInspection */
	/**
	 * Send error, when the database catches fire or other circumstances where users can't do anything
	 *
	 * @param string $message Error message
	 * @param string|null $code Error code
	 * @param array|null $data Additional data (e.g. stack trace)
	 * @param int $http HTTP status code
	 *
	 * @see ofFail - for errors on the user part
	 * @return Response
	 */
	public static function ofError($message, $code = null, $data = null, $http = 500) {
		try {
			$response = JSendResponse::error($message, $code, $data);
		} catch(InvalidJSendException $e) {
			$response = JSendResponse::error('Error while generating error response');
			$http = 500;
		}

		return new self($response, $http);
	}

	/**
	 * Set HTTP status code, send response and exit.
	 */
	public function send() {
		$this->response->setEncodingOptions(\JSON_PRETTY_PRINT);
		$this->response->respond();
		http_response_code($this->httpStatus);
		if($this->response->isError()) {
			exit(1);
		} else {
			exit(0);
		}
	}

	/**
	 * @return int
	 */
	public function getHttpStatus() {
		return $this->httpStatus;
	}

	/**
	 * Get content of response as the array that would be serialized to JSON
	 *
	 * @return array
	 */
	public function asArray() {
		return $this->response->asArray();
	}
}
