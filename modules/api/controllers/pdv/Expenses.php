<?php

defined('BASEPATH') OR exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Expenses extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Expenses_model');
    }
    
    /**
     * @api {get} api/pdv/expenses/list Listar Despesas
     * @apiName ListExpenses
     * @apiGroup Expenses
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {Number} [page] Número da página
     * @apiParam {Number} [pageSize] Itens por página
     * @apiParam {String} [search] Termo para busca
     * @apiParam {String} [sortField] Campo para ordenação
     * @apiParam {String} [sortOrder] Ordem (asc/desc)
     *
     * @apiSuccess {Boolean} status Status da requisição
     * @apiSuccess {Number} total Total de registros
     * @apiSuccess {Object[]} data Lista de despesas
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "total": 50,
     *       "data": [{
     *         "id": 1,
     *         "category": 1,
     *         "amount": "200.00",
     *         "date": "2024-03-21",
     *         "note": "Descrição da despesa"
     *       }]
     *     }
     */
    public function list_get() {

        // Pegando os parâmetros de entrada
        $page = $this->get('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->get('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->get('search') ?: ''; // Parâmetro de busca
        $sortField = $this->get('sortField') ?: db_prefix() . 'expenses.id'; // Ordenação padrão pelo campo 'id' da tabela de despesas
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, crescente por padrão

        // Configurando a query com base no contexto de despesas
        $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as taxrate,' . db_prefix() . 'taxes_2.name as tax_name2, ' . db_prefix() . 'taxes_2.taxrate as taxrate2, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'expenses.addedfrom as addedfrom, recurring_from');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
        $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');

        // Aplicando busca, se houver
        if (!empty($search)) {
            $this->db->like('expenses.description', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('expenses_categories.name', $search);
        }

        // Aplicando ordenação
        $this->db->order_by($sortField, $sortOrder);

        // Definindo o limite e o offset (página atual)
        $this->db->limit($limit, ($page - 1) * $limit);

        // Executando a consulta
        $data = $this->db->get()->result_array();

        // Verificando se há dados
        if (empty($data)) {
            $this->response(['status' => FALSE, 'message' => 'Nenhum dado foi encontrado'], REST_Controller::HTTP_NOT_FOUND);
        } else {
            // Pegando o total de resultados
            $this->db->from(db_prefix() . 'expenses');
            if (!empty($search)) {
                $this->db->like('expenses.description', $search);
                $this->db->or_like('clients.company', $search);
                $this->db->or_like('expenses_categories.name', $search);
            }
            $total = $this->db->count_all_results();

            $this->response(['status' => TRUE, 'total' => $total, 'data' => $data], REST_Controller::HTTP_OK);
        }
    }

    /**
     * @api {get} api/pdv/expenses/list_by_date Listar Despesas por Data
     * @apiName ListExpensesByDate
     * @apiGroup Expenses
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {String} [start_date] Data inicial (YYYY-MM-DD)
     * @apiParam {String} [end_date] Data final (YYYY-MM-DD)
     * @apiParam {Number} [page] Número da página
     * @apiParam {Number} [pageSize] Itens por página
     * @apiParam {String} [search] Termo para busca
     * @apiParam {String} [sortField] Campo para ordenação
     * @apiParam {String} [sortOrder] Ordem (asc/desc)
     *
     * @apiSuccess {Boolean} status Status da requisição
     * @apiSuccess {Number} total Total de registros
     * @apiSuccess {Object[]} data Lista de despesas
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "total": 10,
     *       "data": [{
     *         "id": 1,
     *         "category": 1,
     *         "amount": "200.00",
     *         "date": "2024-03-21"
     *       }]
     *     }
     */
    public function list_by_date_get() {
        $page = $this->get('page') ? (int) $this->get('page') : 0;
        $page = $page + 1;
        $limit = $this->get('pageSize') ? (int) $this->get('pageSize') : 10;
        $search = $this->get('search') ?: '';
        $sortField = $this->get('sortField') ?: db_prefix() . 'expenses.id';
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');

        $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as taxrate,' . db_prefix() . 'taxes_2.name as tax_name2, ' . db_prefix() . 'taxes_2.taxrate as taxrate2, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'expenses.addedfrom as addedfrom, recurring_from');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
        $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');

        if (!empty($start_date))
        {
            $this->db->where('date >=', $start_date);
        }
        if (!empty($end_date))
        {
            $this->db->where('date <=', $end_date);
        }

        if (!empty($search))
        {
            $this->db->group_start();
            $this->db->like('expenses.note', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('expenses_categories.name', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);

        $this->db->limit($limit, ($page - 1) * $limit);

        $data = $this->db->get()->result_array();

        if (empty($data))
        {
            $this->response(['status' => FALSE, 'message' => 'Nenhum dado foi encontrado'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->db->from(db_prefix() . 'expenses');
        if (!empty($search))
        {
            $this->db->like('expenses.note', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('expenses_categories.name', $search);
        }
        if (!empty($start_date))
        {
            $this->db->where('date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('date <=', $end_date);
        }
        $total = $this->db->count_all_results();

        $this->response(['status' => TRUE, 'total' => $total, 'data' => $data], REST_Controller::HTTP_OK);
    }


    private function format_currency($value) {
        return number_format($value, 2);
    }

    


    /**
     * @api {post} api/pdv/expenses/create Criar Nova Despesa
     * @apiName CreateExpense
     * @apiGroup Expenses
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {Number} category ID da categoria da despesa
     * @apiParam {Number} amount Valor da despesa
     * @apiParam {String} date Data da despesa (YYYY-MM-DD)
     * @apiParam {String} note Descrição da despesa
     * @apiParam {Number} [clientid] ID do cliente
     * @apiParam {Number} [paymentmode] ID do modo de pagamento
     * @apiParam {Number} [tax] ID do imposto 1
     * @apiParam {Number} [tax2] ID do imposto 2
     * @apiParam {String} [reference_no] Número de referência
     * @apiParam {Number} [currency] ID da moeda
     *
     * @apiSuccess {Boolean} status Status da requisição
     * @apiSuccess {String} message Mensagem de sucesso
     * @apiSuccess {Object} data Dados da despesa criada
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 201 Created
     *     {
     *       "status": true,
     *       "message": "Despesa criada com sucesso",
     *       "data": {
     *         "id": 1,
     *         "category": 1,
     *         "amount": "200.00",
     *         "date": "2024-03-21"
     *       }
     *     }
     */
    public function create_post() {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        // Recebendo e decodificando os dados
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Validações obrigatórias
        $this->form_validation->set_rules('category', 'Categoria', 'required');
        $this->form_validation->set_rules('amount', 'Valor', 'required|numeric');
        $this->form_validation->set_rules('date', 'Data', 'required');
        $this->form_validation->set_rules('note', 'Descrição', 'required');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        }
        else
        {
            // Preparando os dados para inserção
            $data = array(
                'category' => $this->input->post('category'),
                'amount' => $this->input->post('amount'),
                'date' => $this->input->post('date'),
                'note' => $this->input->post('note'),
                'clientid' => $this->input->post('clientid') ?? null,
                'paymentmode' => $this->input->post('paymentmode') ?? null,
                'tax' => $this->input->post('tax') ?? null,
                'tax2' => $this->input->post('tax2') ?? null,
                'reference_no' => $this->input->post('reference_no') ?? null,
                'currency' => $this->input->post('currency') ?? get_base_currency(),
                'addedfrom' => get_staff_user_id()
            );

            // Tentando criar a despesa
            $expense_id = $this->Expenses_model->add($data);

            if ($expense_id) {
                // Sucesso na criação
                $expense = $this->Expenses_model->get($expense_id);
                $message = array(
                    'status' => TRUE,
                    'message' => 'Despesa criada com sucesso',
                    'data' => $expense
                );
                $this->response($message, REST_Controller::HTTP_CREATED);
            }
            else
            {
                $message = array(
                    'status' => FALSE,
                    'message' => 'Falha ao criar despesa'
                );
                $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * @api {delete} api/delete/customers/:id Delete a Customer
     * @apiName DeleteCustomer
     * @apiGroup Customer
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Customer unique ID.
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Customer Delete Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Customer Delete Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Customer Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Customer Delete Fail."
     *     }
     */
    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);

        if (empty($id) || !is_numeric($id))
        {
            $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->load->model('Expenses_model');
        $output = $this->Expenses_model->delete($id);

        if (!$output)
        {
            $message = array('status' => FALSE, 'message' => 'Expense Delete Failed');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $message = array('status' => TRUE, 'message' => 'Expense Deleted Successfully');
        $this->response($message, REST_Controller::HTTP_OK);
    }




    /**
     * @api {put} api/pdv/expenses/data Atualizar Despesa
     * @apiName UpdateExpense
     * @apiGroup Expenses
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {Number} id ID da despesa
     * @apiParam {Number} [category] ID da categoria
     * @apiParam {Number} [amount] Valor da despesa
     * @apiParam {String} [date] Data da despesa (YYYY-MM-DD)
     * @apiParam {String} [note] Descrição da despesa
     * @apiParam {Number} [clientid] ID do cliente
     * @apiParam {Number} [paymentmode] ID do modo de pagamento
     * @apiParam {Number} [tax] ID do imposto 1
     * @apiParam {Number} [tax2] ID do imposto 2
     * @apiParam {String} [reference_no] Número de referência
     * @apiParam {Number} [currency] ID da moeda
     *
     * @apiSuccess {Boolean} status Status da requisição
     * @apiSuccess {String} message Mensagem de sucesso
     * @apiSuccess {Object} data Dados da despesa atualizada
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Despesa atualizada com sucesso",
     *       "data": {
     *         "id": 1,
     *         "category": 1,
     *         "amount": "200.00"
     *       }
     *     }
     */
    public function data_put() 
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) 
        {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }

        $this->form_validation->set_data($_POST);

        // Validando se o id foi enviado
        if (empty($_POST['id']) || !is_numeric($_POST['id']))
        {
            $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $update_data = $this->input->post();
        $expense_id = $_POST['id'];

        $this->load->model('Expenses_model');
        $output = $this->Expenses_model->update($update_data, $expense_id);

        if (!$output || empty($output))
        {
            $message = array('status' => FALSE, 'message' => 'Expenses Update Fail.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $message = array(
            'status' => TRUE,
            'message' => 'Expenses Update Successful.',
            'data' => $this->Expenses_model->get($expense_id)
        );
        $this->response($message, REST_Controller::HTTP_OK);
    }

    /**
     * @api {get} api/pdv/expenses/totals_by_period Totais por Período
     * @apiName GetTotalsByPeriod
     * @apiGroup Expenses
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {String} [start_date] Data inicial (YYYY-MM-DD)
     * @apiParam {String} [end_date] Data final (YYYY-MM-DD)
     *
     * @apiSuccess {Boolean} status Status da requisição
     * @apiSuccess {Object} data Dados dos totais
     * @apiSuccess {String} data.total_amount Valor total das despesas
     * @apiSuccess {Number} data.total_expenses Quantidade de despesas
     * @apiSuccess {Object} data.period Período consultado
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "data": {
     *         "total_amount": "5000.00",
     *         "total_expenses": 10,
     *         "period": {
     *           "start": "2024-01-01",
     *           "end": "2024-12-31"
     *         }
     *       }
     *     }
     */
    public function totals_by_period_get()
    {
        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');

        $this->db->select('SUM(amount) as total_amount, COUNT(*) as total_expenses');
        $this->db->from(db_prefix() . 'expenses');

        if (!empty($start_date))
        {
            $this->db->where('date >=', $start_date);
        }

        if (!empty($end_date))
        {
            $this->db->where('date <=', $end_date);
        }

        $result = $this->db->get()->row();

        if (empty($result) || $result->total_expenses == 0)
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Nenhuma despesa encontrada no período'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->response([
            'status' => TRUE,
            'data' => [
                'total_amount' => $result->total_amount,
                'total_expenses' => $result->total_expenses,
                'period' => [
                    'start' => $start_date ?? 'all',
                    'end' => $end_date ?? 'all'
                ]
            ]
        ], REST_Controller::HTTP_OK);
    }//http://localhost/aida/erp-backend/api/pdv/expenses/totals_by_period?start_date=2024-01-01&end_date=2024-12-31

    /**
     * @api {get} api/pdv/expenses/categories Listar Categorias
     * @apiName ListCategories
     * @apiGroup Expenses
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiSuccess {Boolean} status Status da requisição
     * @apiSuccess {Object[]} data Lista de categorias
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "data": [{
     *         "id": 1,
     *         "name": "Nome da Categoria"
     *       }]
     *     }
     *
     * @apiError {Boolean} status Status da requisição
     * @apiError {String} message Mensagem de erro
     * @apiError {String} [error] Detalhes do erro
     */
    public function categories_get()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        
        $this->load->model('expenses_model');
        
        try 
        {
            $categories = $this->expenses_model->get_category();

            if (empty($categories)) 
            {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Nenhuma categoria encontrada'
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $this->response([
                'status' => TRUE,
                'data' => $categories
            ], REST_Controller::HTTP_OK);
        } 
        catch (Exception $e) 
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao buscar categorias',
                'error' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }
    }
}
