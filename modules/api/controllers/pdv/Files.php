<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Files extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Files_model');
    }

    public function create_post()
{
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $content_type = isset($this->input->request_headers()['Content-Type'])
        ? $this->input->request_headers()['Content-Type']
        : (isset($this->input->request_headers()['content-type'])
            ? $this->input->request_headers()['content-type']
            : null);

    $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

    $input = []; // Inicializa $input
    if ($is_multipart) {
        log_activity('File Create Input (multipart - processing $_FILES)');
    } else {
        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        log_activity('File Create Input (json): ' . json_encode($input));
    }

    $folder_id = $is_multipart ? ($this->post('folder_id') ? (int) $this->post('folder_id') : null) : ($input['folder_id'] ?? null);
    $is_favorite = $is_multipart ? ($this->post('is_favorite') ? (bool) $this->post('is_favorite') : false) : ($input['is_favorite'] ?? false);

    if ($folder_id !== null && !$this->Files_model->folder_exists($folder_id)) {
        $this->response([
            'status' => false,
            'message' => 'Folder with provided ID does not exist'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $uploaded_files_info = [];
    $errors = [];

    $upload_folder_name = ($folder_id !== null) ? $folder_id : '_root_';

    if ($is_multipart && isset($_FILES['file'])) {
        $files_data = $_FILES['file'];

        // Normaliza o array $_FILES para lidar com múltiplos arquivos ou um único arquivo
        $normalized_files = [];
        if (is_array($files_data['name'])) {
            $file_count = count($files_data['name']);
            for ($i = 0; $i < $file_count; $i++) {
                $normalized_files[] = [
                    'name' => $files_data['name'][$i],
                    'type' => $files_data['type'][$i],
                    'tmp_name' => $files_data['tmp_name'][$i],
                    'error' => $files_data['error'][$i],
                    'size' => $files_data['size'][$i],
                ];
            }
        } else {
            $normalized_files[] = $files_data;
        }

        foreach ($normalized_files as $file) {
            $file_name = $file['name'];
            $file_type = $file['type'];
            $file_size = $file['size'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = ['file' => $file_name, 'message' => 'Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX'];
                    continue;
                }

                $max_size = 5 * 1024 * 1024;
                if ($file_size > $max_size) {
                    $errors[] = ['file' => $file_name, 'message' => 'File is too large. Maximum size: 5MB'];
                    continue;
                }

                $upload_dir = './uploads/files_manager/' . $upload_folder_name . '/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $server_url = base_url();
                    $relative_path = str_replace('./', '', $upload_path);
                    $file_path = rtrim($server_url, '/') . '/' . $relative_path;

                    $result_id = $this->Files_model->create(
                        $file_name,
                        $file_type,
                        $file_size,
                        $folder_id,
                        $file_path,
                        $is_favorite
                    );

                    if ($result_id) {
                        $uploaded_files_info[] = [
                            'id' => $result_id,
                            'name' => $file_name,
                            'type' => $file_type, // Adicionado
                            'size' => $file_size, // Adicionado
                            'file_path' => $file_path,
                            'folder_id' => $folder_id, // Adicionado
                            'is_favorite' => $is_favorite, // Adicionado
                            'status' => 'success'
                        ];
                    } else {
                        $errors[] = ['file' => $file_name, 'message' => 'Failed to save file info to database'];
                    }
                } else {
                    log_activity('Failed to move uploaded file ' . $file_name . ' for folder ' . $upload_folder_name);
                    $errors[] = ['file' => $file_name, 'message' => 'Failed to move uploaded file'];
                }
            } else {
                $errors[] = ['file' => $file_name, 'message' => 'Upload error: ' . $file['error']];
            }
        }
    } else if (!$is_multipart) { // Este bloco é para JSON payload, sem upload de arquivo físico
        $name_json = $input['name'] ?? null;
        $type_json = $input['type'] ?? null;
        $size_json = $input['size'] ?? 0;

        if (!$name_json || !$type_json) {
            $this->response([
                'status' => false,
                'message' => 'Name and type are required for JSON payload'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $file_path = $input['file_path'] ?? null; // Assume que file_path pode vir do JSON se for o caso
        $result_id = $this->Files_model->create($name_json, $type_json, $size_json, $folder_id, $file_path, $is_favorite);

        if ($result_id) {
            $this->response([
                'status' => true,
                'message' => 'File record created successfully (JSON payload)',
                'id' => $result_id,
                'name' => $name_json,   // Adicionado
                'type' => $type_json,   // Adicionado
                'size' => $size_json,   // Adicionado
                'folder_id' => $folder_id, // Adicionado
                'is_favorite' => $is_favorite, // Adicionado
                'file_path' => $file_path
            ], REST_Controller::HTTP_CREATED);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Failed to create file record (JSON payload)'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
        return;
    } else { // Caso não seja multipart e não tenha $_FILES (e não seja JSON payload)
        $this->response([
            'status' => false,
            'message' => 'No files were provided for upload, or an invalid request format was used.'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    // Respostas finais para requisições multipart/form-data
    if (empty($uploaded_files_info) && !empty($errors)) {
        $this->response([
            'status' => false,
            'message' => 'Some files failed to upload.',
            'errors' => $errors
        ], REST_Controller::HTTP_BAD_REQUEST);
    } elseif (!empty($uploaded_files_info)) {
        $message = count($uploaded_files_info) . ' file(s) uploaded successfully.';
        if (!empty($errors)) {
            $message .= ' Some files had errors.';
        }
        $this->response([
            'status' => true,
            'message' => $message,
            'uploaded_files' => $uploaded_files_info,
            'errors' => $errors
        ], REST_Controller::HTTP_CREATED);
    } else {
        $this->response([
            'status' => false,
            'message' => 'No files were successfully uploaded.'
        ], REST_Controller::HTTP_BAD_REQUEST);
    }
}


    public function list_get()
{
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $order_by = $this->get('order_by') ?? 'created_at';
    $order_direction = $this->get('order_direction') ?? 'desc';
    $folder_id = $this->get('folder_id') ?? null;
    $search = $this->get('search') ? trim($this->get('search')) : null;

    $limit = $this->get('limit') ? (int)$this->get('limit') : null;
    $page = $this->get('page') ? (int)$this->get('page') : 1;

    $offset = null;
    if ($limit !== null && $page > 0) {
        $offset = ($page - 1) * $limit;
    }

    $allowed_order_by = ['created_at', 'updated_at', 'name', 'size'];
    $allowed_order_direction = ['asc', 'desc'];

    if (!in_array($order_by, $allowed_order_by)) {
        $this->response([
            'status' => false,
            'message' => 'Parâmetro order_by inválido. Valores permitidos: ' . implode(', ', $allowed_order_by)
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if (!in_array(strtolower($order_direction), $allowed_order_direction)) {
        $this->response([
            'status' => false,
            'message' => 'Parâmetro order_direction inválido. Valores permitidos: asc, desc'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if ($folder_id !== null) {
        if (!is_numeric($folder_id) || !ctype_digit(strval($folder_id)) || $folder_id <= 0) {
            $this->response([
                'status' => false,
                'message' => 'Parâmetro folder_id inválido. Deve ser um número inteiro positivo.'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        if (!$this->Files_model->folder_exists($folder_id)) {
            $this->response([
                'status' => false,
                'message' => 'Pasta com o ID fornecido não existe'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }
    }

    if ($limit !== null && (!is_numeric($limit) || $limit <= 0)) {
        $this->response([
            'status' => false,
            'message' => 'Parâmetro limit inválido. Deve ser um número inteiro positivo.'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if (!is_numeric($page) || $page <= 0) {
        $this->response([
            'status' => false,
            'message' => 'Parâmetro page inválido. Deve ser um número inteiro positivo.'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $total_files = $this->Files_model->count_files($folder_id, $search);

    $files = $this->Files_model->get_files($order_by, $order_direction, $folder_id, $search, $limit, $offset);

    $response_data = [
        'status' => true,
        'message' => 'Arquivos recuperados com sucesso',
        'total_files' => $total_files,
        'data' => []
    ];

    if ($limit !== null) {
        $total_pages = ceil($total_files / $limit);
        $response_data['pagination'] = [
            'current_page' => $page,
            'items_per_page' => $limit,
            'total_pages' => (int)$total_pages,
            'has_next_page' => ($page < $total_pages),
            'has_previous_page' => ($page > 1)
        ];
    }

    if ($files) {
        $formatted_files = array_map(function ($file) {
            $file['mime_type'] = $file['type'];
            $file['type'] = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file['is_favorite'] = (bool)$file['is_favorite'];
            return $file;
        }, $files);

        $response_data['data'] = $formatted_files;
    }

    $this->response($response_data, REST_Controller::HTTP_OK);
}


    public function id_get($id)
    {
        if (!is_numeric($id)) {
            $this->response([
                'status' => false,
                'message' => 'Invalid file ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $file = $this->Files_model->get_file_by_id($id);

        if ($file) {
            $file['is_favorite'] = (bool) $file['is_favorite'];
            $this->response([
                'status' => true,
                'message' => 'File retrieved successfully',
                'data' => $file
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'File not found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function folder_get($folder_id)
    {
        if (!is_numeric($folder_id)) {
            $this->response([
                'status' => false,
                'message' => 'Invalid folder ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!$this->Files_model->folder_exists($folder_id)) {
            $this->response([
                'status' => false,
                'message' => 'Folder with provided ID does not exist'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $files = $this->Files_model->get_files_by_folder($folder_id);

        if ($files) {
            $files = array_map(function ($file) {
                $file['is_favorite'] = (bool) $file['is_favorite'];
                return $file;
            }, $files);

            $this->response([
                'status' => true,
                'message' => 'Files retrieved successfully for folder ' . $folder_id,
                'data' => $files
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'No files found for folder ' . $folder_id
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }


public function favorite_put($id)
{
    // Considerar que \modules\api\core\Apiinit::the_da_vinci_code('api'); é importante e deve permanecer.
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    if (!is_numeric($id)) {
        $this->response([
            'status' => false,
            'message' => 'Invalid file ID'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $file = $this->Files_model->get_file_by_id($id);
    if (!$file) {
        $this->response([
            'status' => false,
            'message' => 'File not found'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
    }

    $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
    if (!isset($input['is_favorite']) || !is_bool($input['is_favorite'])) {
        $this->response([
            'status' => false,
            'message' => 'is_favorite must be a boolean (true or false)'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    // --- Início da correção para o BUG do erro 500 ---
    $current_is_favorite = (bool) $file['is_favorite']; // Garante que é um booleano
    $new_is_favorite     = (bool) $input['is_favorite']; // Garante que é um booleano do input

    // Verifica se o valor atual no banco de dados já é o mesmo que o valor enviado.
    if ($current_is_favorite === $new_is_favorite) {
        $this->response([
            'status' => true, // Operação considerada um sucesso, pois o estado desejado já foi atingido.
            'message' => 'File favorite status is already in the requested state. No update needed.'
        ], REST_Controller::HTTP_OK); // Retorna 200 OK
        return; // Termina a execução aqui
    }
    // --- Fim da correção ---


    if ($file['folder_id'] !== null && !$this->Files_model->folder_exists($file['folder_id'])) {
        $this->response([
            'status' => false,
            'message' => 'Associated folder for this file does not exist'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $result = $this->Files_model->update_file_favorite($id, $new_is_favorite); // Usa o $new_is_favorite castado

    if ($result) {
        $this->response([
            'status' => true,
            'message' => 'File favorite status updated successfully'
        ], REST_Controller::HTTP_OK);
    } else {
        // Se $result for false aqui, significa que a query de UPDATE foi executada,
        // mas nenhuma linha foi afetada. Isso pode acontecer se o ID não for encontrado
        // (o que já foi tratado), ou se por algum motivo o banco não registrou a mudança
        // (ex: problema de permissão, erro de sintaxe, etc.).
        // É importante que o erro 500 ocorra APENAS se houver uma falha real na operação.
        $this->response([
            'status' => false,
            'message' => 'Failed to update favorite status due to a database issue.'
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    public function delete_delete($id)
{
    if (!is_numeric($id)) {
        $this->response([
            'status' => false,
            'message' => 'Invalid file ID'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $file = $this->Files_model->get_file_by_id($id);

    if (!$file) {
        $this->response([
            'status' => false,
            'message' => 'File not found'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
    }

    // Remove base_url() do caminho se estiver presente para obter o caminho relativo ao FCPATH
    // Esta linha assume que $file['file_path'] pode conter a base_url.
    // Se o $file['file_path'] já for relativo ao FCPATH (ex: uploads/files_manager/pasta/arquivo.txt),
    // você pode simplificar para: $relative_path_from_base_url = $file['file_path'];
    $relative_path_from_base_url = str_replace(base_url(), '', $file['file_path']);
    $physical_file_path = FCPATH . $relative_path_from_base_url;

    // Extrai o diretório pai do arquivo
    $file_directory = dirname($physical_file_path);

    // Primeiro, tenta deletar o arquivo físico
    if (file_exists($physical_file_path)) {
        if (!unlink($physical_file_path)) {
            log_activity('Failed to delete physical file: ' . $physical_file_path);
            $this->response([
                'status' => false,
                'message' => 'Failed to delete physical file'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }
    } else {
        log_activity('Physical file not found, but proceeding to delete DB record: ' . $physical_file_path);
    }

    // Em seguida, tenta deletar o registro no banco de dados
    $result = $this->Files_model->delete_file($id);

    if ($result) {
        // --- Lógica para remover a pasta vazia ---
        // Verifica se o diretório pai existe, se é um diretório e se não é o diretório raiz de uploads.
        // realpath() ajuda a garantir a comparação correta de caminhos absolutos.
        $base_upload_dir = realpath(FCPATH . 'uploads/files_manager');

        if (is_dir($file_directory) && $file_directory !== $base_upload_dir) {
            // Conta o número de arquivos e diretórios dentro da pasta
            // O uso de GLOB_BRACE ou GLOB_MARK pode ser necessário dependendo dos tipos de itens que você espera.
            // Para simplicidade, usamos o padrão.
            $items_in_dir = array_diff(scandir($file_directory), array('.', '..')); // Remove '.' e '..'

            if (empty($items_in_dir)) { // Se a pasta estiver vazia
                if (!rmdir($file_directory)) {
                    log_activity('Failed to delete empty directory: ' . $file_directory);
                    // Não impedimos o sucesso da exclusão do arquivo se a pasta não puder ser removida
                } else {
                    log_activity('Successfully deleted empty directory: ' . $file_directory);
                }
            }
        }
        // --- Fim da lógica para remover a pasta vazia ---

        $this->response([
            'status' => true,
            'message' => 'File deleted successfully'
        ], REST_Controller::HTTP_OK);
    } else {
        $this->response([
            'status' => false,
            'message' => 'Failed to delete file from database'
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
}




public function bulk_delete_by_folder_delete($folder_id)
{
    if (!is_numeric($folder_id)) {
        $this->response([
            'status' => false,
            'message' => 'Invalid folder ID'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    // A verificação inicial no banco de dados para a existência da pasta.
    // Se a pasta nem mesmo existe no DB, o recurso é "não encontrado".
    if (!$this->Files_model->folder_exists($folder_id)) {
        $this->response([
            'status' => false, // status: false porque o recurso não foi encontrado
            'message' => 'Folder with provided ID not found in the database.'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
    }

    // Agora, chama o modelo para tentar deletar os arquivos e a pasta.
    $result = $this->Files_model->delete_files_by_folder($folder_id);

    // O Model retornará 'status' => true se conseguiu deletar algo,
    // ou se determinou que já estava "limpo" (nada a fazer).
    // A chave é a 'code_status' que o Model vai adicionar.
    if (isset($result['http_status'])) {
        $http_status = $result['http_status'];
        unset($result['http_status']); // Remove a chave HTTP status antes de enviar a resposta
    } else {
        $http_status = $result['status'] ? REST_Controller::HTTP_OK : REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
    }

    $this->response($result, $http_status);
}


    public function chart_get()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $chart_data = [];

        // Obter dados semanais
        $weekly_stats = $this->Files_model->get_weekly_stats(); // Chamando o Files_model
        $chart_data[] = [
            'name' => 'Semanal',
            'categories' => $weekly_stats['categories'],
            'data' => $weekly_stats['data'],
        ];

        // Obter dados mensais
        $monthly_stats = $this->Files_model->get_monthly_stats(); // Chamando o Files_model
        $chart_data[] = [
            'name' => 'Mensal',
            'categories' => $monthly_stats['categories'],
            'data' => $monthly_stats['data'],
        ];

        // Obter dados anuais
        $yearly_stats = $this->Files_model->get_yearly_stats(); // Chamando o Files_model
        $chart_data[] = [
            'name' => 'Anual',
            'categories' => $yearly_stats['categories'],
            'data' => $yearly_stats['data'],
        ];

        $response_data = [
            'chart' => $chart_data
        ];

        $this->response($response_data, REST_Controller::HTTP_OK);
    }


}
