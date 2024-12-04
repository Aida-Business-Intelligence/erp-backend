<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Api_crm_model extends App_Model{
    public function __construct(){
        parent::__construct();
    }
     public function get_user($id = ''){
        $this->db->select('*');
        if ('' != $id) {
            $this->db->where('id', $id);
        }
        return $this->db->get(db_prefix().'user_crm_api')->result_array();
    }
    public function add_token($data){
        $data['token'] = md5($data['name']).time();
        $this->db->insert(db_prefix().'user_crm_api', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New User Added [ID: '.$insert_id.', Name: '.$data['name'].']');
        }
        return $insert_id;
    }
    public function update_token($data, $id){
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'user_crm_api', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Ticket User Updated [ID: '.$id.' Name: '.$data['name'].']');
            return true;
        }
        return false;
    }
    public function get_user_token($token){
        $this->db->where('token', $token);
        $this->db->select('*');
        return $this->db->get(db_prefix().'user_crm_api')->result_array();
    }
    public function get_custom_fields(){
        $this->db->select('*');
        return $this->db->get(db_prefix().'customfields')->result_array();
    }
}
