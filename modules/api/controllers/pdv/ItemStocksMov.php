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
    public function movements_get($item_id = '')
    {
        try {
            $item_id = $item_id ?: $this->input->get('id');

            if (empty($item_id) || !is_numeric($item_id)) {
                throw new Exception('Item ID is required and must be numeric', 400);
            }

            $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
            $pageSize = $this->input->get('pageSize') ? (int) $this->input->get('pageSize') : 10;
            $sortField = $this->input->get('sortField') ?: 'date';
            $sortOrder = $this->input->get('sortOrder') === 'DESC' ? 'DESC' : 'ASC';

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

    public function product_get($item_id = '')
    {

        
        try {
            $item_id = $item_id ?: $this->input->get('id');

            if (empty($item_id) || !is_numeric($item_id)) {
                throw new Exception('Item ID is required and must be numeric', 400);
            }

            $productInfo = $this->ItemStocksMov_model->get_product_info($item_id);

            if (empty($productInfo)) {
                throw new Exception('Product not found', 404);
            }

            $this->response([
                'success' => true,
                'data' => $productInfo
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}