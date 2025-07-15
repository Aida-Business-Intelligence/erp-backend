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

        if ($is_multipart) {
            $input = $this->input->post();
            log_activity('File Create Input (multipart): ' . json_encode($input));
        } else {
            $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
            log_activity('File Create Input (json): ' . json_encode($input));
        }

        $name = $is_multipart ? $this->post('name') : ($input['name'] ?? null);
        $type = $is_multipart ? $this->post('type') : ($input['type'] ?? null);
        $size = $is_multipart ? ($this->post('size') ? (int) $this->post('size') : 0) : ($input['size'] ?? 0);
        $folder_id = $is_multipart ? ($this->post('folder_id') ? (int) $this->post('folder_id') : null) : ($input['folder_id'] ?? null);

        if (!$name || !$type || !$folder_id) {
            $this->response([
                'status' => false,
                'message' => 'Name, type, and folder_id are required'
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

        $file_path = null;
        if ($is_multipart && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];

            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowed_types)) {
                $this->response([
                    'status' => false,
                    'message' => 'Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $max_size = 5 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $this->response([
                    'status' => false,
                    'message' => 'File is too large. Maximum size: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $upload_dir = './uploads/file_manager/' . $folder_id . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $server_url = base_url();
                $relative_path = str_replace('./', '', $upload_path);
                $file_path = rtrim($server_url, '/') . '/' . $relative_path;
            } else {
                log_activity('Failed to move uploaded file for folder ' . $folder_id);
                $this->response([
                    'status' => false,
                    'message' => 'Failed to upload file'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }
        }

        $result = $this->Files_model->create($name, $type, $size, $folder_id, $file_path);

        if ($result) {
            $this->response([
                'status' => true,
                'message' => 'File created successfully',
                'id' => $result,
                'file_path' => $file_path
            ], REST_Controller::HTTP_CREATED);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Failed to create file'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
