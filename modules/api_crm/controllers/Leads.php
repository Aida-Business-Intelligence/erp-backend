<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Leads extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	
	public function submit_data_post($datas){
		$check_update = false;
		if( isset($datas["enable_update"])) {
			unset($datas["enable_update"]);
			$check_update = true;
			
		}
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('name', 'Lead Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Lead Name'));
        $this->form_validation->set_rules('source', 'Source', 'trim|required', array('is_unique' => 'This %s already exists please enter another Lead source'));
        $this->form_validation->set_rules('status', 'Status', 'trim|required', array('is_unique' => 'This %s already exists please enter another Status'));
	    
        if( $check_update ) { 
			if( isset($datas["email"]) && $datas["email"] != "" ) {

				if( isset($datas["client_id"]) && $datas["client_id"] != "" ) { 
					$_current_id = $this->db->where('email',$datas["email"],"client_id",$datas["client_id"])->get(db_prefix() . 'leads')->row();
					$_current_id = $_current_id->id;
				}else{
					$_current_id = $this->db->where('email',$datas["email"])->get(db_prefix() . 'leads')->row();
					$_current_id = $_current_id->id;
				}
			}
			if( isset($_current_id) && $_current_id > 0 ) { 
				//done
			}else{
				if( isset($datas["phonenumber"]) && $datas["phonenumber"] != "" ) {
					if( isset($datas["client_id"]) && $datas["client_id"] != "" ) { 
						$_current_id = $this->db->where('phonenumber',$datas["phonenumber"],"client_id",$datas["client_id"])->get(db_prefix() . 'leads')->row();
						$_current_id = $_current_id->id;
					}else{
						$_current_id = $this->db->where('phonenumber',$datas["phonenumber"])->get(db_prefix() . 'leads')->row();
						$_current_id = $_current_id->id;
					}
				}
			}
			
		}


		if ($this->form_validation->run() == false){
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}else{

			$this->load->model('leads_model');
			$customfields = array();
			foreach( $datas as $k=>$v ){
				$pos = strpos($k, "customfields");
				if ($pos !== false) {
					$ids = explode("_",$k);
					$customfields[ "". $ids[1] .""] = $v;
					unset($datas[$k]);
				}
			}

			$datas["custom_fields"] = array("leads"=>$customfields);
			if( isset($_current_id) && $_current_id > 0 ) { 
				$id      = $this->leads_model->update($datas, $_current_id);
				$new_message = 'Lead updated successfully.';
			}else {
				$id = $this->leads_model->add($datas);
				$new_message = 'Lead added successfully.';
			}
           

			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => $new_message
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'lead add fail.'
				);
				echo json_encode( $message );
			}
		}
	}

   
}
