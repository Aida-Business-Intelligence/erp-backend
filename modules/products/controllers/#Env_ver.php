<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Env_ver extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        show_404();
    }

}

// End of file Env_ver.php
// Location: ./application/controllers/Env_ver.php
