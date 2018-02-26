<?php

namespace WEEEOpen\Tarallo\Server\HTTP;


class RedirectResponse extends Response {
	public $target;

	/**
	 * RedirectResponse constructor.
	 *
	 * @param int $code 303, mostly
	 * @param string $target URL. Remember to rawurlencode the relevant parts!
	 */
	public function __construct(int $code, string $target) {
		if($code >= 300 && $code < 400) {
			parent::__construct($code, null, null);
			$this->target = $target;
		} else {
			throw new \InvalidArgumentException("HTTP status $code is not a redirect");
		}
	}

	public function send() {
		// Relative URLs are allowed since RFC 7231 (2014), but apparently have worked
		// for a loooong time even if the previous RFC didn't allow them. Good to know.
		http_response_code($this->code);
		header('Location: ' . $this->target);
	}
}
