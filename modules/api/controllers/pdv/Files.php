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

        $name_base = $is_multipart ? $this->post('name') : ($input['name'] ?? null);
        $type_base = $is_multipart ? $this->post('type') : ($input['type'] ?? null);
        $size_base = $is_multipart ? ($this->post('size') ? (int) $this->post('size') : 0) : ($input['size'] ?? 0);
        $folder_id = $is_multipart ? ($this->post('folder_id') ? (int) $this->post('folder_id') : null) : ($input['folder_id'] ?? null);

        if (!$name_base || !$type_base || !$folder_id) {
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

        $uploaded_files_info = [];
        $errors = [];

        if ($is_multipart && isset($_FILES['file'])) {
            $files_data = $_FILES['file'];

            if (is_array($files_data['name'])) {
                $file_count = count($files_data['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    $file = [
                        'name' => $files_data['name'][$i],
                        'type' => $files_data['type'][$i],
                        'tmp_name' => $files_data['tmp_name'][$i],
                        'error' => $files_data['error'][$i],
                        'size' => $files_data['size'][$i],
                    ];

                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                        if (!in_array($file['type'], $allowed_types)) {
                            $errors[] = ['file' => $file['name'], 'message' => 'Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX'];
                            continue;
                        }

                        $max_size = 5 * 1024 * 1024;
                        if ($file['size'] > $max_size) {
                            $errors[] = ['file' => $file['name'], 'message' => 'File is too large. Maximum size: 5MB'];
                            continue;
                        }

                        $upload_dir = './uploads/files_manager/' . $folder_id . '/';
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

                            $result_id = $this->Files_model->create(
                                $file['name'],
                                $file['type'],
                                $file['size'],
                                $folder_id,
                                $file_path
                            );

                            if ($result_id) {
                                $uploaded_files_info[] = [
                                    'id' => $result_id,
                                    'name' => $file['name'],
                                    'file_path' => $file_path,
                                    'status' => 'success'
                                ];
                            } else {
                                $errors[] = ['file' => $file['name'], 'message' => 'Failed to save file info to database'];
                            }
                        } else {
                            log_activity('Failed to move uploaded file ' . $file['name'] . ' for folder ' . $folder_id);
                            $errors[] = ['file' => $file['name'], 'message' => 'Failed to move uploaded file'];
                        }
                    } else {
                        $errors[] = ['file' => $file['name'], 'message' => 'Upload error: ' . $file['error']];
                    }
                }
            } else {
                $file = $_FILES['file'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    if (!in_array($file['type'], $allowed_types)) {
                        $errors[] = ['file' => $file['name'], 'message' => 'Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX'];
                    } else {
                        $max_size = 5 * 1024 * 1024;
                        if ($file['size'] > $max_size) {
                            $errors[] = ['file' => $file['name'], 'message' => 'File is too large. Maximum size: 5MB'];
                        } else {
                            $upload_dir = './uploads/files_manager/' . $folder_id . '/';
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

                                $result_id = $this->Files_model->create(
                                    $file['name'],
                                    $file['type'],
                                    $file['size'],
                                    $folder_id,
                                    $file_path
                                );

                                if ($result_id) {
                                    $uploaded_files_info[] = [
                                        'id' => $result_id,
                                        'name' => $file['name'],
                                        'file_path' => $file_path,
                                        'status' => 'success'
                                    ];
                                } else {
                                    $errors[] = ['file' => $file['name'], 'message' => 'Failed to save file info to database'];
                                }
                            } else {
                                log_activity('Failed to move uploaded file for folder ' . $folder_id);
                                $errors[] = ['file' => $file['name'], 'message' => 'Failed to move uploaded file'];
                            }
                        }
                    }
                } else {
                    $errors[] = ['file' => $file['name'], 'message' => 'Upload error: ' . $file['error']];
                }
            }
        } else if ($is_multipart && !isset($_FILES['file'])) {
            $this->response([
                'status' => false,
                'message' => 'No files uploaded in multipart request'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        } else {

            $file_path = null;
            $result_id = $this->Files_model->create($name_base, $type_base, $size_base, $folder_id, $file_path);

            if ($result_id) {
                $this->response([
                    'status' => true,
                    'message' => 'File created successfully (JSON payload)',
                    'id' => $result_id,
                    'file_path' => $file_path
                ], REST_Controller::HTTP_CREATED);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Failed to create file (JSON payload)'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            return;
        }

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
                'message' => 'No files were provided for upload, or an unexpected error occurred.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function index_get()
    {
        $files = $this->Files_model->get_all();

        if ($files) {
            $this->response([
                'status' => true,
                'message' => 'Files retrieved successfully',
                'data' => $files
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'No files found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
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

    public function rename_put($id)
    {
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
        $new_name = $input['name'] ?? null;

        if (!$new_name) {
            $this->response([
                'status' => false,
                'message' => 'New name is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!$this->Files_model->folder_exists($file['folder_id'])) {
            $this->response([
                'status' => false,
                'message' => 'Associated folder for this file does not exist'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $result = $this->Files_model->update_file_name($id, $new_name);

        if ($result) {
            $this->response([
                'status' => true,
                'message' => 'File name updated successfully'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Failed to update file name or file not found'
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

        $upload_base_path = './uploads/files_manager/';
        $relative_path_from_base_url = str_replace(base_url(), '', $file['file_path']);
        $physical_file_path = FCPATH . $relative_path_from_base_url;

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

        $result = $this->Files_model->delete_file($id);

        if ($result) {
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
}
