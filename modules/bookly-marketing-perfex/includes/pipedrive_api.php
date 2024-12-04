<?php
//version 1.1
class Rednumber_Marketing_CRM_Pipedrive_API {
 	public $api_url='https://api.pipedrive.com';
	public $api_key = "";
	public $domain = "";
	private $lead_labels = array();
	private $attrs_lead = array();
	private $attrs_person = array();
	private $attrs_deal = array();
	private $attrs_activity = array();
	private $attrs_note = array();
	private $attrs_organization = array();
	private $attrs_file = array();
	private static $add_on ="pipedrive";
	function __construct($admin = false){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$this->api_key = $options["api"];
		if( $admin ){ 
			$this->lead_labels = $this->get_all_lead_labels();
			$this->attrs_lead = $this->get_all_attributes_lead();
			$this->attrs_person = $this->get_all_attributes_persons();
			$this->attrs_deal = $this->get_all_attributes_deal();
			$this->attrs_activity = $this->get_all_attributes_activity();
			$this->attrs_note = $this->get_all_attributes_note();
			$this->attrs_organization = $this->get_all_attributes_organization();
			$this->attrs_file = $this->get_all_attributes_file();
		}
	}
	function get_company_domain(){
		$result =	$this->get("https://api.pipedrive.com/v1/users/me?api_token=".$this->api_key );
		if (!empty($result['data']['company_domain'])) { 
				$this->domain= $result['data']['company_domain'];
		}
	}
	function get_all_lists_contact(){
		$url = $this->return_url('contacts/lists?limit=10&offset=0&sort=desc');
		$response = $this->get($url);
		$response = json_decode($response);
		return $response->lists;
	}
	function get_all_attributes_lead(){
		$lead_labels= $this->lead_labels;
		$lead_labels_ids = array();
		foreach( $lead_labels as $label ){
			$lead_labels_ids[$label["id"]] = $label["name"];
		}
		$datas[] = array("name"=>"title","label"=>"Title","type"=>"text","des"=>"The name of the lead","required"=>true);
		$datas[] = array("name"=>"owner_id","label"=>"Owner id","type"=>"text","des"=>"The ID of the user which will be the owner of the created lead. If not provided, the user making the request will be used.");
		$datas[] = array("name"=>"label_ids","label"=>"Label ids","type"=>"checkbox_array","des"=>"The IDs of the lead labels which will be associated with the lead","select_options"=>$lead_labels_ids);
		$datas[] = array("name"=>"person_id","label"=>"person_id","type"=>"text","des"=>"The ID of a person which this lead will be linked to. If the person does not exist yet, it needs to be created first. This property is required unless organization_id is specified.");
		$datas[] = array("name"=>"organization_id","label"=>"organization_id","type"=>"text","des"=>"The ID of an organization which this lead will be linked to. If the organization does not exist yet, it needs to be created first. This property is required unless person_id is specified.");
		$datas[] = array("name"=>"amount","label"=>"Amount","type"=>"text","des"=>"The potential value of the lead");
		$datas[] = array("name"=>"currency","label"=>"Currency","type"=>"text","des"=>"The potential value of the lead");
		$datas[] = array("name"=>"expected_close_date","label"=>"expected_close_date","type"=>"text","des"=>"The date of when the deal which will be created from the lead is expected to be closed. In ISO 8601 format: YYYY-MM-DD.");
		$datas[] = array("name"=>"expected_close_date","label"=>"expected_close_date","type"=>"text","des"=>"The date of when the deal which will be created from the lead is expected to be closed. In ISO 8601 format: YYYY-MM-DD.");
		return $datas;
	}
	function get_all_attributes_persons(){
		$datas = array();
		$marketing_status = array("no_consent"=>"no_consent","unsubscribed"=>"unsubscribed","subscribed"=>"subscribed","archived"=>"archived");
		$datas[] = array("name"=>"name","label"=>"Name","type"=>"text","des"=>"The name of the person","required"=>true);
		$datas[] = array("name"=>"owner_id","label"=>"owner_id","type"=>"text","des"=>"The ID of the user who will be marked as the owner of this person. When omitted, the authorized user ID will be used.");
		$datas[] = array("name"=>"org_id","label"=>"org_id","type"=>"text","des"=>"The ID of the organization this person will belong to");
		$datas[] = array("name"=>"email","label"=>"email","type"=>"text","des"=>"Email data related to the person");
		$datas[] = array("name"=>"phone","label"=>"phone","type"=>"text","des"=>"Phone data related to the person");
		$datas[] = array("name"=>"marketing_status","label"=>"marketing_status","type"=>"select","des"=>"f the person does not have a valid email address, then the marketing status is not set and no_consent is returned for the marketing_status value when the new person is created. If the change is forbidden, the status will remain unchanged for every call that tries to modify the marketing status. Please be aware that it is only allowed once to change the marketing status from an old status to a new one.","select_options" =>$marketing_status );
		$datas[] = array("name"=>"add_time","label"=>"add_time","type"=>"text","des"=>"The optional creation date & time of the person in UTC. Requires admin user API token. Format: YYYY-MM-DD HH:MM:SS");
		
		$lists = $this->get_all_persons_fields();
		
		$remove = array("last_outgoing_mail_time","last_incoming_mail_time","picture_id","email_messages_count",
			"undone_activities_count","done_activities_count","activities_count","closed_deals_count","lost_deals_count","won_deals_count","id","last_activity_date","next_activity_date","visible_to","open_deals_count","owner_id","org_id","update_time","add_time","email","phone","first_name","last_name","label","name");
		foreach( $lists as $list ){
			if( !in_array($list["key"],$remove) ){
				$datas[] = array("name"=>$list["key"],"label"=>$list["name"] ,"type"=>"text","des"=>"");
			}
			
		}
		return $datas;
	}
	function get_all_attributes_deal(){
		$datas = array();
		$status = array("open"=>"open","won"=>"won","lost"=>"lost","deleted"=>"deleted");
		$datas[] = array("name"=>"title","label"=>"title","type"=>"text","des"=>"The title of the deal","required"=>true);
		$datas[] = array("name"=>"value","label"=>"value","type"=>"text","des"=>"The value of the deal. If omitted, value will be set to 0.");
		$datas[] = array("name"=>"currency","label"=>"currency","type"=>"text","des"=>"The currency of the deal. Accepts a 3-character currency code. If omitted, currency will be set to the default currency of the authorized user.");
		$datas[] = array("name"=>"user_id","label"=>"user_id","type"=>"text","des"=>"The ID of the user which will be the owner of the created deal. If not provided, the user making the request will be used.");
		$datas[] = array("name"=>"person_id","label"=>"person_id","type"=>"text","des"=>"The ID of a person which this deal will be linked to. If the person does not exist yet, it needs to be created first. This property is required unless org_id is specified.");
		$datas[] = array("name"=>"org_id","label"=>"org_id","type"=>"text","des"=>"The ID of an organization which this deal will be linked to. If the organization does not exist yet, it needs to be created first. This property is required unless person_id is specified.");
		$datas[] = array("name"=>"pipeline_id","label"=>"pipeline_id","type"=>"text","des"=>"The ID of the pipeline this deal will be added to. By default, the deal will be added to the first stage of the specified pipeline. Please note that pipeline_id and stage_id should not be used together as pipeline_id will be ignored.");
		$datas[] = array("name"=>"stage_id","label"=>"stage_id","type"=>"text","des"=>"The ID of the stage this deal will be added to. Please note that a pipeline will be assigned automatically based on the stage_id. If omitted, the deal will be placed in the first stage of the default pipeline.");
		$datas[] = array("name"=>"status","label"=>"status","type"=>"select","des"=>"If omitted, status will be set to open.","select_options"=>$status);
		$datas[] = array("name"=>"expected_close_date","label"=>"expected_close_date","type"=>"text","des"=>"The expected close date of the deal. In ISO 8601 format: YYYY-MM-DD.");
		$datas[] = array("name"=>"probability","label"=>"probability","type"=>"text","des"=>"The success probability percentage of the deal. Used/shown only when deal_probability for the pipeline of the deal is enabled.");
		$datas[] = array("name"=>"add_time","label"=>"add_time","type"=>"text","des"=>"The optional creation date & time of the person in UTC. Requires admin user API token. Format: YYYY-MM-DD HH:MM:SS");
		return $datas;
	}
	function get_all_attributes_activity(){
		$marketing_subject = array("Call"=>"Call","Meeting"=>"Meeting","Task"=>"Task","Deadline"=>"Deadline","Email"=>"Email","Lunch"=>"Lunch");
		$datas[] = array("name"=>"subject","label"=>"subject","type"=>"select","des"=>"The subject of the activity. When value for subject is not set, it will be given a default value Call.","select_options"=>$marketing_subject);
		$datas[] = array("name"=>"due_date","label"=>"due_date","type"=>"text","des"=>"The due date of the activity. Format: YYYY-MM-DD");
		$datas[] = array("name"=>"due_time","label"=>"due_time","type"=>"text","des"=>"The due time of the activity in UTC. Format: HH:MM");
		$datas[] = array("name"=>"duration","label"=>"duration","type"=>"text","des"=>"The duration of the activity. Format: HH:MM");
		$datas[] = array("name"=>"deal_id","label"=>"deal_id","type"=>"text","des"=>"The ID of the deal this activity is associated with");
		$datas[] = array("name"=>"lead_id","label"=>"lead_id","type"=>"text","des"=>"The ID of the lead this activity is associated with");
		$datas[] = array("name"=>"person_id","label"=>"person_id","type"=>"text","des"=>"The ID of the person this activity is associated with");
		$datas[] = array("name"=>"org_id","label"=>"org_id","type"=>"text","des"=>"The ID of the organization this activity is associated with");
		$datas[] = array("name"=>"note","label"=>"note","type"=>"text","des"=>"The note of the activity");
		$datas[] = array("name"=>"location","label"=>"location","type"=>"text","des"=>"The address of the activity. Pipedrive will automatically check if the location matches a geo-location on Google maps.");
		$datas[] = array("name"=>"public_description","label"=>"public_description","type"=>"text","des"=>"Additional details about the activity that is synced to your external calendar. Unlike the note added to the activity, the description is publicly visible to any guests added to the activity.");
		$datas[] = array("name"=>"type","label"=>"type","type"=>"text","des"=>"The type of the activity. This is in correlation with the key_string parameter of ActivityTypes. When value for type is not set, it will be given a default value Call.");
		$datas[] = array("name"=>"user_id","label"=>"user_id","type"=>"text","des"=>"The ID of the user whom the activity is assigned to. If omitted, the activity is assigned to the authorized user.");
		$datas[] = array("name"=>"done","label"=>"done","type"=>"checkbox","des"=>"Whether the activity is done or not.");
		return $datas;
	}
	function get_all_attributes_note(){
		$datas = array();
		$datas[] = array("name"=>"content","label"=>"content","type"=>"text","des"=>"The content of the note","required"=>true);
		$datas[] = array("name"=>"lead_id","label"=>"lead_id","type"=>"text","des"=>"The ID of the lead the note will be attached to. This property is required unless one of (deal_id/person_id/org_id) is specified.");
		$datas[] = array("name"=>"deal_id","label"=>"deal_id","type"=>"text","des"=>"The ID of the deal the note will be attached to. This property is required unless one of (lead_id/person_id/org_id) is specified.");
		$datas[] = array("name"=>"person_id","label"=>"person_id","type"=>"text","des"=>"The ID of the person this note will be attached to. This property is required unless one of (deal_id/lead_id/org_id) is specified.");
		$datas[] = array("name"=>"org_id","label"=>"org_id","type"=>"text","des"=>"The ID of the organization this note will be attached to. This property is required unless one of (deal_id/lead_id/person_id) is specified.");
		$datas[] = array("name"=>"user_id","label"=>"user_id","type"=>"text","des"=>"The ID of the user who will be marked as the author of the note. Only an admin can change the author.");
		$datas[] = array("name"=>"add_time","label"=>"add_time","type"=>"text","des"=>"The optional creation date & time of the note in UTC. Can be set in the past or in the future. Requires admin user API token. Format: YYYY-MM-DD HH:MM:SS");
		return $datas;
	}
	function get_all_attributes_organization(){
		$datas = array();
		$datas[] = array("name"=>"name","label"=>"name","type"=>"text","des"=>"The name of the organization","required"=>true);
		$datas[] = array("name"=>"owner_id","label"=>"owner_id","type"=>"text","des"=>"The ID of the user who will be marked as the owner of this organization. When omitted, the authorized user ID will be used.");
		$datas[] = array("name"=>"add_time","label"=>"add_time","type"=>"text","des"=>"The optional creation date & time of the note in UTC. Can be set in the past or in the future. Requires admin user API token. Format: YYYY-MM-DD HH:MM:SS");
		return $datas;
	}
	function get_all_attributes_file(){
		$datas = array();
		$datas[] = array("name"=>"file","label"=>"file","type"=>"text","des"=>"A single file (use [upload_pdf] PDF Customizer plugin.)","required"=>true);
		$datas[] = array("name"=>"deal_id","label"=>"deal_id","type"=>"text","des"=>"The ID of the deal to associate file(s) with");
		$datas[] = array("name"=>"person_id","label"=>"person_id","type"=>"text","des"=>"The ID of the person to associate file(s) with");
		$datas[] = array("name"=>"org_id","label"=>"org_id","type"=>"text","des"=>"The ID of the organization to associate file(s) with");
		$datas[] = array("name"=>"product_id","label"=>"product_id","type"=>"text","des"=>"The ID of the product to associate file(s) with");
		$datas[] = array("name"=>"activity_id","label"=>"product_id","type"=>"text","des"=>"The ID of the activity to associate file(s) with");
		return $datas;
	}
	function get_all_lead_labels(){
		$url = $this->return_url('/v1/leadLabels');
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response['data'];
	}
	function get_all_persons_fields(){
		$url = $this->return_url('/v1/personFields');
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response['data'];
	}
	function get_all_organizations(){
		$url = $this->return_url('/v1/organizations');
		$response = $this->get($url);
		$response = json_decode($response,true);
		if( $response['data'] == null){
			return array();
		}
		return $response['data'];
	}
	function get_all_user(){
		$url = $this->return_url('/v1/users');
		$response = $this->get($url);
		$response = json_decode($response,true);
		return $response['data'];
	}
	function add_lead($data){
		$url = $this->return_url('/v1/leads');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_deal($data){
		$url = $this->return_url('/v1/deals');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_person($data){
		$url = $this->return_url('/v1/persons');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_activity($data){
		$url = $this->return_url('/v1/activities');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_note($data){
		$url = $this->return_url('/v1/notes');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_organization($data){
		$url = $this->return_url('/v1/organizations');
		$response = $this->post($url,$data);
		return $response;
	}
	function add_file($data){
		$url = $this->return_url('/v1/files');
		$response = $this->post_file($url,$data);
		return $response;
	}
	public function get_data($key){
		return $this->$key;
	}
	function post($url, $data){
		$post_json = json_encode($data);
		$response = wp_remote_post( $url, array(
		    'body'    => $post_json,
		    'timeout'=>45,
		    'headers' => array(
		        'content-type' => 'application/json',
		        'accept' => 'application/json',
		    ),
		) );
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function post_file($url, $data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	function get($url){
		$response = wp_remote_get( $url, 
			 array(
			    'timeout'=>45,
			    'headers' => array(
			        'content-type' => 'multipart/form-data',
			        'accept' => 'application/json',
			    ),
			  )
			);
		$responseBody = wp_remote_retrieve_body( $response );
		return $responseBody;
	}
	function return_url($url){
		return $this->api_url. $url ."?api_token=".$this->api_key;
	}
}