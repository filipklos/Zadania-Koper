<?php
session_start();
session_unset();

$redirect = $_GET['redirect'] ?? './';

header('Location: ' . $redirect);
