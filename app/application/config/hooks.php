<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$hook['post_controller_constructor'] = array(
	'class'    => 'Inicio',
	'function' => 'validar_sesion',
	'filename' => 'Inicio.php',
	'filepath' => 'hooks',
	'params'   => array()
);
