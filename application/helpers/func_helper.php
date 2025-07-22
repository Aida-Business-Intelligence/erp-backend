<?php

use app\services\HtmlableText;

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('startsWith')) {

    /**
     * String starts with
     * @param  string $haystack
     * @param  string $needle
     * @return boolean
     */
    function startsWith($haystack, $needle)
    {
        return \app\services\utilities\Str::startsWith($haystack, $needle);
    }

}

if (!function_exists('endsWith')) {

    /**
     * String ends with
     * @param  string $haystack
     * @param  string $needle
     * @return boolean
     */
    function endsWith($haystack, $needle)
    {
        return \app\services\utilities\Str::endsWith($haystack, $needle);
    }

}

if (!function_exists('is_html')) {

    /**
     * Check if there is html in string
     */
    function is_html($string)
    {
        return \app\services\utilities\Str::isHtml($string);
    }

}

if (!function_exists('strafter')) {

    /**
     * Get string after specific charcter/word
     * @param  string $string    string from where to get
     * @param  substring $substring search for
     * @return string
     */
    function strafter($string, $substring)
    {
        return \app\services\utilities\Str::after($string, $substring);
    }

}

if (!function_exists('strbefore')) {

    /**
     * Get string before specific charcter/word
     * @param  string $string    string from where to get
     * @param  substring $substring search for
     * @return string
     */
    function strbefore($string, $substring)
    {
        return \app\services\utilities\Str::before($string, $substring);
    }

}

if (!function_exists('is_connected')) {

    /**
     * Is internet connection open
     * @param  string  $domain
     * @return boolean
     */
    function is_connected($domain = 'www.google.com')
    {
        return \app\services\utilities\Utils::isConnected($domain);
    }

}

if (!function_exists('str_lreplace')) {

    /**
     * Replace Last Occurence of a String in a String
     * @since  1.0.1
     * @param  string $search  string to be replaced
     * @param  string $replace replace with
     * @param  string $subject [the string to search
     * @return string
     */
    function str_lreplace($search, $replace, $subject)
    {
        return \app\services\utilities\Str::replaceLast($search, $replace, $subject);
    }

}

if (!function_exists('get_string_between')) {

    /**
     * Get string bettween words
     * @param  string $string the string to get from
     * @param  string $start  where to start
     * @param  string $end    where to end
     * @return string formatted string
     */
    function get_string_between($string, $start, $end)
    {
        return \app\services\utilities\Str::between($string, $start, $end);
    }

}

if (!function_exists('time_ago_specific')) {

    /**
     * Format datetime to time ago with specific hours mins and seconds
     * @param  datetime $lastreply
     * @param  string $from      Optional
     * @return mixed
     */
    function time_ago_specific($date, $from = 'now')
    {
        return \app\services\utilities\Date::timeAgo($date, $from);
    }

}

if (!function_exists('sec2qty')) {

    /**
     * Format seconds to quantity
     * @param  mixed  $sec      total seconds
     * @return [integer]
     */
    function sec2qty($sec)
    {
        $qty = \app\services\utilities\Format::sec2qty($sec);

        return hooks()->apply_filters('sec2qty_formatted', $qty, $sec);
    }

}

if (!function_exists('seconds_to_time_format')) {

    /**
     * Format seconds to H:I:S
     * @param  integer $seconds         mixed
     * @param  boolean $include_seconds
     * @return string
     */
    function seconds_to_time_format($seconds = 0, $include_seconds = false)
    {
        return \app\services\utilities\Format::secondsToTime($seconds, $include_seconds);
    }

}

if (!function_exists('hours_to_seconds_format')) {

    /**
     * Converts hours to minutes timestamp
     * @param  mixed $hours     total hours in format HH:MM or HH.MMM
     * @return int
     */
    function hours_to_seconds_format($hours)
    {
        return \app\services\utilities\Format::hoursToSeconds($hours);
    }

}

if (!function_exists('ip_in_range')) {

    /**
     * Check whether ip is in range
     * @param  string $ip    ip address to check
     * @param  string $range range
     * @return boolean
     */
    function ip_in_range($ip, $range)
    {
        return \app\services\utilities\Utils::ipInRage($ip, $range);
    }

}

if (!function_exists('array_merge_recursive_distinct')) {

    /**
     * @since  2.3.4
     *
     * Array merge recursive distinct
     *
     * @param  array  &$array1
     * @param  array  &$array2
     * @return array
     */
    function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        return \app\services\utilities\Arr::merge_recursive_distinct($array1, $array2);
    }

}

