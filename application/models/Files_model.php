<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Files_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }



    public function create($name, $type, $size, $folder_id = null, $file_path = null, $is_favorite = false)
{
    $data = [
        'name' => $name,
        'type' => $type,
        'size' => $size,
        'folder_id' => $folder_id,
        'is_favorite' => $is_favorite
    ];

    if ($file_path) {
        $data['file_path'] = $file_path;
    }

    $this->db->insert(db_prefix() . 'files_manager', $data);
    return $this->db->insert_id();
}


    public function get_file_by_id($id)
    {
        return $this->db->get_where(db_prefix() . 'files_manager', ['id' => $id])->row_array();
    }

    public function get_files_by_folder($folder_id)
    {
        return $this->db->get_where(db_prefix() . 'files_manager', ['folder_id' => $folder_id])->result_array();
    }

        public function get_files($order_by = 'created_at', $order_direction = 'desc', $folder_id = null, $search = null, $limit = null, $offset = null)
    {
        $allowed_orders = ['created_at', 'updated_at', 'name', 'size'];
        if (!in_array($order_by, $allowed_orders)) {
            $order_by = 'created_at';
        }

        $order_direction = (strtolower($order_direction) === 'asc') ? 'asc' : 'desc';

        if ($folder_id !== null) {
            $this->db->where('folder_id', $folder_id);
        }

        if ($search !== null) {
            $this->db->like('name', $search);
        }

        $this->db->order_by($order_by, $order_direction);

        if ($limit !== null && is_numeric($limit) && $limit > 0) {
            $this->db->limit($limit, $offset);
        }

        $query = $this->db->get(db_prefix() . 'files_manager');
        return $query->result_array();
    }

    public function count_files($folder_id = null, $search = null)
    {
        if ($folder_id !== null) {
            $this->db->where('folder_id', $folder_id);
        }

        if ($search !== null) {
            $this->db->like('name', $search);
        }

        return $this->db->count_all_results(db_prefix() . 'files_manager');
    }

    public function folder_exists($folder_id)
    {
        $this->db->where('id', $folder_id);
        $query = $this->db->get(db_prefix() . 'folders');
        return $query->num_rows() > 0;
    }


public function update_file_favorite($id, $is_favorite)
{
    $this->db->where('id', $id);
    $this->db->update(db_prefix() . 'files_manager', ['is_favorite' => (bool) $is_favorite]);
    return $this->db->affected_rows() > 0;
}

    public function delete_file($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'files_manager');

        return $this->db->affected_rows() > 0;
    }




