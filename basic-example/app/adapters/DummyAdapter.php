<?php

namespace app\adapters;

use cricket\contrib\auth\adapters\Adapter;
use cricket\contrib\auth\User;

class DummyUser implements User {
	protected $username;
	
	public function __construct($inUsername = null) {
		$this->username = $inUsername;
	}
	/** @return string */
	public function getUsername() { 
		return $this->username;
	}

	/** @return string */
	public function getFullName() {}

	/** @return boolean */
	public function isAnonymous() {}

	/** @return boolean */
	public function isActive() {}

	/** @return boolean */
	public function isAuthenticated() {}

	/** @return boolean */
	public function setPassword($inNewPassword) {}
}

class DummyAdapter extends Adapter {
	
	protected function authenticateUser($inUsername, $inPassword) {
		return array(new DummyUser($inUsername), '');
	}
	
	protected function validateCookie($inCookie) {
		return new DummyUser($inCookie);
	}
	
	public static function getUserCookie(User $inUser) {
		return $inUser->getUsername();
	}
}