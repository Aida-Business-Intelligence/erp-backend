<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Tasks extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	
	public function submit_data_post($datas){
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('name', 'Task Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Task Name'));
        $this->form_validation->set_rules('startdate', 'Task Start Date', 'trim|required', array('is_unique' => 'This %s already exists please enter another Task Start Date'));
        $this->form_validation->set_rules('is_public', 'Publicly available task', 'trim', array('is_unique' => 'Public state can be 1. Skip it completely to set it at non-public'));
	    
		if ($this->form_validation->run() == false){
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}else{
			
			$this->load->model('tasks_model');
			 $customfields = array();
			foreach( $datas as $k=>$v ){
				$pos = strpos($k, "customfields");
				if ($pos !== false) {
					$ids = explode("_",$k);
					$customfields[ "". $ids[1] .""] = $v;
					unset($datas[$k]);
				}
			}
			$datas["custom_fields"] = array("tasks"=>$customfields);
            $id = $this->tasks_model->add($datas);
		
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => 'task added successfully.'
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
