<?php

namespace WEEEOpen\Tarallo\APIv1;

use JSend\InvalidJSendException;
use JSend\JSendResponse;

/**
 * @deprecated extend JSendResponse to add these static constructors and asResponseInterface
 */
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
		if($data === null) {
			return new self(JSendResponse::success($data), 200);
		} else if($data instanceof \JsonSerializable) {
			// I hope that this works. JSendResponse wants an array, just an array, only an array, doesn't matter if objects are JSON-serializable or not.
			$arrayed = $data->jsonSerialize();
			if(json_last_error() !== JSON_ERROR_NONE) {
				throw new \LogicException('Cannot encode response: ' . json_last_error_msg());
			}
			return new self(JSendResponse::success($arrayed), 200);
		} else {
			// Or throw
			return new self(JSendResponse::success((array) $data), 200);
		}
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
	 * @deprecated
	 */
	public function send() {
		http_response_code($this->httpStatus);
		$this->response->setEncodingOptions(\JSON_PRETTY_PRINT);
		$this->response->respond();
		if($this->response->isError()) {
			exit(1);
		} else {
			exit(0);
		}
	}

	/**
	 * @return int
	 *
	 * @deprecated
	 */
	public function getHttpStatus() {
		return $this->httpStatus;
	}

	/**
	 * Get content of response as a string with JSON-serialized data
	 *
	 * @return \WEEEOpen\Tarallo\Server\HTTP\Response
	 */
	public function asResponseInterface(): \WEEEOpen\Tarallo\Server\HTTP\Response {
		$result = json_encode($this->response);
		if($result === false) {
			throw new \LogicException('Cannot encode response');
		}

		return new \WEEEOpen\Tarallo\Server\HTTP\Response($this->httpStatus, 'application/json', $result);
	}
}
