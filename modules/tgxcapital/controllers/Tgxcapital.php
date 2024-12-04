<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Tgxcapital extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('app_modules');
        $this->load->model('taskdefault_model', 'taskdefault');
        if(!$this->app_modules->is_active('Tgxcapital')){
            access_denied("Tgxcapital");
        }
       
    }
    
    public function binario() {


        echo 2;
    }
    
    public function index(){
        
        
//        if ($this->staff_no_view_permissions) {
//            access_denied('Appointments');
//        }

        if ($this->input->post()) {

            $data = $this->input->post();

            if (!empty($data)) {

                if ($this->vehicle->create($data)) {

                    $data['message'] = "task created success";
                } else {
                    $data['message'] = "task created success";
                }
            }
        }


        $data['tasks'] = $this->taskdefault->get();
        $this->load->view('tasks', $data); 
      
        
    }
    
    
    
    public function create() {


        if (!staff_can('create', 'appointments') && !staff_appointments_responsible()) {
            access_denied();
        }

        $data = $this->input->post();


        if (!empty($data)) {
            if ($this->vehicle->create($data)) {

                set_alert('success', _l('added_successfully', 'Create success'));
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }
    
     public function delete($id) {
         
      
        $entry = $this->vehicle->get_by_id($id);
      
        if ($entry->creator == get_staff_user_id() || is_admin()) {
            $this->vehicle->delete($id);
        }
        redirect($_SERVER['HTTP_REFERER']);
    }


}