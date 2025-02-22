<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Taskdefault_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single vault entry
     * @param  mixed $id vault entry id
     * @return object
     */
    public function get()
    {
        
        return $this->db->get(db_prefix().'taskdefault')->result_array();
        
    }
    
    public function get_by_id($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix().'taskdefault')->row();
    }



    /**
     * Create new vault entry
     * @param  array $data        $_POST data
     * @param  mixed $customer_id customer id
     * @return boolean
     */
    public function create($data)
    {
        $data['date_created']      = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix().'taskdefault', $data);
      
        log_activity('Task Entry Created');
    }

    /**
     * Update vault entry
     * @param  mixed $id   vault entry id
     * @param  array $data $_POST data
     * @return boolean
     */
    public function update($id, $data)
    {
     
           $this->db->where('id', $id);
        $this->db->update(db_prefix().'taskdefault', $data);

        if ($this->db->affected_rows() > 0) {
           
            return true;
        }

        return false;
    }

    /**
     * Delete vault entry
     * @param  mixed $id entry id
     * @return boolean
     */
    public function delete($id)
    {
     
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'taskdefault');

        if ($this->db->affected_rows() > 0) {
         
            return true;
        }

        return false;
    }
}
