<?php
require_once 'includes/auth.php';
session_destroy();
header('Location: /ITECA_SumativeAssessment/index.php');
exit;
