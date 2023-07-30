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

            // // Change default charset
            // $line .= "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4;\n";
        }

        // Replace BEGIN TRANSACTION
        if (preg_match('/BEGIN TRANSACTION/', $line)) {
            $line = str_replace('BEGIN TRANSACTION', 'START TRANSACTION', $line);
        }

        // Fix JSON escaping as a default value
        if (preg_match('/CREATE TABLE IF NOT EXISTS/', $line)) {
            $pattern = '(
                \{ # JSON object start
                    ( 
                        \s*
                        "[^"]+"                  # key
                        \s*:\s*                  # colon
                        (
                                                 # value
                            (?: 
                                "[^"]+" |        # string
                                \d+(?:\.\d+)? |  # number
                                true |
                                false |
                                null
                            ) | 
                            (?R)                 # pattern recursion
                        )
                        \s*
                        ,?                       # comma
                    )* 
                \} # JSON object end
            )x';

            $jsonInStringOriginal = null;
            $jsonInStringEscaped = null;
            preg_replace_callback(
                $pattern,
                function ($match) use (&$line, &$jsonInStringEscaped, &$jsonInStringOriginal) {
                    $jsonInStringOriginal = $match[0];
                    $jsonInStringEscaped = json_decode($jsonInStringOriginal);
                    if ($jsonInStringEscaped) {
                        $jsonInStringEscaped = json_encode($jsonInStringOriginal);
                        $jsonInStringEscaped = trim($jsonInStringEscaped, '"');

                        $line = str_replace($jsonInStringOriginal, $jsonInStringEscaped, $line);
                    }
                },
                $line
            );

            // Replace "" to `` (only when its not escaped)
            $line = preg_replace('/(?<!\\\\)"/', '`', $line);

            // Replace autoincrement
            if (strpos($line, 'autoincrement') !== false) {
                $line = str_replace('autoincrement', 'AUTO_INCREMENT', $line);
            }

            // Replace varchar
            $line = str_replace('varchar', 'text', $line);

            // Replace default charset 
            $charset = ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;';
            $lastSemicolonPos = strrpos($line, ';');
            if ($lastSemicolonPos !== false) {
                $line = substr_replace($line, $charset, $lastSemicolonPos, 1);
            } else {
                $line = $line;
            }

            // Fix quote escape 
            // https://www.databasestar.com/sql-escape-single-quote/
            if (preg_match('/^INSERT INTO/', $line)) {
                $line = str_replace("\''", "''", $line);
            }
        }

        // Output
        echo $line;
    }
}
