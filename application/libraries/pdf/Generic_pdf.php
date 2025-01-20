<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Generic_pdf extends App_pdf
{
    

    public function __construct($content)
    {
        
        parent::__construct();
        $this->SetTitle('titulo');
        $this->SetDisplayMode('default', 'OneColumn');

    }

    public function prepare()
    {
        $number_word_lang_rel_id = 'unknown';

        if ($this->proposal->rel_type == 'customer') {
            $number_word_lang_rel_id = $this->proposal->rel_id;
        }

        $this->with_number_to_word($number_word_lang_rel_id);

        $total = '';
       

        $this->set_view_vars([
            'number'       => 123,
            'proposal'     => '456',
            'total'        => $total,
            'proposal_url' => site_url('generic/' . $this->proposal->id . '/' . $this->proposal->hash),
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'generic';
    }

    protected function file_path()
    {
         $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/genericpdf.php';

       

        return $actualPath;
    }
}
