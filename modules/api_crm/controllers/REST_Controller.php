<?php
defined('BASEPATH') OR exit('No direct script access allowed');
abstract class REST_Controller extends CI_Controller {
    public function __construct($config = 'rest'){
        
       
        
        parent::__construct();
        $this->CI =& get_instance();
        $this->load->library('form_validation');
        $this->load->model('Api_crm_model');
        $check = $this->check_token();
      
        if( !$check ) {
            echo json_encode( array('status' => FALSE, 'message' => 'Token Time Not Define'));
            die();
        }
        $method = $this->input->method();
    
        switch( $method ){
            case "get":
                $this->data_get();
                break;
            case "put":
                $this->data_post();
                break;
            default:
                 echo json_encode( array('status' => FALSE, 'message' => 'Method Not Supported'));
                die();
        }
    }
    public function _remap($object_called, $arguments = []){
    }
    public function data_get(){
    }
    public function data_post(){
        $data_json ="";
        $putfp = fopen('php://input', 'r');
        $data_json = '';
        while($data = fread($putfp, 1024)){
            $data_json = $data;
            break;
        }
        fclose($putfp); 
        $data_json = json_decode($data_json,true);
        if( $data_json ){
            $this->submit_data_post($data_json);
        }else{
             echo json_encode( array('status' => FALSE, 'message' => 'Datas Json not Format'));
            die();
        }
    }
    public function submit_data_post($datas){ 
    }
    public function get_headers( $key = ""){
        $headers = $this->CI->input->request_headers();
        if( $key != ""){
            if( isset($headers[ $key ])){
                return $headers[ $key ];
            }else{
                return false;
            }
        }else{
            return $headers;
        } 
    }
    public function check_token(){
        $token = $this->get_headers("token");
        if( !$token ){
           $token = $this->get_headers("Token"); 
        }
        if( $token ){
            $user = $this->Api_crm_model->get_user_token($token);
            if( isset($user[0]["id"])){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }  
}
