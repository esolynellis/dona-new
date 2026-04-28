<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$target = "$root/themes/default/checkout.blade.php";
$content = file_get_contents($target);

$results = [];

// Remove QPay HTML block
$old1 = '                  <div class="radio-line-item {{ \'Qpay\' == $current[\'payment_method_code\'] ? \'active\' : \'\' }}"
                       data-key="payment_method_code"
                       data-value="Qpay"
                       id="qpay-payment-method">
                    <div class="left">
                      <span class="radio"></span>
                      <img src="" class="img-fluid">
                    </div>
                    <div class="right ms-2">
                      <div class="title">Qpay</div>
                      <div class="sub-title">Qpay</div>
                    </div>
                  </div>';

if (strpos($content, $old1) !== false) {
    $content = str_replace($old1, '', $content);
    $results['html_block'] = 'removed';
} else {
    $results['html_block'] = 'already removed';
}

// Remove QPay JS block
$old2 = "    html += `<div class=\"radio-line-item d-flex align-items-center \${payment_method_code == 'Qpay' ? 'active' : ''}\" data-key=\"payment_method_code\" data-value=\"Qpay\">";
$pos = strpos($content, $old2);
if ($pos !== false) {
    $endPos = strpos($content, '</div>`;', $pos) + strlen('</div>`;');
    $content = substr($content, 0, $pos) . substr($content, $endPos);
    $results['js_block'] = 'removed';
} else {
    $results['js_block'] = 'already removed';
}

@unlink($target);
file_put_contents($target, $content);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
