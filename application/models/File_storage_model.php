<?php

defined('BASEPATH') or exit('No direct script access allowed');

class File_storage_model extends App_Model // Ou CI_Model
{
    public function __construct()
    {
        parent::__construct();
        // Não precisa carregar o database aqui se App_Model já faz isso
    }

    /**
     * Retorna o mapeamento de tipos MIME para categorias de armazenamento.
     * Deve ser consistente com as categorias do seu componente (Images, Media, Documents, Other).
    //  */
    // private function get_mime_type_storage_categories() {
    //     return [
    //         'Images' => [
    //             'image/jpeg',
    //             'image/jpg',
    //             'image/png',
    //         ],
    //         'Media' => [
    //             'video/mp4',
    //             'audio/mpeg',
    //             // Adicione mais tipos MIME de mídia conforme necessário
    //         ],
    //         'Documents' => [
    //             'application/pdf',
    //             'application/msword',
    //             'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    //             'application/vnd.ms-excel',
    //             'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //             'text/plain',
    //             // Adicione mais tipos MIME de documentos conforme necessário
    //         ],
    //         // 'Other' será a categoria padrão para o que não se encaixa
    //     ];
    // }

    // /**
    //  * Mapeia um tipo MIME para uma categoria de armazenamento.
    //  */
    // private function map_mime_type_to_storage_category($mime_type) {
    //     $mime_type = strtolower($mime_type);
    //     $categories_map = $this->get_mime_type_storage_categories();

    //     foreach ($categories_map as $category_name => $mime_types_allowed) {
    //         if (in_array($mime_type, $mime_types_allowed)) {
    //             return $category_name;
    //         }
    //     }
    //     return 'Other'; // Categoria padrão
    // }

    // /**
    //  * Obtém o uso de armazenamento por categoria de arquivo e a contagem de arquivos.
    //  * Retorna o total de armazenamento usado, contagem total de arquivos, e dados por categoria.
    //  *
    //  * @param float $total_available_storage_gb O total de armazenamento disponível em GB (para cálculo de porcentagem).
    //  * @return array
    //  */
    // public function get_storage_overview($total_available_storage_gb) {
    //     $table_name = db_prefix() . 'files_manager';

    //     // Consulta para obter a soma do tamanho e a contagem por tipo MIME
    //     $this->db->select("type, SUM(size) as used_storage, COUNT(id) as files_count");
    //     $this->db->group_by("type");
    //     $query = $this->db->get($table_name);
    //     $results = $query->result_array();

    //     // Inicializa a estrutura de dados para as categorias
    //     $storage_data_by_category = [];
    //     $predefined_categories = array_keys($this->get_mime_type_storage_categories());
    //     $predefined_categories[] = 'Other'; // Garante que 'Other' esteja sempre presente

    //     foreach ($predefined_categories as $cat_name) {
    //         $storage_data_by_category[$cat_name] = [
    //             'name' => $cat_name,
    //             'usedStorage' => 0,
    //             'filesCount' => 0,
    //             'icon' => null, // O ícone será definido no Controller ou diretamente no front-end
    //         ];
    //     }

    //     $total_used_storage_bytes = 0;
    //     $total_files_count = 0;

    //     foreach ($results as $row) {
    //         $category = $this->map_mime_type_to_storage_category($row['type']);
            
    //         $used_storage_bytes = (int)$row['used_storage'];
    //         $files_count = (int)$row['files_count'];

    //         $storage_data_by_category[$category]['usedStorage'] += $used_storage_bytes;
    //         $storage_data_by_category[$category]['filesCount'] += $files_count;

    //         $total_used_storage_bytes += $used_storage_bytes;
    //         $total_files_count += $files_count;
    //     }

    //     // Definir ícones - É mais limpo fazer isso aqui ou no Controller
    //     // No PHP, você não pode retornar componentes React, então o ícone será um marcador para o front-end
    //     $storage_data_by_category['Images']['icon'] = 'ic-img.svg';
    //     $storage_data_by_category['Media']['icon'] = 'ic-video.svg';
    //     $storage_data_by_category['Documents']['icon'] = 'ic-document.svg';
    //     $storage_data_by_category['Other']['icon'] = 'ic-file.svg';

