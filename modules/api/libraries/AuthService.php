<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Description of AuthServiceJwt
 *
 * @author Estudo programação
 */
class AuthService {

    private $CI;
    private $jwtKey;
    private $jwtAlgorithm;
    public $user;

    public function __construct() {
        // Obter instância do CodeIgniter
        $this->CI = & get_instance();
        // Carregar configurações ou definir diretamente
        $this->jwtKey = $this->CI->config->item('jwt_key');
        $this->jwtAlgorithm = $this->CI->config->item('jwt_algorithm');
    }

    public function decodeToken($token) {
   


       $jwtToken = str_replace('Bearer ', '', $token); // Se necessário

    try {
        $decoded = JWT::decode($jwtToken, new Key($this->jwtKey, $this->jwtAlgorithm));
        $this->user = $decoded->user;
        return ['status' => true, 'data' => $decoded];
    } catch (ExpiredException $e) {
        return ['status' => false, 'error' => 'Token expirado'];
    } catch (SignatureInvalidException $e) {
        return ['status' => false, 'error' => 'Assinatura inválida'];
    } catch (\Exception $e) {
        return ['status' => false, 'error' => 'Token inválido', 'message' => $e->getMessage()];
    }
    }

    public function getUserId() {
        if ($this->user) {
            return $this->user->id ?? null;
        }
        return null;
    }

    public function yourEndpointJwt() {
        $token = $this->input->get_request_header('Authorization');

        if ($token) {
            // Remove 'Bearer ' do token recebido se necessário
            $token = str_replace('Bearer ', '', $token);
            $data = $this->decodeJWT($token);

            // Trabalhe com os dados decodificados (por exemplo, retornar resposta)
            $this->output
                    ->set_content_type('application/json')
                    ->set_output($data);
        } else {
            $this->output
                    ->set_status_header(401)
                    ->set_output(json_encode(['error' => 'Token não fornecido']));
        }
    }
}
