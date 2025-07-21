<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class File_storage extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('File_storage_model');
    }


        public function overview_get()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $storage_data = $this->File_storage_model->get_storage_overview_data();

        $response = [
            'status' => true,
            'message' => 'Dados de visão geral do armazenamento recuperados com sucesso.',
            'total_used_storage_bytes' => $storage_data['total_used_storage_bytes'],
            'total_files_count' => $storage_data['total_files_count'],
            'data' => $storage_data['data'],
        ];

        $this->response($response, REST_Controller::HTTP_OK);
    }
}
