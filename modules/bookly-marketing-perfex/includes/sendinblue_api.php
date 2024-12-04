<?php
class Rednumber_Marketing_CRM_Sendinblue_API {
 	public $api_url='https://api.sendinblue.com/v3/';
	public $api_key = "";
	private static $add_on ="sendinblue";
	private $ids_list_contact = array();
	private $attrs_contact = array();
	private $attrs_deal = array();
	private $attrs_company = array();
	private $attrs_task = array();
	private $attrs_note = array();
	private $attrs_file = array();
	private $pipeline_stages = array();
	private $account = array();
	private $task_type = array();
	function __construct( $admin = false){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$this->api_key = $options["api"];
		if( $admin ){
			$this->ids_list_contact = $this->get_all_lists_contact();
			$this->pipeline_stages = $this->get_pipeline_stages();
			$this->account = $this->get_account();
			$this->task_type = $this->get_taks_type();
			$this->attrs_contact = $this->get_all_attributes_contact();
			$this->attrs_deal = $this->get_all_attributes_deal();
			$this->attrs_company = $this->get_all_attributes_company();
			$this->attrs_task = $this->get_all_attributes_task();
			$this->attrs_note = $this->get_all_attributes_note();
			$this->attrs_file = $this->get_all_attributes_file();
		}
	}
	function get_all_lists_contact(){
		$url = $this->return_url('contacts/lists?limit=50&offset=0&sort=desc');
		$response = $this->get($url);
		$response = json_decode($response);
		return $response->lists;
	}
	function get_all_attributes_contact(){
		$url = $this->return_url('contacts/attributes');
		$response = $this->get($url);
		$response = json_decode($response);
		$datas = array();
		$datas[] = array("name"=>"email","label"=>"Email","type"=>"text","required"=>true,"des"=>"Email address of the user");
		$datas[] = array("name"=>"SMS","label"=>"SMS","type"=>"text","des"=>"Mobile Number in SMS field should be passed with proper country code. For example: +91xxxxxxxxxx or 0091xxxxxxxxxx");
		foreach( $response->attributes as $vl ) {
			if( property_exists($vl,"field_key") &&  $vl->field_key != "" ) {
				$datas[] = array("name"=>$vl->field_key,"label"=>$vl->name,"type"=>"text");
			}
		}
		$ids_list_contact = $this->ids_list_contact;
		$lists = array();
		foreach( $ids_list_contact as $list ){
			$lists[$list->id] = $list->name;
		}
		$datas[] = array("name"=>"listIds","label"=>"listIds","type"=>"checkbox_array","select_options"=>$lists,"required"=>false,"des"=>"Ids of the lists to add the contact to");
		return $datas;
	}
	function get_all_attributes_deal(){
		$url = $this->return_url('crm/attributes/deals');
		$response = $this->get($url);
		$response = json_decode($response,true);
		$datas = array();
		foreach( $response as $attributes ) {
			$type = "text";
			$remove = false;
			$select_options = array();
			if( isset($attributes["attributeTypeName"]) ){
				$type = $attributes["attributeTypeName"];
			}
			switch($type) {
				case "single-select":
					$type = "select";
					foreach( $attributes["attributeOptions"] as $attrs ){
						$select_options[$attrs["key"]] = $attrs["value"];
					}
					break;
				case "user":
					$type = "select";
					foreach( $attributes["attributeOptions"] as $attrs ){
						$select_options[$attrs["key"]] = $attrs["value"];
					}
					break;
			}
			switch ($attributes["internalName"] ){
				case "pipeline":
				case "deal_owner":
					$remove = true;
					break;
				case "deal_stage":
					$select_options = $this->pipeline_stages;
					break;
				case "deal_owner":
					$type = "text";
					break;
			}
			if( !$remove ){
				$datas[] = array("name"=>$attributes["internalName"],"label"=>$attributes["label"],"type"=>$type,"required"=>$attributes["isRequired"],"select_options"=>$select_options);
			}
		}
		return $datas;
	}
	function get_all_attributes_company(){
		$url = $this->return_url('companies/attributes');
		$response = $this->get($url);
		$response = json_decode($response,true);
		$datas = array();
		foreach( $response as $attributes ) {
			$type = "text";
			$remove = false;
			$select_options = array();
			if( isset($attributes["attributeTypeName"]) ){
				$type = $attributes["attributeTypeName"];
			}
			switch($type) {
				case "single-select":
					$type = "select";
					foreach( $attributes["attributeOptions"] as $attrs ){
						$select_options[$attrs["key"]] = $attrs["value"];
					}
					break;
				case "user":
					$type = "select";
					foreach( $attributes["attributeOptions"] as $attrs ){
						$select_options[$attrs["key"]] = $attrs["value"];
					}
					break;
			}
			switch ($attributes["internalName"] ){
				case "owner":
					$remove = true;
					break;
				case "name":
					$attributes["required"] = true;
					break;
			}
			if( !$remove ){
				$datas[] = array("name"=>$attributes["internalName"],"label"=>$attributes["label"],"type"=>$type,"required"=>$attributes["required"],"select_options"=>$select_options);
			}
		}
		return $datas;
	}
	function get_taks_type(){
		$url = $this->return_url('crm/tasktypes');
		$response = $this->get($url);
		$response = json_decode($response,true);
		$datas = array();
		foreach ( $response as $stage ){
			$datas[ $stage["id"] ] = $stage["title"];
		}
		return $datas;
	}
	function get_all_attributes_task(){
		$datas = array();
		$select_options = array();
		$datas[] = array("name"=>"name","label"=>"Name of task","type"=>"text","required"=>true,"des"=>"Name of task");
		$datas[] = array("name"=>"duration","label"=>"duration","type"=>"text","des"=>"Duration of task in milliseconds [1 minute = 60000 ms]");
		$datas[] = array("name"=>"taskTypeId","label"=>"Task Type Id","type"=>"select","des"=>"Id for type of task e.g Call / Email / Meeting etc.","select_options"=>$this->task_type,"required"=>true);
		$datas[] = array("name"=>"notes","label"=>"Notes","type"=>"text","des"=>"Notes added to a task");
		$datas[] = array("name"=>"date","label"=>"Date","type"=>"text","des"=>"Task due date and time (Y-m-d H:i:s)","required"=>true);
		$datas[] = array("name"=>"done","label"=>"Done","type"=>"checkbox","des"=>"Task marked as done");
		$datas[] = array("name"=>"contactsIds","label"=>"Contacts Ids","type"=>"text","des"=>"Contact ids for contacts linked to this task");
		$datas[] = array("name"=>"dealsIds","label"=>"Deals Ids","type"=>"text","des"=>"Deal ids for deals a task is linked to");
		$datas[] = array("name"=>"companiesIds","label"=>"Companies Ids","type"=>"text","des"=>"Companies ids for companies a task is linked to");
		return $datas;
	}
	function get_all_attributes_note(){
		$datas = array();
		$datas[] = array("name"=>"text","label"=>"Text","type"=>"text","des"=>"Text content of a note","required"=>true);
		$datas[] = array("name"=>"contactIds","label"=>"Contacts Ids","type"=>"text","des"=>"Contact ids for contacts linked to this task (Contacts or deals or companies are required.)");
		$datas[] = array("name"=>"dealIds","label"=>"Deals Ids","type"=>"text","des"=>"Deal ids for deals a task is linked to");
		$datas[] = array("name"=>"companyIds","label"=>"Companies Ids","type"=>"text","des"=>"Companies ids for companies a task is linked to");
		return $datas;
	}
	function get_all_attributes_file(){
		$datas = array();
		$datas[] = array("name"=>"file","label"=>"File","type"=>"text","des"=>"File data to create a file (use [upload_pdf] PDF Customizer plugin.","required"=>true);
		$datas[] = array("name"=>"dealId","label"=>"Deal Id","type"=>"text","des"=>"(Contacts or deals or companies are required.)");
		$datas[] = array("name"=>"contactId","label"=>"Contact ID","type"=>"text");
		$datas[] = array("name"=>"companyId","label"=>"Companies Id","type"=>"text");
		return $datas;
	}
	function get_pipeline_stages(){
		$url = $this->return_url('crm/pipeline/details');
		$response = $this->get($url);
		$response = json_decode($response,true);
		$datas = array();
		foreach ( $response["stages"] as $stage ){
			$datas[ $stage["id"] ] = $stage["name"];
		}
		return $datas;
	}
	function get_account(){
		$url = $this->return_url('account');
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response;
	}
	public function get_data($key){
		return $this->$key;
	}
	function add_contact($data){
		$url = $this->return_url('contacts');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_company($data){
		$url = $this->return_url('companies');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_deal($data){
		$url = $this->return_url('crm/deals');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_task($data){
		$url = $this->return_url('crm/tasks');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_note($data){
		$url = $this->return_url('crm/notes');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_file($data){
		$url = $this->return_url('crm/files');
		$response = $this->post_file($url,$data);
		return $response;
	}
	function post($url, $data){
		$post_json = json_encode($data);
		$response = wp_remote_post( $url, array(
		    'body'    => $post_json,
		    'timeout'=>45,
		    'headers' => array(
		        'content-type' => 'application/json',
		        'accept' => 'application/json',
		        'api-key' => $this->api_key,
		    ),
		) );
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function post_file($url, $data){
		$payload = '';
		$local_file = $data["file"];
		unset($data["file"]);
		$post_fields = $data;
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
		        'api-key' => $this->api_key,
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
			        'accept' => 'application/json',
			        'api-key' => $this->api_key,
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