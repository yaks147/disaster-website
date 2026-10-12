<?php
require __DIR__ . '/config.php';

session_unset();
session_destroy();

redirect('index.php');
