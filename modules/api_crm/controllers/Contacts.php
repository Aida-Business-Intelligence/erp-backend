<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Contacts extends REST_Controller {
	function __construct(){
		parent::__construct();
	}
	public function submit_data_post($datas){
		$check_update = false;
		if( isset($datas["enable_update"])) {
			unset($datas["enable_update"]);
			$check_update = true;
			
		}
		$datas["send_set_password_email"] = "on";
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('firstname', 'First Name', 'trim|required|max_length[255]');
		$this->form_validation->set_rules('lastname', 'Last Name', 'trim|required|max_length[255]');
		$this->form_validation->set_rules('customer_id', 'Customer Id', 'trim|required|numeric|callback_client_id_check');
		if( $check_update ) {
			$this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[255]');
			$_current_id = $this->db->where('email',$datas["email"],'customer_id',$datas['customer_id'])->get(db_prefix() . 'contacts')->row();
			$_current_id = $_current_id->id;
		}else{
			$this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[255]|is_unique['.db_prefix().'contacts.email]',array('is_unique' => 'This %s is already exists'));
		}
		
	   
		if ($this->form_validation->run() == false)
		{
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}
		else{
			$customer_id = $datas['customer_id'];
			unset($datas['customer_id']);
			$this->load->model('authentication_model');	
			$customfields = array();
			foreach( $datas as $k=>$v ){
				$pos = strpos($k, "customfields");
				if ($pos !== false) {
					$ids = explode("_",$k);
					$customfields[ "". $ids[1] .""] = $v;
					unset($datas[$k]);
				}
			}
			$datas["custom_fields"] = array("contacts"=>$customfields);
			if( isset($_current_id) && $_current_id > 0 ) { 
				$id      = $this->clients_model->update_contact($datas, $_current_id);
				$new_message = 'Contact updated successfully.';
			}else {
				$id      = $this->clients_model->add_contact($datas, $customer_id,true);
				$new_message = 'Contact added successfully.';
			}
			
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => $new_message,
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'Contact add/update fail.'
				);
				echo json_encode( $message );
			}
		}
	}

   public function client_id_check($customer_id){
        $this->form_validation->set_message('client_id_check', 'The {field} is Invalid');
        if (empty($customer_id)) {
            return FALSE;
        }
		$query = $this->db->get_where(db_prefix().'clients', array('userid' => $customer_id));
		return $query->num_rows() > 0;
	}
}
