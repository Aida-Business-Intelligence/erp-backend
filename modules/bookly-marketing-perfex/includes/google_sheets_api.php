<?php
//v1.1
include_once  plugin_dir_path(__FILE__) . 'google_api_sheets/vendor/autoload.php';
Class Rednumber_Marketing_Google_Sheets_API{
	private  $client_id = "234486129885-pqrkg7b0hsn9khl34tgi16i5a7nc3u21.apps.googleusercontent.com";
	private  $client_secret = "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";
	private  $redirect = 'https://add-ons.org/token/index.php';
	private static $add_on ="google_sheets";
	function __construct(){
		$options = get_option("crm_marketing_".self::$add_on,array("method"=>"none"));
		if( $options["method"] == "app" ){
			$this->client_id = $options["client_id"];
			$this->client_secret = $options["client_secret"];
			$state = admin_url("admin.php?page=crm-marketing-config&tab=".self::$add_on );
			$this->redirect = $state;
		}
	}
	function get_sheet_id($url){
		$urls = explode("d/",$url);
		$urls = explode("/",$urls[1]);
		return $urls[0];
	}
   	public function code_to_token($code){
   		 $client = new Google_Client();
	      $client->setClientId(  $this->client_id );
	      $client->setClientSecret( $this->client_secret );
	      $client->setRedirectUri( $this->redirect );
	      $client->setScopes( Google_Service_Sheets::SPREADSHEETS );
	      $client->setAccessType( 'offline' );
	      $client->fetchAccessTokenWithAuthCode( $code );
	      $token = $client->getAccessToken();
	      update_option( "crm_marketing_".self::$add_on,array("api"=>$code));
      	  $this->update_token( $token );
      	  return $token;
   	}
   	private  function auth(){
   		  $client = new Google_Client();
	      $client->setClientId( $this->client_id );
	      $client->setClientSecret( $this->client_secret );
	      $client->setRedirectUri( $this->redirect );
	      $client->setScopes( Google_Service_Sheets::SPREADSHEETS );
	      $client->setAccessType( 'offline' );
	      $options = get_option("google_sheets_token");
	      if( isset($options["access_token"]) ){
	    	$accessToken = $options;
	      }
	      if( isset($accessToken) && $accessToken != "") {
		    $client->setAccessToken($accessToken);
		   }
		  if ($client->isAccessTokenExpired()) {
	        // Refresh the token if possible, else fetch a new one.
	        if ($client->getRefreshToken()) {
	            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
	        } else {
	        }
	    }
	    return $client;
   	}
   	public function update_token($token){
   		if(isset($token["expires_in"])){
   			update_option( "google_sheets_token",$token);
   		}
   	}
	public function get_authUrl(){
		$state = admin_url("admin.php?page=crm-marketing-config&tab=".self::$add_on );
		$client = new Google_Client();
	      $client->setClientId( $this->client_id );
	      $client->setClientSecret( $this->client_secret );
	      $client->setRedirectUri( $this->redirect );
	      $client->setState( $state );
	      $client->setScopes( Google_Service_Sheets::SPREADSHEETS );
	      $client->setAccessType( 'offline' );
	    return $client->createAuthUrl();
	}
	public function update_header($spreadsheetId="",$datas= array()){
		$client = $this->auth();
		$service = new Google_Service_Sheets( $client );
		$range ="A1:Z"; 
		$valueRange= new Google_Service_Sheets_ValueRange();
		// You need to specify the values you insert
		$valueRange->setValues(["values" => $datas]); // Add two values
		// Then you need to add some configuration
		$conf = ["valueInputOption" => "RAW"];
		$result = $service->spreadsheets_values->update( $spreadsheetId, $range, $valueRange, $conf );
	}
	public function create_spreadsheet($title){
		$client = $this->auth();
		$service = new Google_Service_Sheets( $client );
		$spreadsheet = new Google_Service_Sheets_Spreadsheet([
		    'properties' => [
		        'title' => $title
		    ]
		]);
		$spreadsheet = $service->spreadsheets->create($spreadsheet, [
		    'fields' => 'spreadsheetId'
		]);
		return $spreadsheet->spreadsheetId;
	}
	public function add_row($spreadsheetId="",$datas= array()){
		$client = $this->auth();
		$service = new Google_Service_Sheets( $client );
		$range ="A2:Z"; 
		$valueRange= new Google_Service_Sheets_ValueRange();
		// You need to specify the values you insert
		$valueRange->setValues(["values" => $datas]); // Add two values
		// Then you need to add some configuration
		$conf = ["valueInputOption" => "RAW"];
		$result = $service->spreadsheets_values->append( $spreadsheetId, $range, $valueRange, $conf );
		return $result;
	}
	public function get_header($spreadsheetId=""){
		$client = $this->auth();
		$service = new Google_Service_Sheets($client);
		$range = 'A1:Z';
		$response = $service->spreadsheets_values->get($spreadsheetId,$range);
		$values = $response->getValues();
		if (empty($values)) {
		} else {
		    foreach ($values as $row) {
		        return $row;
		       break;
		    }
		}
		return $values;
	}
}