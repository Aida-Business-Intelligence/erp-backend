<?php

defined('BASEPATH') or exit('No direct script access allowed');
$dimensions = $pdf->getPageDimensions();

$pdf_logo_url = pdf_logo_url();
$pdf->writeHTMLCell(($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm'])), '', '', '', $pdf_logo_url, 0, 1, false, true, 'L', true);

$pdf->ln(4);
// Get Y position for the separation
$y = $pdf->getY();

$proposal_info = '<div style="color:#424242;">';
$proposal_info .= format_organization_info();
$proposal_info .= '</div>';

$pdf->writeHTMLCell(($swap == '0' ? (($dimensions['wk'] / 2) - $dimensions['rm']) : ''), '', '', ($swap == '0' ? $y : ''), $proposal_info, 0, 0, false, true, ($swap == '1' ? 'R' : 'J'), true);

$rowcount = max([$pdf->getNumLines($proposal_info, 80)]);

// Proposal to
$client_details = '<bTitulo</b>';
$client_details .= '<div style="color:#424242;">';
$client_details .= 'Cliente detalhes';
$client_details .= '</div>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['lm'], $rowcount * 7, '', ($swap == '1' ? $y : ''), $client_details, 0, 1, false, true, ($swap == '1' ? 'J' : 'R'), true);

$pdf->ln(6);

$proposal_date = date('Y-m-d');
$open_till     = '';
$project = '';
$qty_heading = _l('estimate_table_quantity_heading', '', false);



$items_html .= '<br /><br />';
$items_html .= '';
$items_html .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';

$items_html .= '
<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_subtotal') . '</strong></td>
    <td align="right" width="15%">23</td>
</tr>';


if(isset($items)){
foreach ($items->taxes() as $tax) {
    $items_html .= '<tr>
    <td align="right" width="85%"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="15%">' . app_format_money($tax['total_tax'], $proposal->currency_name) . '</td>
</tr>';
}
}


$items_html .= '</table>';
$teste =1;

if (get_option('total_to_words_enabled') == 1) {
    $items_html .= '<br /><br /><br />';
    $items_html .= '<strong style="text-align:center;">' . _l('num_word') . ': ' . $teste . '</strong>';
}


// Get the proposals css
// Theese lines should aways at the end of the document left side. Dont indent these lines
$html = <<<EOF
    <p style="font-size:20px;"># {$number}
    <br /><span style="font-size:15px;">Assunto</span>
    </p>
    {$proposal_date}
    <br />
    {$open_till}
    {$project}
    <div style="width:675px !important;">
    Conteudo
    </div>
    EOF;

$pdf->writeHTML($html, true, false, true, false, '');
