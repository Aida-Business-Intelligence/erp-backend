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

    /**
     * Endpoint para obter a visão geral do armazenamento de arquivos.
     * GET /api/file_storage/overview
     *
     * Parâmetros:
     * - total_available_gb: O armazenamento total que você quer considerar como "disponível" (ex: 20 GB, 50 GB).
     * Se não for fornecido, um valor padrão (ex: 10 GB) será usado.
     */
    // public function overview_get()
    // {
    //     \modules\api\core\Apiinit::the_da_vinci_code('api');

    //     // Defina um valor padrão para o armazenamento total disponível em GB
    //     // Este valor é o `total` que você passa para o componente.
    //     // Pode vir de uma configuração do sistema, de um parâmetro de requisição, etc.
    //     $total_available_gb = (float)($this->get('total_available_gb') ?? 10); // Exemplo: 10 GB padrão

    //     if ($total_available_gb <= 0) {
    //         $this->response([
    //             'status' => false,
    //             'message' => 'O valor de total_available_gb deve ser maior que zero.'
    //         ], REST_Controller::HTTP_BAD_REQUEST);
    //         return;
    //     }

    //     $storage_data = $this->File_storage_model->get_storage_overview($total_available_gb);

    //     // Prepara os ícones para o front-end. No PHP, retornamos apenas o nome do arquivo SVG.
    //     // O front-end React usará isso para construir o caminho completo para o ícone.
    //     $formatted_data_for_component = [];
    //     foreach ($storage_data['data'] as $item) {
    //         $formatted_data_for_component[] = [
    //             'name' => $item['name'],
    //             'usedStorage' => $item['usedStorage'], // Em bytes
    //             'filesCount' => $item['filesCount'],
    //             // Retorna apenas o nome do arquivo para o front-end construir o caminho completo
    //             'icon_name' => $item['icon'], 
    //         ];
    //     }

    //     $response = [
    //         'status' => true,
    //         'message' => 'Dados de visão geral do armazenamento recuperados com sucesso.',
    //         'total' => $total_available_gb * 1024 * 1024 * 1024, // total em bytes para o `total` prop do componente
    //         'chart' => [
    //             'series' => $storage_data['chart_series_percentage'],
    //         ],
    //         'data' => $formatted_data_for_component,
    //     ];

    //     $this->response($response, REST_Controller::HTTP_OK);
    // }



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