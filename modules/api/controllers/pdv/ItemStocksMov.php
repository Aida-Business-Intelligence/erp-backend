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

            $movements = $this->ItemStocksMov_model->get_movements_by_item($item_id);

            $this->response([
                'success' => true,
                'data' => $movements
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}