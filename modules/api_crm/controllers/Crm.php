<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Crm extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	public function data_get(){

		$l_sources = $this->db->get(db_prefix() . 'leads_sources')->result_array();
		$l_status = $this->db->get(db_prefix() . 'leads_status')->result_array();
		$t_priorities =$this->db->get(db_prefix() . 'tickets_priorities')->result_array();
		$departments =$this->db->get(db_prefix() . 'departments')->result_array();
		$groups = $this->db->get(db_prefix() . 'customers_groups')->result_array();
		$currencies = $this->db->get(db_prefix() . 'currencies')->result_array();
		$countries = $this->db->get(db_prefix() . 'countries')->result_array();

		$this->load->model('payment_modes_model');
		$groups_datas = array();
		$leads_sources = array();
		$leads_status = array();
		$tickets_priorities = array();
		$currencies_datas = array();
		$countries_datas = array();
		foreach( $groups as $vl ){
			$groups_datas[ $vl["id"] ]= $vl["name"];
		}
		foreach( $l_sources as $vl ){
			$leads_sources[ $vl["id"] ]= $vl["name"];
		}
		foreach( $l_status as $vl ){
			$leads_status[ $vl["id"] ]= $vl["name"];
		}
		$payment_modes = $this->db->get(db_prefix() . 'payment_modes')->result_array();
		$allowedModes = array();
        foreach( $payment_modes as $mode ){
        	$allowedModes[ $mode["id"] ] =$mode["name"];
        }
        foreach( $t_priorities as $vl ){
			$tickets_priorities[ $vl["priorityid"] ]= $vl["name"];
		}
		$departments_datas = array();
		 foreach( $departments as $vl ){
			$departments_datas[ $vl["departmentid"] ]= $vl["name"];
		}
  		foreach( $currencies as $vl ){
			$currencies_datas[ $vl["id"] ]= $vl["name"];
		}
		foreach( $countries as $vl ){
			$countries_datas[ $vl["country_id"] ]= $vl["short_name"];
		}
		$languages = $this->app->get_available_languages();
		$languages_datas = array();
		foreach( $languages as $language ){
			$languages_datas[$language] = $language;
		}
		$permissions = array("1"=>"Invoices","2"=>"Estimates","3"=>"Contracts","4"=>"Proposals","5"=>"Support","6"=>"Projects");
		$invoice_emails = array_merge($permissions,array("task_emails"=>"Task"));
		$datas["clients"] = array("title"=>"Customer","datas"=>array(
			"company" => array("type"=>"text","description"=>"Customer company","required"=>true),
			"vat" => array("type"=>"text","description"=>"VAT Number"),
			"phonenumber" => array("type"=>"text","description"=>"Phone Number"),
			"website" => array("type"=>"text","description"=>"Website"),
			"address" => array("type"=>"text","description"=>"Address"),
			"city" => array("type"=>"text","description"=>"Customer city"),
			"state" => array("type"=>"text","description"=>"State"),
			"zip" => array("type"=>"text","description"=>"Zip Code"),
			"country" => array("type"=>"select","description"=>"Country","datas"=>$countries_datas),
			"groups_in" => array("type"=>"checkbox_array","description"=>"Customer groups", "datas"=>$groups_datas),
			"default_currency" => array("type"=>"select","description"=>"Default currency", "datas"=>$currencies_datas),
			"default_language" => array("type"=>"select","description"=>"Default Language","datas"=>$languages_datas),
		));
		$datas["contacts"] = array("title"=>"Contacts","datas"=>array(
			"customer_id" => array("type"=>"number","description"=>"Customer id","required"=>true),
			"firstname" => array("type"=>"text","description"=>"First Name","required"=>true),
			"lastname" => array("type"=>"text","description"=>"Last Name","required"=>true),
			"email" => array("type"=>"text","description"=>"E-mail","required"=>true),
			"title" => array("type"=>"text","description"=>"Position"),
			"phonenumber" => array("type"=>"text","description"=>"Phone Number"),
			"is_primary" => array("type"=>"checkbox","description"=>"Primary Contact","default"=>"on"),
			"permissions" => array("type"=>"checkbox_array","description"=>"Make sure to set appropriate permissions for this contact","datas"=>$permissions),
			
			"invoice_emails " => array("type"=>"checkbox","description"=>"Notification for Invoices"),
			"estimate_emails " => array("type"=>"checkbox","description"=>"Notification for Estimate"),
			"credit_note_emails " => array("type"=>"checkbox","description"=>"Notification for Credit Note"),
			"project_emails " => array("type"=>"checkbox","description"=>"Notification for Project"),
			"ticket_emails " => array("type"=>"checkbox","description"=>"Notification for Tickets"),
			"task_emails " => array("type"=>"checkbox","description"=>"Notification for Task"),
			"contract_emails " => array("type"=>"checkbox","description"=>"Notification for Contract"),
		));
		$datas["staffs"] = array("title"=>"Staffs","datas"=>array(
			"email" => array("type"=>"text","description"=>"Email","required"=>true),
			"password" => array("type"=>"text","description"=>"New Password","required"=>true),
			"firstname" => array("type"=>"text","description"=>"First Name","required"=>true),
			"lastname" => array("type"=>"text","description"=>"Last Name","required"=>true),
			"hourly_rate" => array("type"=>"text","description"=>"Hourly Rate"),
			"phonenumber" => array("type"=>"text","description"=>"Phone Number"),
			"facebook" => array("type"=>"text","description"=>"Staff facebook"),
			"linkedin" => array("type"=>"text","description"=>"Staff linkedin"),
			"skype" => array("type"=>"text","description"=>"Staff skype"),
			"default_language" => array("type"=>"text","description"=>"Staff default language"),
			"email_signature" => array("type"=>"text","description"=>"Staff email signature"),
			"direction" => array("type"=>"select","description"=>"Staff Direction","datas"=>array("ltr"=>"LTR","rtl"=>"RTL")),
			"departments" => array("type"=>"select","description"=>"Staff departments","datas"=>$departments_datas),
			"send_welcome_email" => array("type"=>"checkbox","description"=>"Staff departments"),
		));
		$datas["leads"] = array("title"=>"Leads","datas"=>array(
			"source" => array("type"=>"select","description"=>"Lead source","required"=>true,"datas"=>$leads_sources),
			"status" => array("type"=>"select","description"=>"Lead Status","required"=>true,"datas"=>$leads_status),
			"name" => array("type"=>"text","description"=>"Lead name","required"=>true),
			"assigned" => array("type"=>"text","description"=>"Lead assigned ID"),
			"client_id" => array("type"=>"text","description"=>"Lead From Customer ID"),
			"tags" => array("type"=>"text","description"=>"Lead tags"),
			"contact" => array("type"=>"text","description"=>"Lead contact"),
			"title" => array("type"=>"text","description"=>"Lead Position"),
			"email" => array("type"=>"text","description"=>"Lead email"),
			"website" => array("type"=>"text","description"=>"Lead website"),
			"phonenumber" => array("type"=>"text","description"=>"Lead Phone"),
			"company" => array("type"=>"text","description"=>"Lead company"),
			"address" => array("type"=>"text","description"=>"Lead address"),
			"city" => array("type"=>"text","description"=>"Lead city"),
			"state" => array("type"=>"text","description"=>"Lead state"),
			"zip" => array("type"=>"text","description"=>"Lead Zip Code"),
			"country" => array("type"=>"select","description"=>"Country","datas"=>$countries_datas),
			"default_language" => array("type"=>"select","description"=>"Default Language","datas"=>$languages_datas),
			"description" => array("type"=>"text","description"=>"Lead description"),
		));
		$datas_item = array(
			"description" => array("type"=>"text","description"=>"item description","required"=>true),
			"long_description" => array("type"=>"text","description"=>"item long_description","required"=>true),
			"qty" => array("type"=>"text","description"=>"item Qty","required"=>true),
			"rate" => array("type"=>"text","description"=>"item rate","required"=>true),
			"order" => array("type"=>"text","description"=>"item order","required"=>true),
		);
		$datas["invoices"] = array("title"=>"Invoices","datas"=>array(
			"clientid" => array("type"=>"text","description"=>"Customer id","required"=>true),
			"number" => array("type"=>"text","description"=>"Invoice Number","required"=>true),
			"date" => array("type"=>"text","description"=>"Invoice Date ( YYY-mm-dd)","required"=>true),
			"duedate" => array("type"=>"text","description"=>"Invoice Date ( YYY-mm-dd)"),
			"cancel_overdue_reminders" => array("type"=>"checkbox","description"=>"Prevent sending overdue remainders for invoice"),
			"tags" => array("type"=>"text","description"=>"Invoice tags"),
			"sale_agent" => array("type"=>"text","description"=>"Sale Agent ID"),
			"recurring" => array("type"=>"text","description"=>"recurring 1 to 12 or custom"),
			"discount_type" => array("type"=>"select","description"=>"Discount Type","datas"=>array("before_tax"=>"Before tax","after_tax"=>"After Tax")),
			"adminnote" => array("type"=>"text","description"=>"Admin note"),
			"currency" => array("type"=>"text","description"=>"Invoice currency","required"=>true),
			"subtotal" => array("type"=>"text","description"=>"calculation based on item Qty, Rate and Tax","required"=>true),
			"total" => array("type"=>"text","description"=>"calculation based on subtotal, Discount and Adjustment","required"=>true),
			"billing_street" => array("type"=>"text","description"=>"Street Address","required"=>true),
			"allowed_payment_modes" => array("type"=>"checkbox_array","description"=>"Payment modes","required"=>true,"datas"=>$allowedModes),
			"newitems" => array("type"=>"item","description"=>"New item","required"=>true,"datas"=>$datas_item),
		));
		$datas["tasks"] = array("title"=>"Tasks","datas"=>array(
			"is_public" => array("type"=>"checkbox","description"=>"Task public"),
			"billable" => array("type"=>"checkbox","description"=>"Task billable"),
			"name" => array("type"=>"text","description"=>"Task name","required"=>true),
			"hourly_rate" => array("type"=>"text","description"=>"Hourly Rate"),
			"startdate" => array("type"=>"text","description"=>"Invoice Date ( YYY-mm-dd)","required"=>true),
			"duedate" => array("type"=>"text","description"=>"Invoice Date ( YYY-mm-dd)"),
			"priority" => array("type"=>"select","description"=>"Priority","datas"=>array("1"=>"Low","2"=>"Medium","3"=>"High","4"=>"Urgent")),
			"repeat_every" => array("type"=>"text","description"=>"Repeat every ( E.g: 1-week = 1 Week, 2-week = 2 Weeks, 1-year = 1 year,.. )"),
			"rel_type" => array("type"=>"select","description"=>"Related To","datas"=>array("lead"=>"lead","customer"=>"customer","invoice"=>"invoice","project"=>"project","quotation"=>"quotation","contract"=>"contract","annex"=>"annex","ticket"=>"ticket","expense"=>"expense","proposal"=>"proposal")),
			"rel_id" => array("type"=>"text","description"=>"Related ID"),
			"tags" => array("type"=>"text","description"=>"Tags"),
			"description" => array("type"=>"text","description"=>"Task Description"),

		));
		$datas["tickets"] = array("title"=>"Tickets","datas"=>array(
			"subject" => array("type"=>"text","description"=>"Subject","required"=>true),
			"contactid" => array("type"=>"text","description"=>"Contact ID","required"=>true),
			"department" => array("type"=>"select","description"=>"Department ID","required"=>true,"datas"=>$departments_datas),
			"cc" => array("type"=>"text","description"=>"CC"),
			"tags" => array("type"=>"text","description"=>"Tags"),
			"userid" => array("type"=>"text","description"=>"Assign ticket (user ID)","required"=>true),
			"priority" => array("type"=>"select","description"=>"Priority","datas"=>$tickets_priorities),
			"service" => array("type"=>"text","description"=>"Service ID"),
			"message" => array("type"=>"text","description"=>"Message"),
		));
		echo json_encode( array("status"=>true,"message"=>$datas) );
	}
	

   
}
