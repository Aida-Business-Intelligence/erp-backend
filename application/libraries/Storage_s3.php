<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// Carregar o autoload do Composer
require_once APPPATH . '../vendor/autoload.php';
use Aws\S3\S3Client;

class Storage_s3 {
    protected $client;

    public function __construct() {
        $region = getenv('STORAGE_S3_REGION') ?: 'sf03';
        $endpoint = getenv('STORAGE_S3_ENDPOINT') ?: 'https://sfo3.digitaloceanspaces.com';
        $key = getenv('STORAGE_S3_KEY') ?: 'DO00J2C4EJFR3CE2EMDX';
        $secret = getenv('STORAGE_S3_SECRET') ?: '5GwUlxj7D4jlsFkDPiyjuWAIdZjeX6at17qgf/WRDvk';

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
            'bucket_endpoint' => false,
        ]);
    }

    public function getClient() {
        return $this->client;
    }
}
