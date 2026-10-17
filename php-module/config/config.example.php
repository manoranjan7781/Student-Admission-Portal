<?php
/**
 * EXAMPLE configuration. Copy this file to  config.local.php  and edit it.
 * config.local.php is listed in .gitignore, so your real password is never
 * uploaded to GitHub.   (Environment variables DB_HOST, DB_NAME, DB_USER,
 * DB_PASS, UPLOAD_DIR override these values if you prefer them.)
 */
return [
    'db_host'    => '127.0.0.1',
    'db_port'    => 3306,
    'db_name'    => 'student_portal',
    'db_user'    => 'portal',          // create a dedicated user, avoid using root
    'db_pass'    => 'change_me',
    // Folder where uploaded files are stored. The Java module must use the SAME folder.
    // Use an absolute path, e.g. 'C:/xampp/htdocs/Student-Admission-Portal/php-module/uploads'
    'upload_dir' => __DIR__ . '/../uploads',
];