if (!function_exists('array_to_object')) {

    /**
     * Convert array to oobject
     * @param  array $array array to convert
     * @return object
     */
    function array_to_object($array)
    {
        return \app\services\utilities\Arr::toObject($array);
    }

}

if (!function_exists('array_flatten')) {

    /**
     * Flatten multidimensional array
     * @param  array  $array
     * @return array
     */
    function array_flatten(array $array)
    {
        return \app\services\utilities\Arr::flatten($array);
    }

}

if (!function_exists('value_exists_in_array_by_key')) {

    /**
     * Check if value exist in array by key
     * @param  array $array
     * @param  string $key   key to check
     * @param  mixed $val   value
     * @return boolean
     */
    function value_exists_in_array_by_key($array, $key, $val)
    {
        return \app\services\utilities\Arr::valueExistsByKey($array, $key, $val);
    }

}

if (!function_exists('in_array_multidimensional')) {

    /**
     * Check if in array multidimensional
     * @param  array $array  array to perform the checks
     * @param  mixed $key    array key
     * @param  mixed $val    the value to check
     * @return boolean
     */
    function in_array_multidimensional($array, $key, $val)
    {
        return \app\services\utilities\Arr::inMultidimensional($array, $key, $val);
    }

}

if (!function_exists('in_object_multidimensional')) {

    /**
     * Check if in object multidimensional
     * @param  object $object  object to perform the checks
     * @param  mixed $key      object key
     * @param  mixed $val      the value to check
     * @return boolean
     */
    function in_object_multidimensional($object, $key, $val)
    {
        foreach ($object as $item) {
            if (isset($item->{$key}) && $item->{$key} == $val) {
                return true;
            }
        }

        return false;
    }

}

if (!function_exists('array_pluck')) {

    /**
     *
     * @param  $array - data
     * @param  $key - value you want to pluck from array
     *
     * @return plucked array only with key data
     */
    function array_pluck($array, $key)
    {
        return \app\services\utilities\Arr::pluck($array, $key);
    }

}

if (!function_exists('adjust_color_brightness')) {

    /**
     * Adjust color brightness
     * @param  string $hex   hex color to adjust from
     * @param  mixed $steps eq -20 or 20
     * @return string
     */
    function adjust_color_brightness($hex, $steps)
    {
        return \app\services\utilities\Utils::adjustColorBrightness($hex, $steps);
    }

}

if (!function_exists('hex2rgb')) {

    /**
     * Convert hex color to rgb
     * @param  string $color color hex code
     * @return string
     */
    function hex2rgb($color)
    {
        return \app\services\utilities\Utils::hex2rgb($color);
    }

}

if (!function_exists('process_text_content_for_display')) {

    /**
     * Process text content for display.
     *
     * @since 3.1.1
     *
     * @param string $text
     * @return string
     */
    function process_text_content_for_display($text)
    {
        return (new HtmlableText($text))->toHtml();
    }

}

if (!function_exists('check_for_links')) {

    /**
     * Check for links/emails/ftp in string to wrap in href
     * @param  string $text
     * @return string      formatted string with href in any found
     */
    function check_for_links($text)
    {
        if (empty($text)) {
            return $text;
        }

        // $text = htmlspecialchars_decode($text);

        return \app\services\utilities\Str::clickable($text);

        $regexPattern = '/<a\s+[^>]*href="([^"]*)"[^>]*>.*?<\/a>/';

        return preg_replace_callback($regexPattern, function ($matches) {
            return $matches[1];
        }, $text);
    }

}

if (!function_exists('time_ago')) {

    /**
     * Short Time ago function
     * @param  datetime $date
     * @return mixed
     */
    function time_ago($date)
    {
        $CI = &get_instance();

        $localization = [];

        foreach (['time_ago_just_now', 'time_ago_minute', 'time_ago_minutes', 'time_ago_hour', 'time_ago_hours', 'time_ago_yesterday', 'time_ago_days', 'time_ago_week', 'time_ago_weeks', 'time_ago_month', 'time_ago_months', 'time_ago_year', 'time_ago_years'] as $langKey) {
            if (isset($CI->lang->language[$langKey])) {
                $localization[$langKey] = $CI->lang->language[$langKey];
            }
        }

        return \app\services\utilities\Date::timeAgoString($date, $localization);
    }

}

if (!function_exists('slug_it')) {

    /**
     * Slug function
     * @param  string $str
     * @param  array  $options Additional Options
     * @return mixed
     */
    function slug_it($str, $options = [])
    {
        $defaults = ['lang' => get_option('active_language')];
        $settings = array_merge($defaults, $options);

        return \app\services\utilities\Str::slug($str, $settings);
    }

}

