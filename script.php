<?php
// Convert .sql of SQLITE to MariaDB format
// 
// php script.php sqlite.sql > output.mariadb.sql

// Load 
if (isset($argv[1])) {
    $inputFile = $argv[1];
} else {
    die("Input files is required.");
}

// Open and iterate
$file = fopen($inputFile, 'r');
if ($file) {
    // Read the file line by line
    while (($line = fgets($file)) !== false) {
        // Remove 
        if (preg_match('/DELETE FROM sqlite_sequence;/', $line)) {
            continue;
        }
        if (preg_match('/PRAGMA foreign_keys=OFF;/', $line)) {
            continue;
        }

        // Auto Increment 
        if (preg_match('/^INSERT INTO sqlite_sequence VALUES/', $line)) {
            preg_match("/VALUES\('([^']+)',(\d+)\)/", $line, $matches);
            $table = $matches[1] ?? '';
            $value = $matches[2] ?? '';
            $line = "ALTER TABLE `$table` AUTO_INCREMENT = $value;\n";
        }

        // Replace BEGIN TRANSACTION
        if (preg_match('/BEGIN TRANSACTION/', $line)) {
            $line = str_replace('BEGIN TRANSACTION', 'START TRANSACTION', $line);
        }

        // Replace "" to ``
        if (preg_match('/CREATE TABLE IF NOT EXISTS/', $line)) {
            $line = str_replace('"', '`', $line);
            // Replace autoincrement
            if (strpos($line, 'autoincrement') !== false) {
                $line = str_replace('autoincrement', 'AUTO_INCREMENT', $line);
            }

            // Replace varchar
            $line = str_replace('varchar', 'text', $line);
        }

        // Escape quote inside JSON
        $line = str_replace('\"', '\\\"', $line);

        // Output
        echo $line;
    }
}
