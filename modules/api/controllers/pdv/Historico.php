<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Historico extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    public function get_get($imposto_id = '')
    {
        if (empty($imposto_id) || !is_numeric($imposto_id)) {
            $this->response(['status' => FALSE, 'message' => 'ID do imposto é obrigatório.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->where('impostoId', $imposto_id)->order_by('dataHora', 'DESC');

        $historico = $this->db->get(db_prefix() . 'imposto_historico')->result();

        $this->response(['status' => TRUE, 'data' => $historico], REST_Controller::HTTP_OK);
    }
}