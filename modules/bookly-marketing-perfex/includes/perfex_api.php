<?php
class Rednumber_Marketing_CRM_Perfex_API {
	private $add_on ="perfex";
	public $api_url='';
	public $api_key = "";
	private $attrs_contact = array();
	private $attrs = array();
	public static $list_tabs = array("clients","contacts","leads","invoices","tasks","tickets","staffs");
	function __construct($admin = false){
		$options = get_option("crm_marketing_".$this->add_on,array("api"=>"","url"));
		if( $options["api"] != "" ){
			$this->api_url = $options["url"];
			$this->api_key = $options["api"];
			if( $admin ){
				$this->attrs = $this->get_attrs();
			}
		}
	}
	function get_account_info(){
		$url = $this->return_url("accounts");
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response;
	}
	function get_version(){
		$url = $this->return_url("version");
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response;
	}
	function get_custom_fields(){
		$datas = array();
		$url = $this->return_url("Customfields");
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response["message"];
	}
	function get_attrs(){
		$datas = array();
		$properties = $this->get_properties("crm");
		$custom_fields = $this->get_custom_fields();
		if( !is_array($custom_fields)){
			$custom_fields = array();
		}
		foreach( $properties as $k => $property ){ 
			$datas[$k]["title"] = $property["title"];
			$datas[$k]["datas"] = array();
			foreach( $property["datas"] as $name=> $value ){
				$select_options = array();
				switch( $value["type"] ){
					case "select":
						$type = "select";
						$select_options = $value["datas"];
						break;
					case "item":
						$i=0;
						$datas[$k]["datas"][] = array("name"=>$name,"label"=>$name,"type"=>"hr","des"=>$value["description"],"required"=>$required,"select_options"=>$select_options);
						foreach( $value["datas"] as $n=>$v ){
							$required = false;
							if( isset($value["required"]) ){
								$required = true;
							}
							$datas[ $k ]["datas"][] = array("name"=>$n,"label"=>$n,"type"=>"text","des"=>$v["description"],"required"=>$required,"select_options"=>$select_options);
							$i++;
						}
						continue 2;
						break;
					case "checkbox_array":
						$type = "checkbox_array";
						$select_options = $value["datas"];
						break;
					case "checkbox":
						$type = "checkbox";
						break;
					case "hr":
						$datas[$k]["datas"][] = array("name"=>$name,"label"=>$name,"type"=>"hr","des"=>$value["description"],"required"=>$required,"select_options"=>$select_options);
						break;
					default:
						$type = "text";
						break;
				}
				if( $name == "message" ){
				$type = "textarea";
			}
				$required = false;
				if( isset($value["required"]) ){
					$required = true;
				}
				$datas[$k]["datas"][] = array("name"=>$name,"label"=>$name,"type"=>$type,"des"=>$value["description"],"required"=>$required,"select_options"=>$select_options);
			}
			//custom fields
			
			foreach( $custom_fields as $field ){
				$type = $field["fieldto"];	
				if( $type == "customers") {
					$type = "clients";
				}
				if( $type == "staff") {
					$type = "staffs";
				}
				if( $type == "invoice") {
					$type = "invoices";
				}
				if( $type == $k  ) {
					$datas[$k]["datas"][] = array("name"=>"customfields_".$field["id"],"label"=>$field["name"],"type"=>"text","des"=>$field["name"],"required"=>false,"select_options"=>array());
				}
			}
		}
		return $datas;
	}
	function get_all_attributes_contact(){
		$datas = array();
		$properties = $this->get_properties("contacts");
		foreach( $properties as $name => $property ){
			switch( $property["type"] ){
				default:
					$type = "text";
					break;
			}
			$required = false;
			if( isset($property["required"]) ){
				$required = true;
			}
			$select_options = array();
			$datas[] = array("name"=>$name,"label"=>$name,"type"=>$type,"des"=>$property["description"],"required"=>$required);
		}
		return $datas;
	}
	function get_properties($type){
		$url = $this->return_url($type);
		$response = $this->get($url);
		$response = json_decode($response,true);
		if( $response["status"] ){
			return $response["message"];
		}else{
			return array();
		}
	}
	function add_submit($key,$data){
		$url = $this->return_url($key);
		$response = $this->post($url,$data);
		return $response;
	}
	function add_contact($data){
		$url = $this->return_url("contacts");
		$response = $this->post($url,$data);
		return $response;
	}
	public function get_data($key){
		return $this->$key;
	}
	function post($url, $data ){
		$post_json = json_encode($data);
		$api_key = $this->api_key;

		$response = wp_remote_request( $url, array(
	    'body'    => $post_json,
	    'method'    => 'PUT',
	    'timeout'=>45,
		    'headers' => array(
		        'Content-Type' => 'application/json',
		        'token' => $api_key
		    ),
		) );
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function get($url){
		$api_key = $this->api_key;
		$response = wp_remote_get( $url, 
			 array(
			      'headers' => array(
			        'Content-Type' => 'application/json',
			        'token' => $api_key
			      )
			  )
			);
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function return_url($url){
		return $this->api_url."/api_crm/". $url;
	}
}