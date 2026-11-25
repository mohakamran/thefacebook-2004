<?php
require __DIR__ . '/../src/bootstrap.php';

require_post_csrf();
$_SESSION = [];
session_regenerate_id(true);
flash('You have been logged out.');
redirect('index.php');
