<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Tickets extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	
	public function submit_data_post($datas){
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('subject', 'Ticket Name', 'trim|required', array('is_unique' => 'This %s already exists please enter another Ticket Name'));
        $this->form_validation->set_rules('department', 'Department', 'trim|required', array('is_unique' => 'This %s already exists please enter another Ticket Department'));
        $this->form_validation->set_rules('contactid', 'Contact', 'trim|required', array('is_unique' => 'This %s already exists please enter another Ticket Contact'));
      	
      	
		if ($this->form_validation->run() == false){
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}else{
			 $this->load->model('tickets_model');
			 $customfields = array();
			foreach( $datas as $k=>$v ){
				$pos = strpos($k, "customfields");
				if ($pos !== false) {
					$ids = explode("_",$k);
					$customfields[ "". $ids[1] .""] = $v;
					unset($datas[$k]);
				}
			}
			$datas["custom_fields"] = array("tickets"=>$customfields);
            $id = $this->tickets_model->add($datas);
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => 'Ticket added successfully.'
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'Ticket add fail.'
				);
				echo json_encode( $message );
			}
		}
	}

   
}
