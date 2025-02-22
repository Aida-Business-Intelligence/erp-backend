<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Invoices extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	
	public function submit_data_post($datas){
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('clientid', 'Customer', 'trim|required|max_length[255]');
      	$this->form_validation->set_rules('number', 'Invoice number', 'trim|required|max_length[255]');
      	$this->form_validation->set_rules('date', 'Invoice date', 'trim|required|max_length[255]');
      	$this->form_validation->set_rules('currency', 'Currency', 'trim|required|max_length[255]');
      	$this->form_validation->set_rules('newitems[]', 'Items', 'required');
      	$this->form_validation->set_rules('allowed_payment_modes[]', 'Allow Payment Mode', 'trim|required|max_length[255]');
      	$this->form_validation->set_rules('billing_street', 'Billing Street', 'trim|required|max_length[255]');
      	$this->form_validation->set_rules('subtotal', 'Sub Total', 'trim|required|decimal|greater_than[0]');
      	$this->form_validation->set_rules('total', 'Total', 'trim|required|decimal|greater_than[0]');
      	
      	$allowed_payment_modes = array();
      	foreach( $datas["allowed_payment_modes"] as $mode ){
      		$allowed_payment_modes[] = $mode[0];
      	}
      	
      	unset($datas["allowed_payment_modes"]);

      	$datas["allowed_payment_modes"] = $allowed_payment_modes;
      	
		if ($this->form_validation->run() == false){
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}else{
			$this->load->model('invoices_model');
			$customfields = array();
			foreach( $datas as $k=>$v ){
				$pos = strpos($k, "customfields");
				if ($pos !== false) {
					$ids = explode("_",$k);
					$customfields[ "". $ids[1] .""] = $v;
					unset($datas[$k]);
				}
			}
			$datas["custom_fields"] = array("invoice"=>$customfields);
            $id = $this->invoices_model->add($datas);
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => 'Invoices added successfully.'
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'Invoices add fail.'
				);
				echo json_encode( $message );
			}
		}
	}

   
}
