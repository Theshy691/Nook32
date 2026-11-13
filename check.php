
<?php
$folder = __DIR__ . '/config';

echo "Config folder exists: ";
var_dump(is_dir($folder));

echo "<br><br>Files PHP can see in config:<br>";

if (is_dir($folder)) {
    print_r(scandir($folder));
} else {
    echo "PHP cannot find the config folder.";
}
?>