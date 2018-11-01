<?php

$config['charset'] = 'utf8mb4';
$config['prefix'] = 'aws_';
$config['driver'] = 'PDO_MYSQL';
$config['master'] = array (
  'charset' => 'utf8mb4',
  'host' => 'localhost',
  'username' => 'user1',
  'password' => '123456',
  'dbname' => 'db',
);
$config['slave'] = false;