if (!function_exists('similarity')) {

    /**
     * Check 2 string similarity
     * @param  string $str1
     * @param  string $str2
     * @return float
     */
    function similarity($str1, $str2)
    {
        return \app\services\utilities\Str::similarity($str1, $str2);
    }

}

/**
 * @since  2.3.0
 * Sort array by position
 * @param  array  $array     the arry to sort
 * @param  boolean $keepIndex whether to keep the indexes
 * @return array
 */
function app_sort_by_position($array, $keepIndex = false)
{
    return \app\services\utilities\Arr::sortBy($array, 'position', $keepIndex);
}

/**
 * Fill common empty attributes used for various function e.q. menu, tabs etc...
 * This is used e.q. if user didn't added icon array attribute but there are no checks performed if(iseet($item['icon'])) to prevent
 * throwing errors.
 * @param  array $array
 * @return array
 */
function app_fill_empty_common_attributes($array)
{
    $array['icon'] = isset($array['icon']) ? $array['icon'] : '';

    $array['href'] = isset($array['href']) && $array['href'] != '' ? $array['href'] : '#';

    $array['position'] = isset($array['position']) ? $array['position'] : null;

    return $array;
}

/**
 * Function that strip all html tags from string/text/html
 * @param  string $str
 * @param  string $allowed prevent specific tags to be stripped
 * @return string
 */
function strip_html_tags($str, $allowed = '')
{
    $str = preg_replace('/(<|>)\1{2}/is', '', $str);

    $str = remove_html_invisible_tags($str);

    $str = preg_replace([
        // Add line breaks before and after blocks
        '@</?((address)|(blockquote)|(center)|(del))@iu',
        '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
        '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
        '@</?((table)|(th)|(td)|(caption))@iu',
        '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
        '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
        '@</?((frameset)|(frame)|(iframe))@iu',
    ], [
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
    ], $str);

    $str = strip_tags($str, $allowed);

    // Remove on events from attributes
    $re = '/\bon[a-z]+\s*=\s*(?:([\'"]).+?\1|(?:\S+?\(.*?\)(?=[\s>])))/i';
    $str = preg_replace($re, '', $str);

    $str = trim($str);
    $str = trim($str, '&nbsp;');
    $str = trim($str);

    return $str;
}

/**
 * Function that removes invisible tags from HTML string
 * 
 * @since 3.1.5
 * 
 * @param  string $str
 * 
 * @return string
 */
function remove_html_invisible_tags($str)
{
    return preg_replace([
        '@<head[^>]*?>.*?</head>@siu',
        '@<style[^>]*?>.*?</style>@siu',
        '@<script[^>]*?.*?</script>@siu',
        '@<object[^>]*?.*?</object>@siu',
        '@<embed[^>]*?.*?</embed>@siu',
        '@<applet[^>]*?.*?</applet>@siu',
        '@<noframes[^>]*?.*?</noframes>@siu',
        '@<noscript[^>]*?.*?</noscript>@siu',
        '@<noembed[^>]*?.*?</noembed>@siu',
    ], [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
    ], $str);
}

if (!function_exists('adjust_hex_brightness')) {

    function adjust_hex_brightness($hex, $percent)
    {
        // Work out if hash given
        $hash = '#';
        if (stristr($hex, '#')) {
            $hex = str_replace('#', '', $hex);
        }
        /// HEX TO RGB
        $rgb = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        //// CALCULATE
        for ($i = 0; $i < 3; $i++) {
            // See if brighter or darker
            if ($percent > 0) {
                // Lighter
                $rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1 - $percent));
            } else {
                // Darker
                $positivePercent = $percent - ($percent * 2);
                $rgb[$i] = round($rgb[$i] * (1 - $positivePercent)); // round($rgb[$i] * (1-$positivePercent));
            }
            // In case rounding up causes us to go to 256
            if ($rgb[$i] > 255) {
                $rgb[$i] = 255;
            }
        }
        //// RBG to Hex
        $hex = '';
        for ($i = 0; $i < 3; $i++) {
            // Convert the decimal digit to hex
            $hexDigit = dechex($rgb[$i]);
            // Add a leading zero if necessary
            if (strlen($hexDigit) == 1) {
                $hexDigit = '0' . $hexDigit;
            }
            // Append to the hex string
            $hex .= $hexDigit;
        }

        return $hash . $hex;
    }

}

