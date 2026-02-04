<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sesion extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["Usuario_model"]);
		$this->load->library(["token"]);
		$this->output->set_content_type('application/json');
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function login()
	{
		$data = ["exito" => 0];
		
		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "alias") && 
				verPropiedad($datos, "clave")) {
				
				$us = new Usuario_model();

				if ($us->iniciarSesion($datos)) {
					$us->id = $us->getPK();

					$usuario = var_session($us);
					$this->session->set_userdata([
						"usuario" => $usuario
					]);
					
					$tk = new Token();
					$token = $tk->set_token($usuario);

					$data["usuario"] = $usuario;
					$data["mensaje"] = "Bienvenido a Logy";
					$data["token"]   = $token;
					$data["exito"]   = 1;
				} else {
					$data["mensaje"] = "Usuario o clave incorrecta, intente de nuevo.";
				}

			} else {
				$data["mensaje"] = "Ingrese sus credenciales";
			}

		} else {
			$this->output->set_status_header("405");
		}

		$this->output->set_output(json_encode($data));
	}

	public function cerrar_sesion()
	{
		$data = ["exito" => 0];

		$this->session->sess_destroy();
		$data["exito"] = 1;
		$data["mensaje"] = "Sesión finalizada con éxito.";

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Sesion.php */
/* Location: ./application/controllers/Sesion.php */ ?>