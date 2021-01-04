<?php

echo $_GET['signature'];

require __DIR__.'/vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

$bookHTML = "<page>
        <style>
            .table {
                border: 2px solid; 
                display: inline-table;
                margin: 10px;
            }
        </style>";
    
        for ($i=0; $i < $_GET['sticker_count'] ; $i++) { 
            $bookHTML = $bookHTML . "<nobreak><table class='table'>
            <tr align='center'><th><b>Leo-Statz-Berufskolleg</b></th></tr>
            <tr align='center'><td>
                " . $_GET['building'] . "  <br />
                " . $_GET['signature'] . " <br />
        
               (" . $_GET['series'] . ") -" . $_GET['band'] . " + ". $_GET['avaible_copys'] ." <br />  
            
            </td></tr>
            
            </table></nobreak>";
        }
        
        $bookHTML = $bookHTML . "</page>";




ob_end_clean();
$html2pdf = new Html2Pdf('P', 'A4', 'DE');
$html2pdf->writeHTML($bookHTML);

$html2pdf->output('sticker.pdf', 'I');

?>