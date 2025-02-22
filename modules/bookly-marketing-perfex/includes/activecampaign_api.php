<?php
class Rednumber_Marketing_Activecampaign_API {
 	public $api_url='';
	public $api_key = "";
	public $domain = "";
	private static $add_on ="activecampaign";
	private $attrs_contact = array();
	private $attrs_deal = array();
	private $attrs_task = array();
	function __construct( $admin = false){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>"","url"=>""));
		$this->api_key = $options["api"];
		$this->api_url = $options["url"]."/api/3/";
		if( $admin ){
			$this->attrs_contact = $this->get_all_attributes_contact();
			$this->attrs_deal = $this->get_all_attributes_deal();
			$this->attrs_task = $this->get_all_attributes_task();
		}
	}
	public function get_data($key){
		return $this->$key;
	}
	function get_account(){
		$url = $this->return_url('accounts');
		$response = $this->get($url);
		$response=json_decode($response,true);
		return $response;
	}
	function get_all_attributes_contact(){
		$datas = array();
		$select_options = array();
		$datas[] = array("name"=>"email","label"=>"email","type"=>"text","required"=>true,"des"=>"Email address of the new contact");
		$datas[] = array("name"=>"firstName","label"=>"firstName","type"=>"text","des"=>"First name of the new contact.");
		$datas[] = array("name"=>"lastName","label"=>"lastName","type"=>"text","des"=>"Last name of the new contact.");
		$datas[] = array("name"=>"phone","label"=>"phone","type"=>"text","des"=>"Phone of the new contact.");
		$custom_fields = $this->get_contact_custom_field();
		foreach( $custom_fields as $field){
			$datas[] = array("name"=>$field["perstag"],"label"=>$field["title"],"type"=>"text");  
		}
		return $datas;
	}
	function get_all_attributes_deal(){
		$datas = array();
		$select_options_stage = array();
		foreach( $this->get_all_deal_stages() as $stage ){
			$select_options_stage[ $stage["id"] ] = $stage["title"];
		}
		$select_options_status = array("0"=>"Open","1"=>"Won","2"=>"Lost");
		$datas[] = array("name"=>"title","label"=>"title","type"=>"text","required"=>true,"des"=>"The title of the deal");
		$datas[] = array("name"=>"description","label"=>"description","type"=>"text","des"=>"Deal's description.");
		$datas[] = array("name"=>"contact","label"=>"contact","type"=>"text","des"=>"Deal's primary contact's id.");
		$datas[] = array("name"=>"account","label"=>"account","type"=>"text","des"=>"Deal’s account id");
		$datas[] = array("name"=>"currency","label"=>"currency","type"=>"text","des"=>"Deal's currency in 3-digit ISO format, lowercased.","required"=>true);
		$datas[] = array("name"=>"value","label"=>"value","type"=>"text","des"=>"Deal's value in cents. (i.e. $456.78 => 45678). Must be greater than or equal to zero.");
		$datas[] = array("name"=>"group","label"=>"group","type"=>"text","des"=>"Deal's pipeline id. Required if deal.stage is not provided. If deal.group is not provided, the stage's pipeline will be assigned to the deal automatically.");
		$datas[] = array("name"=>"stage","label"=>"stage","type"=>"select","des"=>"Deal's stage id. Required if deal.group is not provided. If deal.stage is not provided, the deal will be assigned with the first stage in the pipeline provided in deal.group.","select_options"=>$select_options_stage);
		$datas[] = array("name"=>"owner","label"=>"owner","type"=>"text","des"=>"Deal's owner id. Required if pipeline's auto-assign option is disabled.");
		$datas[] = array("name"=>"percent","label"=>"percent","type"=>"text","des"=>"Deal's percentage.");
		$datas[] = array("name"=>"status","label"=>"status","type"=>"select","des"=>"Deal's status.","select_options"=>$select_options_status);
		$datas[] = array("name"=>"group","label"=>"group","type"=>"text","des"=>"");
		$custom_fields = $this->get_custom_deal_field();
		foreach( $custom_fields["fields"] as $field){
			$datas[] = array("name"=>$field["fieldValues"],"label"=>$field["fieldLabel"],"type"=>"text");  
		}
		return $datas;
	}
	function get_all_attributes_task(){
		$datas = array();
		$select_options = array();
		$datas[] = array("name"=>"title","label"=>"title","type"=>"text","required"=>true,"des"=>"The title to be assigned to the task");
		$datas[] = array("name"=>"OwnerType","label"=>"OwnerType","type"=>"text","des"=>"The name of the relating object. Valid values are contact or deal. (see relationships table)");
		$datas[] = array("name"=>"relid","label"=>"relid","type"=>"text","des"=>"The id of the relational object for this task","required"=>true);
		$datas[] = array("name"=>"Status","label"=>"Status","type"=>"select","des"=>"Task status","select_options"=>array("1"=>"complete","0"=>"incomplete"));
		$datas[] = array("name"=>"note","label"=>"note","type"=>"text","des"=>"The content describing the task");
		$datas[] = array("name"=>"duedate","label"=>"duedate","type"=>"text","des"=>"Due date of the task","required"=>true);
		$datas[] = array("name"=>"dealTasktype","label"=>"dealTasktype","type"=>"text","des"=>"The type of the task based on the available Task Types in the account","required"=>true);
		$datas[] = array("name"=>"assignee","label"=>"assignee","type"=>"text","des"=>"The id of an user the task will be assigned to");
		return $datas;
	}
	function get_contact_custom_field(){
		$url = $this->return_url('fields');
		$response = $this->get($url);
		$response=json_decode($response,true);
		return $response["fields"];
	}
	function get_custom_deal_field(){
		$url = $this->return_url('dealCustomFieldMeta');
		$response = $this->get($url);
		$response=json_decode($response,true);
		return $response["dealCustomFieldMeta"];
	}
	function get_all_deal_stages(){
		$url = $this->return_url('dealStages');
		$response = $this->get($url);
		$response=json_decode($response,true);
		return $response["dealStages"];
	}
	function get_accounts(){
		$url = $this->return_url('accounts');
		$response = $this->get($url);
		return $response;
	}
	function add_contact($data){
		$url = $this->return_url('contacts');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_deal($data){
		$url = $this->return_url('deals');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_task($data){
		$url = $this->return_url('dealTasks');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_list($data){
		$url = $this->return_url('lists');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_note($data){
		$url = $this->return_url('notes');
		$response = $this->post($url,$data);
		return $response;
	}
	function post($url, $data){
		$post_json = json_encode($data);
		$response = wp_remote_post( $url, array(
		    'body'    => $post_json,
		    'timeout'=>45,
		    'headers' => array(
		          'content-type' => 'application/json',
			      'Api-Token' => $this->api_key,
			      'accept' => 'application/json',	
		    ),
		) );
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function get($url){
		$response = wp_remote_get( $url, 
			 array(
			    'timeout'=>45,
			    'headers' => array(
			        'content-type' => 'application/json',
			        'Api-Token' => $this->api_key,
			        'accept' => 'application/json',
			    ),
			  )
			);
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function return_url($url){
		return $this->api_url. $url;
	}
}