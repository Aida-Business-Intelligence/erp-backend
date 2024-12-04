<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Si_custom_theme_model extends App_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	* get all themes
	*/
	function get_themes($id='')
	{
		$theme_list = [];
		if(is_numeric($id))
			$this->db->where('id',$id);
		$this->db->order_by('theme_type','DESC');
		$result = $this->db->get(db_prefix() . 'si_custom_theme_list');
		if(!empty($result))
		{
			foreach($result->result_array() as $theme)
			{
				$theme_list[$theme['id']] = $theme;
				$theme_list[$theme['id']]['class'] = explode("|",$theme_list[$theme['id']]['class']);
			}
		}
		if(is_numeric($id) && isset($theme_list[$id]))
			return $theme_list[$id];
		else										
			return $theme_list;
	}
	/**
	* Add new theme style
	* @param mixed $data All $_POST data
	* @return mixed
	*/
	public function add($data)
	{
		$this->db->insert(db_prefix() . 'si_custom_theme_list', $data);
		$insert_id = $this->db->insert_id();
		if ($insert_id) {
			log_activity('New Theme Added [Name:' . $data['theme_name'] . ']');
			return $insert_id;
		}
		return false;
	}
	/**
	* Update theme style
	* @param mixed $data All $_POST data
	* @return mixed
	*/
	public function update($data,$theme_id)
	{
		$this->db->where('id',$theme_id);
		$update = $this->db->update(db_prefix() . 'si_custom_theme_list', $data);
		if ($update) {
			$theme = $this->get_themes($theme_id);
			log_activity('Theme Updated [Name:' . $theme['theme_name'] . ']');
			return true;
		}
		return false;
	}
	/**
	* Delete theme style
	* @param  mixed $id theme id
	* @return boolean
	*/
	public function delete($id)
	{
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'si_custom_theme_list');
		if ($this->db->affected_rows() > 0) {
			log_activity('Theme Deleted [ID:' . $id . ']');
			return true;
		}
		return false;
	}
	/**
	* Reset theme style
	* @param  mixed $id theme id
	* @return boolean
	*/
	public function reset_theme()
	{
		$this->db->where('theme_type', 'default');
		$this->db->set('theme_style', 'default_style',false);#copy from default_style
		$this->db->update(db_prefix() . 'si_custom_theme_list');
		if ($this->db->affected_rows() > 0) {
			log_activity('Theme Reset done.');
			return true;
		}
		return false;
	}
}
