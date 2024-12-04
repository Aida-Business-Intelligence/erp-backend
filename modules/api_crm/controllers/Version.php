<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Version extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	public function data_get(){
		echo json_encode( array("status"=>true,"message"=>"2.0.0") );
	}
}
