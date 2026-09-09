<?php
require_once 'config.php';
require_once 'functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verifyCsrf();
destroyUserSession();
header('Location: login.php', true, 303);
exit;
