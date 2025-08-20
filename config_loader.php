<?php
// config_loader.php

// Define the absolute path to the secure configuration directory.
// This makes the application more portable as the server environment may change.
// The path /home/qdosnetw/brisas_secure_configs was provided by the user.
$secure_config_path = '/home/qdosnetw/brisas_secure_configs/main_config.php';

// Check if the file exists before trying to include it.
if (file_exists($secure_config_path)) {
    require_once $secure_config_path;
} else {
    // If the file doesn't exist, stop execution and show an error.
    // This provides a clear error message instead of a generic 500 error.
    http_response_code(500);
    echo "Error: The main configuration file is missing or the path is incorrect. Please check the server configuration.";
    exit;
}
