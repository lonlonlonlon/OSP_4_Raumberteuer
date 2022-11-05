<?php
$path = getcwd();
$parts = explode('/', $path);
if ($parts[2] == 'www') {
    $parts[2] = 'www-data';
}
echo($parts[2].PHP_EOL);