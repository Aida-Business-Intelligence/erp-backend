<?php
defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Item_stocks_mov extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Item_stocks_mov_model');
    }
    public function movements_get($item_id = '')
    {
        try {
            // Log para debug
            log_message('debug', 'Item_stocks_mov::movements_get - Iniciando método');
            
            // Teste de conexão com o banco
            if (!$this->db->simple_query('SELECT 1')) {
                throw new Exception('Erro na conexão com o banco de dados', 500);
            }
            
            // Verificar se a tabela existe
            $table_exists = $this->db->table_exists(db_prefix() . 'itemstocksmov');
            if (!$table_exists) {
                throw new Exception('Tabela ' . db_prefix() . 'itemstocksmov não existe', 500);
            }
            
            $item_id = $item_id ?: $this->input->get('id');
            
            // Log para debug
            log_message('debug', 'Item_stocks_mov::movements_get - Item ID: ' . $item_id);

            if (empty($item_id) || !is_numeric($item_id)) {
                throw new Exception('Item ID is required and must be numeric', 400);
            }

            $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
            $pageSize = $this->input->get('pageSize') ? (int) $this->input->get('pageSize') : 10;
            $sortField = $this->input->get('sortField') ?: 'date';
            $sortOrder = $this->input->get('sortOrder') === 'DESC' ? 'DESC' : 'ASC';

            // Log para debug
            log_message('debug', 'Item_stocks_mov::movements_get - Parâmetros: page=' . $page . ', pageSize=' . $pageSize . ', sortField=' . $sortField . ', sortOrder=' . $sortOrder);

            $result = $this->Item_stocks_mov_model->get_movements_by_item($item_id, $page, $pageSize, $sortField, $sortOrder);

            // Log para debug
            log_message('debug', 'Item_stocks_mov::movements_get - Resultado obtido com sucesso');

            $this->response([
                'success' => true,
                'data' => $result['data'],
                'total' => $result['total']
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            // Log para debug
            log_message('error', 'Item_stocks_mov::movements_get - Erro: ' . $e->getMessage() . ' - Linha: ' . $e->getLine());
            
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

            $productInfo = $this->Item_stocks_mov_model->get_product_info($item_id);

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