    //     // Calcular a porcentagem para chart.series
    //     // O componente usa `total * 2` como o máximo. `total` é o armazenamento disponível.
    //     // Então, o máximo para o cálculo da porcentagem é `total_available_storage_gb * 2`.
    //     $max_storage_for_percentage_bytes = $total_available_storage_gb * 1024 * 1024 * 1024 * 2; // Convertendo GB para Bytes e multiplicando por 2
        
    //     $percentage_used = 0;
    //     if ($max_storage_for_percentage_bytes > 0) {
    //         $percentage_used = round(($total_used_storage_bytes / $max_storage_for_percentage_bytes) * 100);
    //         // Garante que a porcentagem não exceda 100
    //         if ($percentage_used > 100) {
    //             $percentage_used = 100;
    //         }
    //     }
        
    //     // Formata os dados de categoria para um array indexado
    //     $formatted_data = array_values($storage_data_by_category);

    //     return [
    //         'total_used_storage_bytes' => $total_used_storage_bytes,
    //         'total_files_count' => $total_files_count,
    //         'chart_series_percentage' => $percentage_used,
    //         'data' => $formatted_data,
    //     ];
    // }




       /**
     * Retorna o mapeamento de tipos MIME para categorias de armazenamento.
     * Deve ser consistente com as categorias do seu componente (Images, Media, Documents, Other).
     */
    private function get_mime_type_storage_categories() {
        return [
            'Images' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
            ],
            'Media' => [
                'video/mp4',
                'audio/mpeg',
                // Adicione mais tipos MIME de mídia conforme necessário
            ],
            'Documents' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                // Adicione mais tipos MIME de documentos conforme necessário
            ],
            // 'Other' será a categoria padrão para o que não se encaixa
        ];
    }

    /**
     * Mapeia um tipo MIME para uma categoria de armazenamento.
     */
    private function map_mime_type_to_storage_category($mime_type) {
        $mime_type = strtolower($mime_type);
        $categories_map = $this->get_mime_type_storage_categories();

        foreach ($categories_map as $category_name => $mime_types_allowed) {
            if (in_array($mime_type, $mime_types_allowed)) {
                return $category_name;
            }
        }
        return 'Other'; // Categoria padrão
    }

    /**
     * Obtém o uso de armazenamento por categoria de arquivo e a contagem de arquivos.
     * Retorna o total de armazenamento usado, contagem total de arquivos, e dados por categoria.
     *
     * @return array
     */
    public function get_storage_overview_data() {
        $table_name = db_prefix() . 'files_manager';

        // Consulta para obter a soma do tamanho e a contagem por tipo MIME
        $this->db->select("type, SUM(size) as used_storage, COUNT(id) as files_count");
        $this->db->group_by("type");
        $query = $this->db->get($table_name);
        $results = $query->result_array();

        // Inicializa a estrutura de dados para as categorias
        $storage_data_by_category = [];
        $predefined_categories = array_keys($this->get_mime_type_storage_categories());
        $predefined_categories[] = 'Other'; // Garante que 'Other' esteja sempre presente

        foreach ($predefined_categories as $cat_name) {
            $storage_data_by_category[$cat_name] = [
                'name' => $cat_name,
                'usedStorage' => 0,
                'filesCount' => 0,
            ];
        }

        $total_used_storage_bytes = 0;
        $total_files_count = 0;

        foreach ($results as $row) {
            $category = $this->map_mime_type_to_storage_category($row['type']);
            
            $used_storage_bytes = (int)$row['used_storage'];
            $files_count = (int)$row['files_count'];

            $storage_data_by_category[$category]['usedStorage'] += $used_storage_bytes;
            $storage_data_by_category[$category]['filesCount'] += $files_count;

            $total_used_storage_bytes += $used_storage_bytes;
            $total_files_count += $files_count;
        }
        
        // Formata os dados de categoria para um array indexado
        $formatted_data = array_values($storage_data_by_category);

        return [
            'total_used_storage_bytes' => $total_used_storage_bytes,
            'total_files_count' => $total_files_count,
            'data' => $formatted_data,
        ];
    }
}