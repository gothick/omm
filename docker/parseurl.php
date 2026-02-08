#! /usr/local/bin/php
<?php

function usage_and_exit(): never {
   fwrite(STDERR, "Usage: parseurl.php --component=<port|host> --url=<url>\n");
   exit(42);
}

$my_args = [];
for ($i = 1; $i < count($argv); $i++) {
    if (preg_match('/^--([^=]+)=(.*)/', $argv[$i], $match)) {
        $my_args[$match[1]] = $match[2];
    }
}

if (!(array_key_exists("component", $my_args) && array_key_exists("url", $my_args))) {
   usage_and_exit();
}

$component = $my_args["component"];
$url = $my_args["url"];

if (!($component == "port" || $component == "host")) {
   usage_and_exit();
};

$result = parse_url($url, $component == "port" ? PHP_URL_PORT : PHP_URL_HOST);
if ($result === false || $result === null) {
   throw new \Exception("Could not parse {$component} from URL {$url}");
}
echo "{$result}\n";
return 0;