function app_unserialize($data)
{
    $unserializeError = false;
    set_error_handler(function () use (&$unserializeError) {
        $unserializeError = true;
    });

    $unserialized = unserialize($data);

    if ($unserializeError) {
        $fixed = preg_replace_callback(
            '!s:\d+:"(.*?)";!s',
            function ($m) {
                return 's:' . strlen($m[1]) . ':"' . $m[1] . '";';
            },
            $data
        );

        $unserialized = unserialize($fixed);
    }

    restore_error_handler();

    return $unserialized;
}

function determine_color_type($hexColor)
{
    // Remove '#' if it exists
    $hexColor = ltrim($hexColor, '#');

    // Expand shorthand hex (e.g., 'abc' => 'aabbcc')
    if (strlen($hexColor) === 3) {
        $hexColor = str_repeat($hexColor[0], 2) .
            str_repeat($hexColor[1], 2) .
            str_repeat($hexColor[2], 2);
    }

    if (strlen($hexColor) !== 6) {
        return ['error' => 'Invalid hex color format'];
    }

    // Convert hex to RGB
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));

    // Calculate relative luminance
    $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

    // Convert luminance to percentage
    $percentage = round($luminance * 100, 2);

    // Determine light or dark
    $type = $luminance > 0.5 ? 'light' : 'dark';

    return [
        'type' => $type,
        'percentage' => $percentage
    ];
}



if (!function_exists('updateStock')) {

    function updateStock($data, $item, $transaction, $type = 'debit')
    {
        $CI = &get_instance();
        $id_itemstocks = update_itemstocks($item['qty'], $item['id'], $data['warehouse_id'], $type);


        $data_itemstocksmov = [
            'warehouse_id' => $data['warehouse_id'],
            'cash_id' => $transaction['id'], //$data['cash_id'],
            'qtde' => $item['qty'],
            'transaction_id' => $transaction['id'],
            'hash' => $data['hash'],
            'user_id' => $data['user_id'],
            'obs' => 'pagamento',
            'type_transaction' => $transaction['type']
        ];
        $CI->db->insert(db_prefix() . 'itemstocksmov', $data_itemstocksmov);
    }

}
if (!function_exists('update_itemstocks')) {

    function update_itemstocks($qtde, $item_id, $warehouse_id, $type)
    {
        $CI = &get_instance();

        // Retrieve the current quantity
        $CI->db->select('stock, id');
        $CI->db->from(db_prefix() . 'items');
        $CI->db->where('id', $item_id);
        $CI->db->where('warehouse_id', $warehouse_id);
        $query = $CI->db->get();

        if ($query->num_rows() > 0) {
            $row = $query->row();

            $currentQuantity = $row->stock;

            if ($type == 'debit') {
                $updatedQuantity = $currentQuantity - $qtde;
            } else {

                $updatedQuantity = $currentQuantity + $qtde;
            }

            // Update the quantity in the database
            $CI->db->where('id', $item_id);
            $CI->db->where('warehouse_id', $warehouse_id);
            $CI->db->set('stock', $updatedQuantity);
            $CI->db->update(db_prefix() . 'items');

            // Return the ID of the updated record
            return $row->id;
        } else {
            // Handle case where no record is found
            return false; // or handle as necessary
        }
    }

}

// Funcão para atualizar o estoque do item - Produtos do Ecommerce ao criar pedido
function updateStocks2($data, $item, $transaction)
{
    $CI = &get_instance();

    // Busca o item atual
    $CI->db->where('id', $item['id']);
    $CI->db->where('warehouse_id', $data['warehouse_id']);
    $current_item = $CI->db->get(db_prefix() . 'items')->row();

    if (!$current_item) {
        throw new Exception('Item não encontrado');
    }

    // Calcula o novo estoque
    $new_stock = $current_item->stock - $item['qty'];

    // Atualiza o estoque do item
    $CI->db->where('id', $item['id']);
    $CI->db->where('warehouse_id', $data['warehouse_id']);
    $CI->db->update(db_prefix() . 'items', ['stock' => $new_stock]);

    // Registra o movimento no itemstocksmov
    $movement_data = [
        'warehouse_id' => $data['warehouse_id'],
        'transaction_id' => $transaction['id'],
        'cash_id' => $transaction['id'],
        'qtde' => $item['qty'],
        'hash' => $data['hash'],
        'user_id' => $data['user_id'],
        'obs' => $data['obs'],
        'type_transaction' => $transaction['cash']
    ];

    $CI->db->insert(db_prefix() . 'itemstocksmov', $movement_data);

    return true;
}

