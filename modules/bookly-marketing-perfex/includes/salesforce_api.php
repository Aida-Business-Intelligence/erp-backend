<?php
class Rednumber_Marketing_CRM_Salesforce_API {
	private $add_on ="salesforce";
	private $api_url ="https://d5j000008esxueam-dev-ed.my.salesforce.com/";
	private $client_Key = "3MVG9pRzvMkjMb6mu_PpHaORrC2.eV0G6RaEebVR.ldaZab5viThoCNM5XcNJiLMNg7axStq0yrMfXbWuFO7n";
	private $client_secret = "237CDB635F414E326E56EBF739E9AB2463252F5D37DC348E8834C86C3882D079";
	private $call_back ="https://add-ons.org/token/index.php";
	private $token ="";
	private $attrs_contact = array();
	private $attrs_tabs = array();
	function __construct($admin = false){
		$token = get_option("_".$this->add_on."_crm_token");
		if ( $token ){
			$this->token = $token;
			if( $admin ){
				$this->attrs_tabs = $this->wc_get_attribute_types();
				$this->attrs_contact = $this->get_all_attributes_contact();
			}
		}
	}
	function wc_get_attribute_types(){
		$url = $this->return_url("services/data/v55.0/sobjects");
		$response = $this->get($url);
		$response = json_decode($response,true);
		$tabs=array();
		 if(isset($response['sobjects'])){
			  foreach($response['sobjects'] as $object){
				  if($object['createable'] == true && $object['layoutable'] == true){
				  	$tabs[]= array("name"=>$object['name'],"label"=>$object['label'],"urls"=>$object["urls"]);  
				  }    
		  }
		 }
		 var_dump("\n");
		 var_dump(count($tabs));
		return $tabs;
	}
	function get_attr_meta(){
		$url = $this->return_url("services/data/v55.0/sobjects/contact/describe");
		$response = $this->get($url);
		$response = json_decode($response,true);
		if(isset($response['fields']) && is_array($response['fields'])){ 
			foreach($response['fields'] as $k=>$field){  
				if( (isset($field['createable']) && $field['createable'] ==true) || $field['name'] == 'Id' || (isset($field['custom']) && $field['custom'] ==true) ){ 
					$required = false;
					if( !empty($field['nameField']) || (!empty($field['createable']) && empty($field['nillable']) && empty($field['defaultedOnCreate']) )  ){ 
					  $required="true";   
					 } 
					var_dump($field['name']);
					var_dump($field['type']);
				}
			}
		}
		var_dump($response);
	}
	function get_token($code){
		$url = "https://login.salesforce.com/services/oauth2/token";
		$data = array(
						"grant_type"=>"authorization_code",
						"code" => $code,
						"client_id"=>$this->client_Key,
						"client_secret" =>$this->client_secret,
						"redirect_uri" => $this->call_back
					);
		$response = $this->post($url,$data, false);
		$response=json_decode($response,true);
		if( isset($response["access_token"]) ){ 
			$response["current_time"] = time();
			update_option("_".$this->add_on."_crm_token",$response);
			$this->token = $response;
		}
	}
	function get_authorization(){
		$link_href='https://login.salesforce.com/services/oauth2/authorize?client_id='.$this->client_Key.'&redirect_uri='.$this->call_back.'&response_type=code'; 
		return $link_href;
	}
	function get_all_attributes_contact(){
		$datas[] = array("name"=>"First Name","label"=>"First Name","type"=>"text","des"=>"The contact first name");
		$datas[] = array("name"=>"Last Name","label"=>"Last Name","type"=>"text","des"=>"The contact Last Name","required"=>true);
		$datas[] = array("name"=>"Phone","label"=>"Phone","type"=>"text","des"=>"The contact Phone");
		return $datas;
	}
	function add_case($data){ 
		$url_case = $this->return_url("services/data/v55.0/sobjects/Case");
		$url_lead = $this->return_url("services/data/v55.0/sobjects/Lead");
	}
	function add_contact($data){
		$url = $this->return_url("services/data/v55.0/sobjects/Contact");
		$response = $this->post($url,$data);
		return $response;
	}
	public function get_data($key){
		return $this->$key;
	}
	function post($url, $data , $check_token= true){
		if(  $check_token ){	
			$post_json = json_encode($data);
			$tokens = $this->token;
			$token = $tokens["access_token"];
			$response = wp_remote_post( $url, array(
		    'body'    => $post_json,
		    'timeout'=>45,
			    'headers' => array(
			        'Content-Type' => 'application/json',
			        'Authorization' => 'Bearer '.$token,
			    ),
			) );
		}else{
			$post_json = http_build_query($data);
			$response = wp_remote_post( $url, array(
		    'body'    => $post_json,
		    'timeout'=>45,
		    'headers' => array(
		        	'content-type' => 'application/x-www-form-urlencoded',
		    	),
			) );
		}
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function get($url){
		$tokens = $this->token;
		$token = $tokens["access_token"];
		$response = wp_remote_get( $url, 
			 array(
			 	  'timeout'=>45,
			      'headers' => array(
			        'Content-Type' => 'application/json',
			        'Authorization' => 'Bearer '.$token,
			      )
			  )
			);
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function return_url($url){
		return $this->api_url. $url;
	}
}