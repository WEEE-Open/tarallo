<?php

/** @var int $statusCode */
switch ($statusCode) {
	case 400:
		echo 'Bad Request';
		break;
	case 401:
		echo 'Unauthorized';
		break;
	case 402:
		echo 'Payment Required';
		break;
	case 403:
		echo 'Forbidden';
		break;
	case 404:
		echo 'Not Found';
		break;
	case 405:
		echo 'Method Not Allowed';
		break;
	case 406:
		echo 'Not Acceptable';
		break;
	case 407:
		echo 'Proxy Authentication Required';
		break;
	case 408:
		echo 'Request Time-out';
		break;
	case 409:
		echo 'Conflict';
		break;
	case 410:
		echo 'Gone';
		break;
	case 411:
		echo 'Length Required';
		break;
	case 412:
		echo 'Precondition Failed';
		break;
	case 413:
		echo 'Request Entity Too Large';
		break;
	case 414:
		echo 'Request-URI Too Large';
		break;
	case 415:
		echo 'Unsupported Media Type';
		break;
	default:
	case 500:
		echo 'Internal Server Error';
		break;
	case 501:
		echo 'Not Implemented';
		break;
	case 502:
		echo 'Bad Gateway';
		break;
	case 503:
		echo 'Service Unavailable';
		break;
	case 504:
		echo 'Gateway Time-out';
		break;
	case 505:
		echo 'HTTP Version not supported';
		break;
}
