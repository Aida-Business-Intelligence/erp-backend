<?php
//v2 private app
class Rednumber_Marketing_CRM_Hubspot_API {
	private $add_on ="hubspot";
	public $api_url='https://api.hubapi.com/';
	public $api_key = "";
	public $redirect_uri = "https://add-ons.org/token/index.php";
	public static $client_id = "6a3bca64-3a4c-4bb7-842c-85f2e8acbbab";
	public static $client_secret = "0902e9bc-24fd-48af-8b81-aeb672074253";
	private $token ="";
	private $attrs_contact = array();
	private $attrs_deal = array();
	private $attrs_company = array();
	private $attrs_ticket = array();
	private $attrs_task = array();
	private $attrs_note = array();
	private $attrs_pipeline = array();
	private $attrs_file = array();
	function __construct($admin = false){
		$options = get_option("crm_marketing_".$this->add_on,array("api"=>""));
		$this->token = array("access_token"=>$options["api"]);
		if( $admin ){
			$this->attrs_contact = $this->get_all_attributes_contact();
			$this->attrs_deal = $this->get_all_attributes_deal();
			$this->attrs_company = $this->get_all_attributes_company();
			$this->attrs_ticket = $this->get_all_attributes_ticket();
			$this->attrs_task = $this->get_all_attributes_task();
			$this->attrs_note = $this->get_all_attributes_note();
			$this->attrs_pipeline = $this->get_all_attributes_pipeline();
			$this->attrs_file = $this->get_all_attributes_file();
		}
	}
	function get_authorization(){
		$redirect_uri = $this->redirect_uri;
		$state = admin_url("admin.php?page=crm-marketing-config&tab=".$this->add_on);
		$scope = "scope=timeline%20oauth%20files%20tickets%20crm.lists.read%20crm.objects.contacts.read%20crm.objects.contacts.write%20crm.objects.marketing_events.read%20crm.objects.marketing_events.write%20crm.schemas.custom.read%20crm.objects.custom.read%20crm.objects.custom.write%20crm.objects.companies.write%20crm.schemas.contacts.read%20crm.objects.feedback_submissions.read%20crm.lists.write%20crm.objects.companies.read%20crm.objects.deals.read%20crm.objects.deals.write%20crm.schemas.companies.read%20crm.schemas.companies.write%20crm.schemas.contacts.write%20crm.schemas.deals.read%20crm.schemas.deals.write%20crm.objects.owners.read%20crm.objects.quotes.write%20crm.objects.quotes.read%20crm.schemas.quotes.read%20crm.objects.line_items.read%20crm.objects.line_items.write%20crm.schemas.line_items.read";
		$url = "https://app.hubspot.com/oauth/authorize?client_id=".self::$client_id."&".$scope."&redirect_uri=".$redirect_uri."&state=".urlencode($state);
		return $url;
	}
	function get_token($code){
		$url = "https://api.hubapi.com/oauth/v1/token";
		$data = array("grant_type"=>"authorization_code",
						"client_id"=>self::$client_id,
						"client_secret" =>self::$client_secret,
						"redirect_uri"=> $this->redirect_uri,
						"code" => $code
					);
		$response = $this->post($url,$data, false);
		$response=json_decode($response,true);
		if( isset($response["access_token"]) ){ 
			$response["current_time"] = time();
			update_option("_".$this->add_on."_crm_token",$response);
			$this->token = $response;
		}
	}
	function refresh_token($refresh_token){
		$url = "https://api.hubapi.com/oauth/v1/token";
		$data = array("grant_type"=>"refresh_token",
						"client_id"=>self::$client_id,
						"client_secret" =>self::$client_secret,
						"redirect_uri"=> $this->redirect_uri,
						"refresh_token" => $refresh_token
					);
		$response = $this->post($url,$data,false);
		$response=json_decode($response,true);
		$response["current_time"] = time();
		update_option("_".$this->add_on."_crm_token",$response);
		return $response;
	}
	function get_account_info($meta = ""){
		$url = $this->return_url("account-info/v3/details");
		$response = $this->get($url);
		$response=json_decode($response,true);
		if( $meta != "" ){
			return $response[ $meta ];
		}else{
			return $response;
		}
	}
	function get_properties($type="tickets"){
		$url = $this->return_url("crm/v3/properties/".$type);

		$response = $this->get($url);
		$response=json_decode($response,true);
		return $response["results"];
	}
	function get_all_attributes_pipelines_stages($type = "contacts",$pipelineId = 1) {
		$url = $this->return_url("crm/v3/pipelines/".$type."/".$pipelineId."/stages");
		$response = $this->get($url);
		$response=json_decode($response,true);
		return $response["results"];
	}
	function get_all_attributes_contact(){
		$datas = array();
		$properties = $this->get_properties("contacts");
		foreach( $properties as $property ){
			if($property["displayOrder"] >= 0 && $property["formField"] ){
				$select_options = array();
				switch( $property["fieldType"] ){
					case 'select':
						if( count($property["options"])>0 ){
							$type = "select";
							foreach( $property["options"] as $option ){
								$select_options[ $option["value"] ] = $option["label"];
							}
						}else{
							$type = "text";
						}
						break;
					default:
						$type = "text";
						break;
				}
				$datas[] = array("name"=>$property["name"],"label"=>$property["label"],"type"=>$type,"des"=>$property["description"],"select_options"=>$select_options);
			}
		}
		return $datas;
	}
	function get_all_attributes_deal(){
		$datas = array();
		$properties = $this->get_properties("deals");
		foreach( $properties as $property ){
			if($property["displayOrder"] >= 0 ){
				$select_options = array();
				switch( $property["fieldType"] ){
					case 'select':
						if( count($property["options"])>0 ){
							$type = "select";
							foreach( $property["options"] as $option ){
								$select_options[ $option["value"] ] = $option["label"];
							}
						}else{
							$type = "text";
						}
					default:
						$type = "text";
						break;
				}
				$datas[] = array("name"=>$property["name"],"label"=>$property["label"],"type"=>$type,"des"=>$property["description"],"select_options"=>$select_options);
			}
		}
		return $datas;
	}
	function get_all_attributes_company(){
		$datas = array();
		$properties = $this->get_properties("companies");
		foreach( $properties as $property ){
			if($property["displayOrder"] >= 0 ){
				$select_options = array();
				switch( $property["fieldType"] ){
					case 'select':
						if( count($property["options"])>0 ){
							$type = "select";
							foreach( $property["options"] as $option ){
								$select_options[ $option["value"] ] = $option["label"];
							}
						}else{
							$type = "text";
						}
					default:
						$type = "text";
						break;
				}
				$datas[] = array("name"=>$property["name"],"label"=>$property["label"],"type"=>$type,"des"=>$property["description"],"select_options"=>$select_options);
			}
		}
		return $datas;
	}
	function get_all_attributes_ticket(){
		$datas = array();
		$properties = $this->get_properties("tickets");
		foreach( $properties as $property ){
			if($property["displayOrder"] >= 0 ){
				$select_options = array();
				switch( $property["fieldType"] ){
					case 'select':
						if( count($property["options"])>0 ){
							$type = "select";
							foreach( $property["options"] as $option ){
								$select_options[ $option["value"] ] = $option["label"];
							}
						}else{
							$type = "text";
						}
					default:
						$type = "text";
						break;
				}
				$datas[] = array("name"=>$property["name"],"label"=>$property["label"],"type"=>$type,"des"=>$property["description"],"select_options"=>$select_options);
			}
			if($property["name"] == "hs_pipeline_stage"){
				$status = array("1"=>"New","2"=>"Waiting on contact","3"=>"Waiting on us","4"=>"Close");
				$datas[] = array("name"=>$property["name"],"label"=>$property["label"],"type"=>"select","des"=>$property["description"],"select_options"=>$status,"required"=>true);
			}
		}
		return $datas;
	}
	function get_all_attributes_pipeline(){
		$datas = array();
		$datas[] = array("name"=>"objectType","label"=>"objectType","type"=>"select","required"=>true,"select_options"=>array("deals"=>"deals","tickets"=>"tickets"));
		$datas[] = array("name"=>"pipelineId","label"=>"pipelineId","type"=>"text","required"=>true);
		$datas[] = array("name"=>"label","label"=>"label","type"=>"text","des"=>"A label used to organize pipeline stages in HubSpot's UI. Each pipeline stage's label must be unique within that pipeline.","required"=>true);
		$datas[] = array("name"=>"displayOrder","label"=>"displayOrder","type"=>"text","des"=>"The order for displaying this pipeline stage. If two pipeline stages have a matching displayOrder, they will be sorted alphabetically by label.","required"=>true);
		$datas[] = array("name"=>"probability","label"=>"probability","type"=>"text","des"=>"For deals pipelines, the probability field is required (0.0 and 1.0)","required"=>true);
		$datas[] = array("name"=>"ticketState","label"=>"ticketState","type"=>"select","des"=>"The ticketState field","select_options"=>array("OPEN"=>"OPEN","CLOSED"=>"CLOSED","required"=>true));
		return $datas;
	}
	function get_all_attributes_task(){
		$datas = array();
		$status = array("COMPLETED"=>"COMPLETED","NOT_STARTED"=>"NOT_STARTED");
		$priority = array("LOW"=>"LOW","MEDIUM"=>"MEDIUM","HIGH"=>"HIGH");
		$datas[] = array("name"=>"hs_task_subject","label"=>"hs_task_subject","type"=>"text","des"=>"The title of the task. ","required"=>true);
		$datas[] = array("name"=>"hs_task_status","label"=>"hs_task_status","type"=>"select","des"=>"The priority of the task.","select_options"=>$status);
		$datas[] = array("name"=>"hs_task_priority","label"=>"hs_task_priority","type"=>"select","des"=>"The status of the task","select_options"=>$priority);
		$datas[] = array("name"=>"hs_task_body","label"=>"hs_task_body","type"=>"text","des"=>"The task notes.");
		$datas[] = array("name"=>"hs_timestamp","label"=>"hs_timestamp","type"=>"text","des"=>"This field marks the task's due date. You can use either a Unix timestamp in milliseconds or UTC format.","required"=>true);
		return $datas;
	}
	function  get_all_attributes_file(){
		$datas = array();
		$datas[] = array("name"=>"file","label"=>"file","type"=>"text","des"=>"File to be uploaded.","required"=>true);
		$datas[] = array("name"=>"folderId","label"=>"folderId","type"=>"text","des"=>"Either 'folderId' or 'folderPath' is required. folderId is the ID of the folder the file will be uploaded to.");
		$datas[] = array("name"=>"folderPath","label"=>"folderPath","type"=>"text","des"=>"Either 'folderPath' or 'folderId' is required. This field represents the destination folder path for the uploaded file. If a path doesn't exist, the system will try to create one.");
		return $datas;
	}
	function get_all_attributes_note(){
		$datas = array();
		$properties = $this->get_properties("notes");
		foreach( $properties as $property ){
			if($property["displayOrder"] >= 0 ){
				$select_options = array();
				switch( $property["fieldType"] ){
					case 'select':
						if( count($property["options"])>0 ){
							$type = "select";
							foreach( $property["options"] as $option ){
								$select_options[ $option["value"] ] = $option["label"];
							}
						}else{
							$type = "text";
						}
					default:
						$type = "text";
						break;
				}
				$datas[] = array("name"=>$property["name"],"label"=>$property["label"],"type"=>$type,"des"=>$property["description"],"select_options"=>$select_options);
			}
		}
		return $datas;
	}
	function add_contact($data){
		$url = $this->return_url("crm/v3/objects/contacts");
		$response = $this->post($url,$data);
		return $response;
	}
	function add_deal($data){
		$url = $this->return_url("crm/v3/objects/deals");
		$response = $this->post($url,$data);
		return $response;
	}
	function add_company($data){
		$url = $this->return_url("crm/v3/objects/companies");
		$response = $this->post($url,$data);
		return $response;
	}
	function add_ticket($data){
		$url = $this->return_url("crm/v3/objects/tickets");
		$response = $this->post($url,$data);
		return $response;
	}
	function add_pipeline($data,$objectType="", $pipelineId = ""){
		$url = $this->return_url("crm/v3/pipelines/".$objectType."/".$pipelineId."/stages");
		$response = $this->post($url,$data);
		return $response;
	}
	function add_task($data){
		$url = $this->return_url("crm/v3/objects/tasks");
		$response = $this->post($url,$data);
		return $response;
	}
	function add_file($data){
		$url = $this->return_url("files/v3/files");
		$response = $this->post_file($url,$data);
		return $response;
	}
	public function get_data($key){
		return $this->$key;
	}
	function post($url, $data , $no_token= true){
		if(  $no_token ){
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
	function post_file($url, $data){
		$tokens = $this->token;
		$token = $tokens["access_token"];
		$payload = '';
		$local_file = $data["file"];
		unset($data["file"]);
		$post_fields = $data;
		$post_fields["options"] = '{
  "access":  "PUBLIC_NOT_INDEXABLE",
"ttl": "P2W",
"overwrite": false,
"duplicateValidationStrategy": "NONE",
"duplicateValidationScope": "EXACT_FOLDER"
}';
		$boundary = wp_generate_password( 24 );
		foreach ( $post_fields as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . $name .
				'"' . "\r\n\r\n";
			$payload .= $value;
			$payload .= "\r\n";
		}
		// Upload the file
		if ( $local_file ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . 'file' .
				'"; filename="' . basename( $local_file ) . '"' . "\r\n";
			$payload .= "\r\n";
			$payload .= file_get_contents( $local_file );
			$payload .= "\r\n";
		}
		$payload .= '--' . $boundary . '--';
		$response = wp_remote_post( $url, array(
		    'body'    => $payload,
		    'timeout'=>45,
		    'headers' => array(
		        'content-type' => 'multipart/form-data; boundary=' . $boundary,
		        'accept' => 'application/json',
		        'Authorization' => 'Bearer '.$token,
		    ),
		) );
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function get($url){
		$tokens = $this->token;
		$token = $tokens["access_token"];
		$response = wp_remote_get( $url, 
			 array(
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