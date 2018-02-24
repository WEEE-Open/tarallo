<?php

namespace WEEEOpen\Tarallo\APIv1;

use JSend\InvalidJSendException;
use JSend\JSendResponse;
use WEEEOpen\Tarallo\Server\HTTP\Response;

class JSend extends JSendResponse {
	public function __construct(
		string $status,
		array $data = null,
		string $errorMessage = null,
		string $errorCode = null
	) {
		parent::__construct($status, $data, $errorMessage, $errorCode);
	}

	/** @var int $httpStatus */
	private $httpStatus = 200;

	/**
	 * Send success. Passing an empty array will send null, as per JSend specification.
	 *
	 * @param \JsonSerializable|null - $data
	 *
	 * @return JSend
	 */
	public static function ofSuccess($data) {
		if($data === null) {
			return self::success($data);
		} else if($data instanceof \JsonSerializable) {
			// I hope that this works. JSendResponse wants an array, just an array, only an array, doesn't matter if objects are JSON-serializable or not.
			$arrayed = $data->jsonSerialize();
			if(json_last_error() !== JSON_ERROR_NONE) {
				throw new \LogicException('Cannot encode response: ' . json_last_error_msg());
			}
			return self::success($arrayed);
		} else {
			// Do this or throw an exception?
			return self::success((array) $data);
		}
	}

	/**
	 * Send fail. When there are missing or invalid parameters in request or similar errors.
	 *
	 * @param string $parameter Which parameter caused the failure
	 * @param string $reason for which reason
	 *
	 * @return JSend
	 * @see ofError - for errors on the server part
	 */
	public static function ofFail($parameter, $reason) {
		$response = self::fail([$parameter => $reason]);
		$response->httpStatus = 400;
		return $response;
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
	 * @return JSend
	 */
	public static function ofError($message, $code = null, $data = null, $http = 500) {
		try {
			$response = self::error($message, $code, $data);
			$response->httpStatus = $http;
		} catch(InvalidJSendException $e) {
			$response = self::error('Error while generating error response');
		}

		return $response;
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
	 * @return Response
	 */
	public function asResponseInterface(): Response {
		$result = json_encode($this);
		if($result === false || json_last_error() !== JSON_ERROR_NONE) {
			throw new \LogicException('Cannot encode response: ' . json_last_error_msg());
		}

		return new Response($this->httpStatus, 'application/json', $result);
	}

	public function respond() {
		throw new \LogicException('Cannot send directly, use asResponseInterface instead');
	}
}
