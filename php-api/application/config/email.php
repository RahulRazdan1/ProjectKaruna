<?php defined('BASEPATH') OR exit('No direct script access allowed');

$config = array(
    'protocol' => 'smtp', // 'mail', 'sendmail', or 'smtp'
    'smtp_host' => 'mail.projectkaruna.net', 
    'smtp_port' => 465,
    'smtp_user' => 'info@projectkaruna.net',
    'smtp_pass' => 'Wolverene99999',
    'smtp_crypto' => 'ssl', //can be 'ssl' or 'tls' for example
    'mailtype' => 'html', //plaintext 'text' mails or 'html'
    'smtp_timeout' => '10', //in seconds
    'charset' => 'iso-8859-1',
    'wordwrap' => TRUE
);