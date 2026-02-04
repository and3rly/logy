<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
	protected $ci;
	protected $key = "logy_app";
	protected $encrypt = "HS256";
	protected $time;

	public function __construct()
	{
        $this->ci =& get_instance();
        $this->time = time();
	}

	public function set_token($data=[])
	{
		$data = [
			"iat"  => $this->time,
			"exp"  => $this->time + (60 * 60),
			"data" => $data
		];

		return JWT::encode($data, $this->key, $this->encrypt);
	}

	public function validar_token($token)
	{
		if (!is_string($token) || empty($token)) {
			return false;
		}

		try {
			return JWT::decode($toke, $this->key, $this->encrypt);
		} catch (Exception $e) {
			return false;
		}
	}
}

/* End of file Token.php */
/* Location: ./application/libraries/Token.php */