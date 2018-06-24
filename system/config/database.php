<?php

$config['charset'] = getenv('WECENTER_DB_CHARSET');
$config['prefix'] = getenv('WECENTER_DB_PREFIX');
$config['driver'] = getenv('WECENTER_DB_DRIVER');
$config['master'] = array (
  'charset' => getenv('WECENTER_DB_CHARSET'),
  'host' => getenv('WECENTER_DB_HOST'),
  'username' => getenv('WECENTER_DB_USERNAME'),
  'password' => getenv('WECENTER_DB_PASSWORD'),
  'dbname' => getenv('WECENTER_DB_DBNAME'),
);
$config['slave'] = false;
