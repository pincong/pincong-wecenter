<?php

$config['charset'] = 'utf8';
$config['prefix'] = $_SERVER['WECENTER_DB_PREFIX'];
$config['driver'] = $_SERVER['WECENTER_DB_DRIVER'];
$config['master'] = array (
  'charset' => 'utf8',
  'host' => $_SERVER['WECENTER_DB_HOST'],
  'username' => $_SERVER['WECENTER_DB_USERNAME'],
  'password' => $_SERVER['WECENTER_DB_PASSWORD'],
  'dbname' => $_SERVER['WECENTER_DB_DBNAME'],
);
$config['slave'] = false;
