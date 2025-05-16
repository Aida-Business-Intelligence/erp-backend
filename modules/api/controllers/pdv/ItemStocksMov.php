<?php
defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class ItemStocksMov extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('ItemStocksMov_model');
    }

    /**
     * Get stock movements by item ID
     * GET /api/itemstocksmov/movements/{item_id}
     */
    public function movements_get($item_id = '')
    {
        try {
            // Tenta pegar do parâmetro direto, ou da query
            $item_id = $item_id ?: $this->input->get('id');

            if (empty($item_id) || !is_numeric($item_id)) {
                throw new Exception('Item ID is required and must be numeric', 400);
            }

            // Parâmetros de paginação
            $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
            $pageSize = $this->input->get('pageSize') ? (int) $this->input->get('pageSize') : 10;
            $sortField = $this->input->get('sortField') ?: 'date';
            $sortOrder = $this->input->get('sortOrder') === 'DESC' ? 'DESC' : 'ASC';

            // Obter movimentações com paginação
            $result = $this->ItemStocksMov_model->get_movements_by_item($item_id, $page, $pageSize, $sortField, $sortOrder);

            $this->response([
                'success' => true,
                'data' => $result['data'],
                'total' => $result['total']
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}