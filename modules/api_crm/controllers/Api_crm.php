<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Api_crm extends AdminController{
    public function __construct(){
        parent::__construct();
        $this->load->model('Api_crm_model');
    }
    public function manager(){
        $data['user_api'] = $this->Api_crm_model->get_user();
        $data['menu_items'] = $this->app_menu->get_sidebar_menu_items();
        $data['menu_options'] = json_decode(get_option('aside_menu_active'));
        $data['title'] = _l('Manager');
        $this->load->view('api_crm_manager', $data);
    }
     public function token(){
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->Api_crm_model->add_token($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('user_crm_api')));
                }
                 redirect(admin_url('api_crm/manager'));
            } else {
                $data = $this->input->post();
                $id   = $data['id'];
                unset($data['id']);
                $success = $this->Api_crm_model->update_token($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('user_crm_api')));
                }
                redirect(admin_url('api_crm/manager'));
            }
            die;
        }
    }
   public function delete_token($id){
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'user_crm_api');
        if ($this->db->affected_rows() > 0) {
            log_activity('User Deleted [ID: '.$id.']');
        }
        redirect(admin_url('api_crm/manager'));
    }
}