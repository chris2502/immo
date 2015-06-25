<?php

/* Killi Json client
 * 
 * Les actions et leurs arguments sont disponibles dans le fichier class/class.JSONMethod.php
 */

// config

$path     = 'http://localhost/myKilliApp/';
$login    = 'chucknorris';
$password = 'superman';
$cert	  = '/var/www/priv/foinfo/certificats/userkey.pem'; // si https
$cert_pwd = 'cDaTaLm';

// query







$action = 'search';
$fields = array('object'=>'adresse','filter'=>array());









// traitement

$ch = curl_init();

if(substr($path, 0, 5)=='https')
{
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $cert_pwd);
	curl_setopt($ch, CURLOPT_SSLCERT, $cert);
}
curl_setopt($ch, CURLOPT_URL, $path.'index.php?action=json.'.$action);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'login='.$login.'&password='.$password.'&data='.json_encode($fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);
$error = curl_error($ch);

curl_close($ch);

if($error) die('Error : '.$error);

$json=json_decode($result,TRUE);

if(!$json) die($result);

header('Content-type:text/plain; charset=utf-8');

die(var_export($json));
