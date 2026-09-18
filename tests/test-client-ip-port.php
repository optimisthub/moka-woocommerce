<?php
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) { return $value; }
}
/**
 * Extracts the IP/port helpers from Moka_Gateway.php and exercises them
 * against the valid/invalid tables in the official Moka guide.
 */
$src = file_get_contents(dirname(__DIR__) . '/core/library/Moka_Gateway.php');

$methods = ['reservedIpRanges','isPublicIp','getUserIp','getUserPort','isValidPort'];
$code = "class T {\n";
foreach ($methods as $m) {
    $start = strpos($src, "private function $m(");
    if ($start === false) { fwrite(STDERR, "MISSING $m\n"); exit(1); }
    // find matching closing brace of the method
    $i = strpos($src, '{', $start);
    $depth = 0; $end = null;
    for ($j = $i; $j < strlen($src); $j++) {
        if ($src[$j] === '{') $depth++;
        elseif ($src[$j] === '}') { $depth--; if ($depth === 0) { $end = $j; break; } }
    }
    $code .= str_replace("private function", "public function", substr($src, $start, $end - $start + 1)) . "\n";
}
$code .= "}\n";
eval($code);
$t = new T();

$pass = 0; $fail = 0;
function check($label, $actual, $expected, &$pass, &$fail) {
    $ok = $actual === $expected;
    $ok ? $pass++ : $fail++;
    printf("%s  %-46s actual=%-14s expected=%s\n", $ok ? 'PASS' : 'FAIL', $label, var_export($actual,true), var_export($expected,true));
}

echo "=== isPublicIp() — guide validity table ===\n";
check('88.240.10.5 (valid)',            $t->isPublicIp('88.240.10.5'), true, $pass, $fail);
check('2a01:5ec0:1a:8b2::14 (valid)',   $t->isPublicIp('2a01:5ec0:1a:8b2::14'), true, $pass, $fail);
check('2001:db8:85a3:0:0:8a2e:370:7334',$t->isPublicIp('2001:db8:85a3:0:0:8a2e:370:7334'), true, $pass, $fail);
check('176.829.921.201 (octet>255)',    $t->isPublicIp('176.829.921.201'), false, $pass, $fail);
check('192.168.0.256 (octet>255)',      $t->isPublicIp('192.168.0.256'), false, $pass, $fail);
check('1.2.3.4.5 (5 parts)',            $t->isPublicIp('1.2.3.4.5'), false, $pass, $fail);
check('example.com (hostname)',         $t->isPublicIp('example.com'), false, $pass, $fail);
check('88.240.10.5:51520 (has port)',   $t->isPublicIp('88.240.10.5:51520'), false, $pass, $fail);
check('(empty)',                        $t->isPublicIp(''), false, $pass, $fail);

echo "\n=== reserved ranges must be rejected (guide section 01) ===\n";
foreach (['10.1.2.3','172.16.0.1','172.31.255.254','192.168.1.1','127.0.0.1','169.254.1.1','::1'] as $ip) {
    check("$ip reserved", $t->isPublicIp($ip), false, $pass, $fail);
}
check('172.32.0.1 (outside 172.16/12 = public)', $t->isPublicIp('172.32.0.1'), true, $pass, $fail);

echo "\n=== isValidPort() — guide validity table ===\n";
check('51520 valid',  $t->isValidPort('51520'), true,  $pass, $fail);
check('443 valid',    $t->isValidPort('443'),   true,  $pass, $fail);
check('8080 valid',   $t->isValidPort('8080'),  true,  $pass, $fail);
check('1 valid',      $t->isValidPort('1'),     true,  $pass, $fail);
check('65535 valid',  $t->isValidPort('65535'), true,  $pass, $fail);
check('0 invalid',    $t->isValidPort('0'),     false, $pass, $fail);
check('65536 invalid',$t->isValidPort('65536'), false, $pass, $fail);
check('-1 invalid',   $t->isValidPort('-1'),    false, $pass, $fail);
check('abc invalid',  $t->isValidPort('abc'),   false, $pass, $fail);
check('"8 0" invalid',$t->isValidPort('8 0'),   false, $pass, $fail);
check('(empty) invalid', $t->isValidPort(''),   false, $pass, $fail);

echo "\n=== getUserIp() behind proxies (the Hetzner case) ===\n";
$_SERVER = ['HTTP_X_FORWARDED_FOR'=>'88.240.10.5, 10.0.0.7, 172.16.0.1','REMOTE_ADDR'=>'10.0.0.7'];
check('XFF chain -> first public IP', $t->getUserIp(), '88.240.10.5', $pass, $fail);

$_SERVER = ['HTTP_CF_CONNECTING_IP'=>'88.240.10.5','REMOTE_ADDR'=>'172.68.1.1'];
check('Cloudflare CF-Connecting-IP', $t->getUserIp(), '88.240.10.5', $pass, $fail);

$_SERVER = ['HTTP_X_FORWARDED_FOR'=>'10.0.0.7, 192.168.1.5','REMOTE_ADDR'=>'10.0.0.7'];
check('all private -> falls back to REMOTE_ADDR', $t->getUserIp(), '10.0.0.7', $pass, $fail);

$_SERVER = ['REMOTE_ADDR'=>'88.240.10.5'];
check('plain REMOTE_ADDR only', $t->getUserIp(), '88.240.10.5', $pass, $fail);

$_SERVER = ['HTTP_X_FORWARDED_FOR'=>'88.240.10.5:51520'];
check('XFF entry carrying a port', $t->getUserIp(), '88.240.10.5', $pass, $fail);

$_SERVER = ['HTTP_X_REAL_IP'=>'88.240.10.5','REMOTE_ADDR'=>'10.0.0.7'];
check('X-Real-IP', $t->getUserIp(), '88.240.10.5', $pass, $fail);

echo "\n=== getUserPort() ===\n";
$_SERVER = ['HTTP_X_FORWARDED_PORT'=>'51520','REMOTE_PORT'=>'443'];
check('X-Forwarded-Port wins over proxy port', $t->getUserPort(), '51520', $pass, $fail);
$_SERVER = ['HTTP_X_FORWARDED_PORT'=>'51520, 443','REMOTE_PORT'=>'443'];
check('X-Forwarded-Port chain', $t->getUserPort(), '51520', $pass, $fail);
$_SERVER = ['HTTP_X_REAL_PORT'=>'51520','REMOTE_PORT'=>'443'];
check('X-Real-Port', $t->getUserPort(), '51520', $pass, $fail);
$_SERVER = ['REMOTE_PORT'=>'51520'];
check('REMOTE_PORT fallback', $t->getUserPort(), '51520', $pass, $fail);
$_SERVER = ['REMOTE_PORT'=>'0'];
check('invalid REMOTE_PORT -> empty', $t->getUserPort(), '', $pass, $fail);
$_SERVER = [];
check('no port info -> empty (never a fake value)', $t->getUserPort(), '', $pass, $fail);

echo "\n----------------------------------------\n";
printf("PASSED: %d   FAILED: %d\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);
