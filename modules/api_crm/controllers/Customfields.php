<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Customfields extends REST_Controller {
    function __construct(){
        parent::__construct();  
    }
    public function data_get(){
        $token = $this->get_headers("token");
     if( !$token ){
        $token = $this->get_headers("Token"); 
     }
        $user = $this->Api_crm_model->get_custom_fields();
         if( isset($user[0]["id"])){
                echo json_encode( array("status"=>true,"message"=>$user) );
            }else{
               echo json_encode( array("status"=>false,"message"=>"No result") );
            }
    }
}
