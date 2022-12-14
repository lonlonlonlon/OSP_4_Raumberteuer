<?php
$envFileContent = file_get_contents('envtemplate');

$tmpString = shell_exec('docker inspect RaumbetreuerToolDatabase | grep IPAddress');
$tmpString = preg_replace(["/\r\n/", "/\r/"], "\n", $tmpString);

$lines = explode("\n", $tmpString);
$replace = "";
foreach ($lines as $line) {
    if (!str_contains($line, '""') && str_contains($line, '"IPAddress":')) {
        $line = substr($line, 33, strlen($line)-33);
        $line = trim(trim($line, ','), '"');
        $replace = $line;
    }
}

$envFileContent = str_replace('DBIPADDR', $replace, $envFileContent);

file_put_contents('.env', $envFileContent);

// 13 34