if (!function_exists('gerarNFC')) {

    function gerarNFC($data, $pedido)
    {
        $CI = &get_instance();
        $CI->load->model('Warehouse_model');
        $CI->load->model('Clients_model');

        $items = [];
        $cont = 1;

        foreach ($data['newitems'] as $item) {

            $tributo = $CI->db->select('id, ncm, cfop as codigo_cfop, tax_percent as pICMS')->get(db_prefix() . 'items', ['id' => $item['id']])->row();


            $items[] = [
                'item' => $cont,
                'nome' => $item['description'],
                'ncm' => $tributo->ncm, // Adicione se necessário
'total' => ($item['qty'] * $item['rate']) * (1 - ($item['discount'] / 100)),
                'subtotal' => $item['subtotal'],
                'quantidade' => $item['qty'],
                'unidade' => $item['unit'],
                'impostos' => [
                    'icms' => [
                        'codigo_cfop' => $tributo->codigo_cfop,
                        'situacao_tributaria' => '102',
                        'modBC' => '3',
                        'pICMS' => $tributo->pICMS,
                        'origem' => '0'
                    ],
                    'ipi' => [
                        'situacao_tributaria' => '-1'
                    ]
                ]
            ];
            $cont++;
        }

        // Buscar detalhes do loja (warehouse)
        $loja = $CI->Warehouse_model->get($data['warehouse_id']);

        // Buscar cliente se o documento estiver definido
        if ($data['doc']) {
            $cliente = $CI->Clients_model->get_by_vat($data['doc']);

            $documento = $data['doc'];
            $nome = "";
            $email = "";

            if ($cliente) {
                $nome = $cliente['company'] ?? '';
                $email = $cliente['email_default'] ?? '';
            }
        } else {
            $documento = '';
            $nome = '';
            $email = '';
        }

        // Monta o JSON de envio
        $postData = [
            "emitente" => [
                "atualizacao" => date("Y-m-d H:i:s"),
                "tpAmb" => (int)$loja->tpAmb,
                "razaosocial" => $loja->razao_social,
                "cnpj" => preg_replace('/\D/', '', $loja->cnpj),
                "fantasia" => $loja->warehouse_name,
                "ie" => $loja->ie,
                "im" => $loja->im,
                "cnae" => preg_replace('/\D/', '', $loja->cnae),
                "crt" => $loja->crt,
                "rua" => $loja->endereco,
                "numero" => $loja->numero,
                "bairro" => $loja->bairro,
                "cidade" => $loja->cidade,
                "ccidade" => $loja->ccidade,
                "cep" => preg_replace('/\D/', '', $loja->cep),
                "siglaUF" => $loja->estado,
                "codigoUF" => $loja->codigoUF,
                "fone" => preg_replace('/\D/', '', $loja->telefone),
                "schemes" => "PL_009_V4",
                "versao" => "4.00",
                "tokenIBPT" => "01",
                "password_nfe" => $loja->password_nfe,
                "arquivo_nfe" => $loja->arquivo_nfe,
                "CSC" => $loja->csc,
                "CSCid" => $loja->cscid,
                "proxyConf" => [
                    "proxyIp" => "",
                    "proxyPort" => "",
                    "proxyUser" => "",
                    "proxyPass" => ""
                ],
                "situacao_tributaria" => "102"
            ],
            "warehouse_id" => $data['warehouse_id'],
            "parceiro_id" => getenv('NEXT_PUBLIC_CLIENT_MASTER_ID'),
            "consumidorfinal" => 1,
            "modelo" => "65",
            "impressao" => "4",
            "finalidade" => "1",
            "debug" => false,
            "cliente" => [
                "email" => $email,
                "tipoPessoa" => "F",
                "cpf" => $documento,
                "contato" => $nome
            ],
            "pedido" => [
                "id" => $pedido,
                "presenca" => "1",
                "forma_pagamento" => "01",
                "valor_pagamento" => number_format($data['total'], 2, '.', '') // Garante formato decimal
            ],
            "produtos" => $items // já é um array, será convertido para JSON na requisição
        ];

     

        // Converter o array para JSON
        $jsonData = json_encode($postData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Debug opcional
        // echo '<pre>'; var_dump($jsonData); exit;

        // Requisição cURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => getenv('NFE_BASE_URL') . '/gerador/Emissor.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

  

        if(curl_errno($curl)){
            // Aqui é importante tratar erro de conexão
            $error_msg = curl_error($curl);
            curl_close($curl);
            return (object) [
                'error' => true,
                'message' => $error_msg
            ];
        }

        curl_close($curl);

        return json_decode($response);
    }
}


