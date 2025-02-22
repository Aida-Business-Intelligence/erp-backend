<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require __DIR__ . '/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Auth extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Api_model');
    }

    public function data_post() {
        

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $email = $_POST['email'];
        $password = $_POST['password'];;
        $data = $this->Authentication_model->login_api($email, $password);
        if (is_array($data) && isset($data['token'])) {

            $this->response($data, REST_Controller::HTTP_OK);
        }
        $this->response([
            'status' => FALSE,
            'error' => _l('admin_auth_invalid_email_or_password'),
            'message' => _l('admin_auth_invalid_email_or_password')
                ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
    }
    
    
    public function signin_post() {
        
        

        $email = $this->input->post('email');
        $password = $this->input->post('password', false);
        $data = $this->Authentication_model->login_api($email, $password);
        if (is_array($data) && isset($data['token'])) {

            $this->response($data, REST_Controller::HTTP_OK);
        }
        $this->response([
            'status' => FALSE,
            'error' => _l('admin_auth_invalid_email_or_password'),
            'message' => _l('admin_auth_invalid_email_or_password')
                ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
    }
    
    public function session_get() {
        
            $this->response(array(), REST_Controller::HTTP_OK);
    }
    
    public function authjs($type ='session') {
          $this->response(array(), REST_Controller::HTTP_OK);
        
        
    }
    
    
}
