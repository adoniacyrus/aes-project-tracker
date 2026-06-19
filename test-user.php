<?php

require_once 'app/models/UserModel.php';

$userModel = new UserModel();

$user = $userModel->findByEmail(
    'admin@aes.com'
);

echo "<pre>";
print_r($user);
echo "</pre>";