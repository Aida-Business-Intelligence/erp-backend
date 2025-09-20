<?php
defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Nfe extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nfe_model');

        // Autenticação nos mesmos moldes dos seus controllers
        $decodedToken = $this->authservice->decodeToken($this->token_jwt);
        if (!$decodedToken['status']) {
            $this->response([
                'status' => false,
                'message' => 'Usuário não autenticado'
            ], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }

        $this->user_id = (int) $decodedToken['data']->user->staffid;
    }

    /**
     * GET /api/nfe
     * Filtros via query string:
     *  page, limit, search, sortField, sortOrder, warehouse_id, status, start_date, end_date, invoice_id
     */
    public function index_get()
    {
        $page         = (int) $this->get('page') ?: 1;
        $limit        = (int) $this->get('limit') ?: 10;
        $search       = trim($this->get('search') ?: '');
        $sortField    = $this->get('sortField') ?: 'created_at';
        $sortOrder    = strtoupper($this->get('sortOrder')) === 'ASC' ? 'ASC' : 'DESC';
        $warehouse_id = (int) $this->get('warehouse_id') ?: 0;
        $status       = $this->get('status') ?: null;        // string ou array
        $start_date   = $this->get('start_date') ?: null;    // YYYY-mm-dd
        $end_date     = $this->get('end_date') ?: null;      // YYYY-mm-dd
        $invoice_id   = $this->get('invoice_id') ?: null;

        if ($warehouse_id <= 0) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $params = compact(
            'page','limit','search','sortField','sortOrder','warehouse_id',
            'status','start_date','end_date','invoice_id'
        );

        $data = $this->Nfe_model->list($params);

        return $this->response([
            'status' => true,
            'total'  => (int) $data['total'],
            'data'   => $data['rows'],
        ], REST_Controller::HTTP_OK);
    }

    /**
     * GET /api/nfe/{id}
     */
    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID inválido'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $row = $this->Nfe_model->get((int)$id);

        if (!$row) {
            return $this->response([
                'status' => false,
                'message' => 'NFe não encontrada'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response([
            'status' => true,
            'data'   => $row
        ], REST_Controller::HTTP_OK);
    }

    /**
     * POST /api/nfe/validate
     * body: { invoice_id, warehouse_id }
     * Valida se a fatura pode virar NFe e cria um registro com status "VALIDATED"
     */
    public function validate_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true);

        if (empty($_POST['invoice_id']) || empty($_POST['warehouse_id'])) {
            return $this->response([
                'status' => false,
                'message' => 'invoice_id e warehouse_id são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $this->db->trans_begin();

            $result = $this->Nfe_model->validate_invoice(
                (int) $_POST['invoice_id'],
                (int) $_POST['warehouse_id'],
                $this->user_id
            );

            if (!$result['status']) {
                $this->db->trans_rollback();
                return $this->response($result, REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->db->trans_commit();
            return $this->response([
                'status'  => true,
                'message' => 'Validação realizada',
                'nfe_id'  => $result['nfe_id']
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->response([
                'status' => false,
                'message' => 'Erro na validação: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/nfe/generate
     * body: { nfe_id } ou { invoice_id, warehouse_id }
     * Gera (stub) XML e chave; atualiza status para "GENERATED"
     */
    public function generate_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true);

        try {
            $this->db->trans_begin();

            $out = $this->Nfe_model->generate_nfe($_POST, $this->user_id);

            if (!$out['status']) {
                $this->db->trans_rollback();
                return $this->response($out, REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->db->trans_commit();
            return $this->response([
                'status'  => true,
                'message' => 'NFe gerada',
                'data'    => [
                    'nfe_id' => $out['nfe_id'],
                    'chave'  => $out['chave'],
                    'xml'    => $out['xml_preview'] // opcional: snippet (não o XML completo)
                ]
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->response([
                'status' => false,
                'message' => 'Erro ao gerar NFe: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/nfe/pdf/{id}
     * Retorna informação do PDF (URL) se existir
     */
    public function pdf_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID inválido'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $row = $this->Nfe_model->get((int)$id);

        if (!$row) {
            return $this->response([
                'status' => false,
                'message' => 'NFe não encontrada'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        if (empty($row['pdf_path'])) {
            return $this->response([
                'status' => false,
                'message' => 'PDF ainda não gerado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        // Devolvo apenas a URL (ou caminho) — baixar é com o front.
        return $this->response([
            'status'   => true,
            'pdf_path' => $row['pdf_path']
        ], REST_Controller::HTTP_OK);
    }

    /**
     * POST /api/nfe/remove
     * body: { rows: [ids...] }
     */
    public function remove_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true);

        if (empty($_POST['rows']) || !is_array($_POST['rows'])) {
            return $this->response([
                'status' => false,
                'message' => 'rows deve ser um array de IDs'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $ids = array_values(array_filter($_POST['rows'], 'is_numeric'));
        if (empty($ids)) {
            return $this->response([
                'status' => false,
                'message' => 'Nenhum ID válido informado'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $out = $this->Nfe_model->remove_many($ids);

        return $this->response($out, $out['status'] ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST);
    }
}
