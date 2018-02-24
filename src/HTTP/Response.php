<?php
namespace WEEEOpen\Tarallo\Server\HTTP;


class Response {
	public $code;
	public $type;
	public $content;

	public function __construct(int $code, string $type = null, string $content = null) {
		$this->code = $code;
		$this->type = $type;
		$this->content = $content;
	}

	public function send() {
		http_response_code($this->code);

		if($this->content !== null) {
			if(!is_string($this->type)) {
				throw new \LogicException('Got content to send but no content type');
			}
			header('Content-Type: ' . $this->type);
			echo $this->content;
		}
	}
}
