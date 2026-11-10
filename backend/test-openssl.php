<?php
putenv('OPENSSL_CONF=' . __DIR__ . '\\openssl.cnf');
$key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
var_dump($key);
while ($e = openssl_error_string()) { echo $e, PHP_EOL; }