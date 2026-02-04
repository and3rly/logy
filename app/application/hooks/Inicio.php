<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inicio
{
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->permitidas = [
			"sesion"
		];
	}

	public function validar_sesion()
	{
		$tmp = explode("/", $_SERVER["REQUEST_URI"]);

		if (isset($tmp[3]) && in_array($tmp[3], $this->permitidas)) {
			return;
		} else {
			$headers = $this->ci->input->request_headers();

			if (isset($headers["authorization"])) {
				$token = str_replace("Bearer ", "", $headers["authorization"]);
				$this->ci->load->library("token");

				$tk = new Token();
				$datos = $tk->validar_token($token);

				if ($datos) {
					$this->ci->session->set_userdata([
						"usuario" => var_session($datos->data)
					]);
				} else {
					http_response_code(401);
					outputJson(["mensaje" => "Token invalido"]);
					die;
				}
			} else {
				http_response_code(401);
	            outputJson(["mensaje" => "Acceso denegado"]);
	            die;
			}
		}
	}
}

/* End of file Inicio.php */
/* Location: ./application/hooks/Inicio.php */