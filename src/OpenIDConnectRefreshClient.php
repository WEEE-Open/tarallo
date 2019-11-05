<?php


namespace WEEEOpen\Tarallo;


use Jumbojett\OpenIDConnectClient;

class OpenIDConnectRefreshClient extends OpenIDConnectClient {
	protected $nonce;

	public function setNonceForRefresh($nonce) {
		$this->setNonce($nonce);
	}

	protected function setNonce($nonce) {
		$this->nonce = $nonce;
		return $nonce;
	}

	protected function getNonce() {
		return $this->nonce;
	}

	protected function startSession() {
		error_log('startSession called, override mode methods to prevent this');
	}
}