public function delete_files_by_folder($folder_id)
{
    if (!is_numeric($folder_id)) {
        return [
            'status' => false,
            'message' => 'Invalid folder ID',
            'http_status' => REST_Controller::HTTP_BAD_REQUEST // Adiciona status HTTP para erro de input
        ];
    }

    // Obtém a lista de arquivos associados a esta pasta no banco de dados.
    $files = $this->get_files_by_folder($folder_id);
    
    $folder_path = FCPATH . 'uploads/files_manager/' . $folder_id . '/';
    $physical_folder_exists = file_exists($folder_path) && is_dir($folder_path);

    // --- Lógica para cenários de "já deletado" ou "nada a fazer" com status HTTP específico ---

    // Cenário 1: Não há arquivos associados no DB E a pasta física não existe.
    // Isso significa que tudo já foi removido. Retornar 404 Not Found para os arquivos.
    if (empty($files) && !$physical_folder_exists) {
        return [
            'status' => false, // Status false porque não encontrou arquivos para deletar
            'message' => 'No files found for this folder ID. Physical folder already removed or never existed.',
            'http_status' => REST_Controller::HTTP_NOT_FOUND
        ];
    }

    // Cenário 2: Não há arquivos associados no DB, mas a pasta física AINDA existe (pasta fantasma vazia).
    if (empty($files) && $physical_folder_exists) {
        try {
            $items_in_dir = array_diff(scandir($folder_path), array('.', '..'));
            if (empty($items_in_dir)) { // Confirma que está realmente vazia
                if (!is_writable($folder_path)) {
                    return [
                        'status' => false,
                        'message' => 'Folder is not writable to delete the empty folder.',
                        'debug' => [
                            'folder_path' => $folder_path,
                            'permissions' => substr(sprintf('%o', fileperms($folder_path)), -4)
                        ],
                        'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
                    ];
                }
                if (rmdir($folder_path)) {
                    log_activity('Successfully deleted empty folder: ' . $folder_path . ' (no DB files).');
                    // Retorna 200 OK, pois uma ação de deleção (da pasta vazia) ocorreu com sucesso.
                    return [
                        'status' => true,
                        'message' => 'No files found for this folder ID. Empty physical folder deleted successfully.',
                        'http_status' => REST_Controller::HTTP_OK
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => 'Failed to delete empty physical folder.',
                        'debug' => ['folder_path' => $folder_path],
                        'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
                    ];
                }
            } else {
                // Pasta existe fisicamente e não está vazia, mas não há registros no DB.
                // Isso é uma inconsistência grave, deve retornar erro.
                return [
                    'status' => false,
                    'message' => 'Folder exists physically and is not empty, but no files are registered for it in the database. Manual check required.',
                    'debug' => ['folder_path' => $folder_path, 'remaining_items' => $items_in_dir],
                    'http_status' => REST_Controller::HTTP_CONFLICT // 409 Conflict ou 500 Internal Server Error
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Error attempting to delete empty folder: ' . $e->getMessage(),
                'debug' => ['folder_path' => $folder_path, 'error_code' => $e->getCode()],
                'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }
    // --- Fim da nova Lógica para cenários de "já deletado" ---


    // --- Lógica de Deleção Padrão: Se ainda há arquivos no DB ---
    $all_physical_files_deleted = true;
    $db_records_deleted = 0;

    // Deleta arquivos físicos primeiro
    foreach ($files as $file) {
        $relative_path_from_base_url = str_replace(base_url(), '', $file['file_path']);
        $physical_file_path = FCPATH . $relative_path_from_base_url;
        
        if (file_exists($physical_file_path)) {
            if (!unlink($physical_file_path)) {
                log_activity('Failed to delete physical file: ' . $physical_file_path);
                $all_physical_files_deleted = false;
            }
        }
    }

    // Deleta registros do banco de dados (mesmo que alguns arquivos físicos falhem)
    $this->db->where('folder_id', $folder_id);
    $this->db->delete(db_prefix() . 'files_manager');
    $db_records_deleted = $this->db->affected_rows();
    
    // Tenta deletar a pasta física se todos os arquivos foram removidos e ela existe
    if ($all_physical_files_deleted) {
        try {
            if (file_exists($folder_path) && is_dir($folder_path)) {
                // Após a exclusão dos arquivos, verifica se a pasta está realmente vazia
                $items_in_dir_after_deletion = array_diff(scandir($folder_path), array('.', '..'));
                if (empty($items_in_dir_after_deletion)) {
                    if (!is_writable($folder_path)) {
                        return [
                            'status' => false,
                            'message' => 'Folder is not writable after file deletion for folder deletion.',
                            'debug' => [
                                'folder_path' => $folder_path,
                                'permissions' => substr(sprintf('%o', fileperms($folder_path)), -4)
                            ],
                            'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
                        ];
                    }
                    if (!rmdir($folder_path)) {
                        return [
                            'status' => false,
                            'message' => 'Failed to delete physical folder after file deletion.',
                            'debug' => ['folder_path' => $folder_path],
                            'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
                        ];
                    }
                    log_activity('Successfully deleted folder: ' . $folder_path . ' after files were removed.');
                } else {
                    return [
                        'status' => false,
                        'message' => 'Physical folder not empty after files deleted. Manual intervention may be required.',
                        'debug' => ['folder_path' => $folder_path, 'remaining_items' => $items_in_dir_after_deletion],
                        'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
                    ];
                }
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Error during final folder deletion: ' . $e->getMessage(),
                'debug' => ['folder_path' => $folder_path, 'error_code' => $e->getCode()],
                'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    } else {
        // Se alguns arquivos físicos não puderam ser deletados, a pasta não será removida.
        return [
            'status' => false,
            'message' => 'Failed to delete some physical files. Folder not removed.',
            'debug' => ['folder_path' => $folder_path],
            'http_status' => REST_Controller::HTTP_INTERNAL_SERVER_ERROR
        ];
    }

    // Retorno final de sucesso se chegamos até aqui
    return [
        'status' => true,
        'message' => 'All associated files and folder ' . $folder_id . ' deleted successfully.',
        'http_status' => REST_Controller::HTTP_OK
    ];
}


     // --- MÉTODOS DE ESTATÍSTICAS AJUSTADOS ---

    /**
     * Retorna o mapeamento de tipos MIME para categorias de gráfico.
     */
    private function get_mime_type_chart_categories() {
        return [
            'Imagens' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
            ],
            'Mídia' => [], // Adicione tipos MIME de vídeo/áudio aqui se necessário
            'Documentos' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }

    /**
     * Determina a categoria de gráfico de um arquivo com base em seu MIME type.
     */
    private function map_mime_type_to_category($mime_type) {
        $mime_type = strtolower($mime_type);
        $categories_map = $this->get_mime_type_chart_categories();

        foreach ($categories_map as $category_name => $mime_types_allowed) {
            if (in_array($mime_type, $mime_types_allowed)) {
                return $category_name;
            }
        }
        return 'Outros';
    }

    /**
     * Prepara a estrutura de dados para o gráfico com todas as categorias zeradas,
     * incluindo tanto os dados de tamanho quanto a quantidade.
     */
    private function prepare_chart_data_structure($categories_labels) {
        $chart_series_data = [];
        $file_categories = array_keys($this->get_mime_type_chart_categories());
        $file_categories[] = 'Outros';

        foreach ($file_categories as $category_name) {
            $chart_series_data[$category_name] = [
                'name' => $category_name,
                'data' => array_fill(0, count($categories_labels), 0), // Tamanhos (bytes)
                'dataValueQuantity' => array_fill(0, count($categories_labels), 0) // Quantidade de arquivos
            ];
        }
        return $chart_series_data;
    }

    /**
     * Obtém estatísticas de arquivos agrupadas por semana, incluindo soma de tamanhos e contagem.
     */
    public function get_weekly_stats() {
        $categories_labels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4', 'Semana 5'];
        $chart_series_data = $this->prepare_chart_data_structure($categories_labels);
        $table_name = db_prefix() . 'files_manager';

        // Modificado para selecionar 'size' e 'created_at' para cálculo
        $this->db->select("WEEK(created_at, 1) as week_num, type, SUM(size) as total_size, COUNT(id) as total_quantity");
        $this->db->where('created_at >= DATE_SUB(NOW(), INTERVAL 5 WEEK)');
        $this->db->group_by('week_num, type');
        $this->db->order_by('week_num ASC');
        $query = $this->db->get($table_name);
        $results = $query->result_array();

        $week_data_size = []; // Para armazenar a soma dos tamanhos por semana e categoria
        $week_data_quantity = []; // Para armazenar a contagem por semana e categoria

        // Inicializa as estruturas temporárias para garantir que todas as semanas e categorias estejam presentes
        $file_categories = array_keys($this->get_mime_type_chart_categories());
        $file_categories[] = 'Outros';

        // Define as semanas recentes para mapeamento correto, ajustando para o ano atual se necessário
        $recent_weeks_map = []; // week_num => index no array categories_labels
        $current_week = (int)date('W');
        $current_year = (int)date('Y');
        
        for ($i = 0; $i < 5; $i++) {
            $date = new DateTime();
            $date->modify("-$i week");
            $week_num = (int)$date->format('W');
            $year_num = (int)$date->format('Y');

            // Ajuste para virada de ano, se a semana do ano anterior for maior que a semana atual
            // Este é um tratamento simplificado e pode precisar de mais robustez para casos de borda complexos de semanas
            $adjusted_week_num = $week_num;
            // Se a semana calculada é maior que a semana atual e o ano é o anterior (ex: semana 52 de 2024 vs semana 1 de 2025)
            // Isso garante que a ordem das semanas recentes seja coerente
            if ($year_num < $current_year && $week_num > $current_week) {
                 // Adiciona um offset grande para semanas do ano anterior para ordenar corretamente
                $adjusted_week_num = $week_num + 100; // Offset arbitrário grande
            }

            $recent_weeks_map[$adjusted_week_num] = 4 - $i; // Mapeia a semana para o índice correto (0 a 4)
        }
        ksort($recent_weeks_map); // Garante que as semanas estejam ordenadas do mais antigo para o mais recente

        $categories_labels_ordered = [];
        $index_counter = 0;
        foreach ($recent_weeks_map as $adj_week => $original_index) {
            $date = new DateTime();
            $date->setISODate($current_year, $adj_week % 100); // Usa o ano atual e a semana ajustada (sem o offset)
            $categories_labels_ordered[$index_counter] = 'Semana ' . ($index_counter + 1);
            $index_counter++;
        }
        $categories_labels = array_values($categories_labels_ordered);


        foreach ($results as $row) {
            $category = $this->map_mime_type_to_category($row['type']);
            $week_num_db = (int)$row['week_num'];
            $year_num_db = (int)(new DateTime($row['created_at']))->format('Y');

            // Adaptação similar ao mapeamento das categorias_labels
            $adjusted_week_num_db = $week_num_db;
            if ($year_num_db < $current_year && $week_num_db > $current_week) {
                $adjusted_week_num_db = $week_num_db + 100;
            }

            if (isset($recent_weeks_map[$adjusted_week_num_db])) {
                $index = $recent_weeks_map[$adjusted_week_num_db];
                
                // Inicializa se ainda não existir
                if (!isset($week_data_size[$index])) {
                    $week_data_size[$index] = array_fill_keys($file_categories, 0);
                    $week_data_quantity[$index] = array_fill_keys($file_categories, 0);
                }
                $week_data_size[$index][$category] += (int)$row['total_size'];
                $week_data_quantity[$index][$category] += (int)$row['total_quantity'];
            }
        }
        
        // Popula o chart_series_data com os dados coletados
        foreach ($chart_series_data as $cat_name => &$series) {
            foreach ($categories_labels as $index => $label) { // Itera pelos índices das 5 semanas
                $series['data'][$index] = $week_data_size[$index][$cat_name] ?? 0;
                $series['dataValueQuantity'][$index] = $week_data_quantity[$index][$cat_name] ?? 0;
            }
        }

        return [
            'categories' => $categories_labels,
            'data' => array_values($chart_series_data)
        ];
    }


    /**
     * Obtém estatísticas de arquivos agrupadas por mês, incluindo soma de tamanhos e contagem.
     */
    public function get_monthly_stats() {
        $categories_labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $chart_series_data = $this->prepare_chart_data_structure($categories_labels);
        $table_name = db_prefix() . 'files_manager';

        // Modificado para selecionar 'size' e 'created_at' para cálculo
        $this->db->select("DATE_FORMAT(created_at, '%b') as month_abbr, MONTH(created_at) as month_num, type, SUM(size) as total_size, COUNT(id) as total_quantity");
        $this->db->where("created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)");
        $this->db->group_by("month_num, type");
        $this->db->order_by("month_num ASC");
        $query = $this->db->get($table_name);
        $results = $query->result_array();

        $temp_monthly_data_size = []; // Para armazenar a soma dos tamanhos por mês e categoria
        $temp_monthly_data_quantity = []; // Para armazenar a contagem por mês e categoria

        $file_categories = array_keys($this->get_mime_type_chart_categories());
        $file_categories[] = 'Outros';

        foreach ($results as $row) {
            $month_num = (int)$row['month_num'];
            $category = $this->map_mime_type_to_category($row['type']);
            
            // Inicializa se ainda não existir
            if (!isset($temp_monthly_data_size[$month_num])) {
                $temp_monthly_data_size[$month_num] = array_fill_keys($file_categories, 0);
                $temp_monthly_data_quantity[$month_num] = array_fill_keys($file_categories, 0);
            }
            $temp_monthly_data_size[$month_num][$category] += (int)$row['total_size'];
            $temp_monthly_data_quantity[$month_num][$category] += (int)$row['total_quantity'];
        }
        
        // Popula o chart_series_data com os dados coletados
        foreach ($categories_labels as $index => $month_abbr) {
            $month_num_current = $index + 1;
            
            foreach ($chart_series_data as $cat_name => &$series) {
                $series['data'][$index] = $temp_monthly_data_size[$month_num_current][$cat_name] ?? 0;
                $series['dataValueQuantity'][$index] = $temp_monthly_data_quantity[$month_num_current][$cat_name] ?? 0;
            }
        }

        return [
            'categories' => $categories_labels,
            'data' => array_values($chart_series_data)
        ];
    }

    /**
     * Obtém estatísticas de arquivos agrupadas por ano, incluindo soma de tamanhos e contagem.
     */
    public function get_yearly_stats() {
        $categories_labels = [];
        $current_year = (int)date('Y');
        $num_years = 6;
        $start_year = $current_year - ($num_years - 1); 

        for ($y = $start_year; $y <= $current_year; $y++) {
            $categories_labels[] = (string)$y;
        }

        $chart_series_data = $this->prepare_chart_data_structure($categories_labels);
        $table_name = db_prefix() . 'files_manager';

        // Modificado para selecionar 'size' e 'created_at' para cálculo
        $this->db->select("YEAR(created_at) as year_num, type, SUM(size) as total_size, COUNT(id) as total_quantity");
        $this->db->where("YEAR(created_at) >= {$start_year}");
        $this->db->group_by("year_num, type");
        $this->db->order_by("year_num ASC");
        $query = $this->db->get($table_name);
        $results = $query->result_array();

        $temp_yearly_data_size = []; // Para armazenar a soma dos tamanhos por ano e categoria
        $temp_yearly_data_quantity = []; // Para armazenar a contagem por ano e categoria

        $file_categories = array_keys($this->get_mime_type_chart_categories());
        $file_categories[] = 'Outros';

        foreach ($results as $row) {
            $year_num = (int)$row['year_num'];
            $category = $this->map_mime_type_to_category($row['type']);
            
            // Inicializa se ainda não existir
            if (!isset($temp_yearly_data_size[$year_num])) {
                $temp_yearly_data_size[$year_num] = array_fill_keys($file_categories, 0);
                $temp_yearly_data_quantity[$year_num] = array_fill_keys($file_categories, 0);
            }
            $temp_yearly_data_size[$year_num][$category] += (int)$row['total_size'];
            $temp_yearly_data_quantity[$year_num][$category] += (int)$row['total_quantity'];
        }
        
        // Popula o chart_series_data com os dados coletados
        foreach ($categories_labels as $index => $year_label) {
            $year_num_current = (int)$year_label;
            foreach ($chart_series_data as $cat_name => &$series) {
                $series['data'][$index] = $temp_yearly_data_size[$year_num_current][$cat_name] ?? 0;
                $series['dataValueQuantity'][$index] = $temp_yearly_data_quantity[$year_num_current][$cat_name] ?? 0;
            }
        }

        return [
            'categories' => $categories_labels,
            'data' => array_values($chart_series_data)
        ];
    }

}
