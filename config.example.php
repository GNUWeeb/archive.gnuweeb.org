<?php

const DB_HOST = "127.0.0.1";
const DB_PORT = 3306;
const DB_USER = "username";
const DB_PASS = "password";
const DB_NAME = "gw_telegram_new";

const PDO_PARAM = [
	"mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME,
	DB_USER,
	DB_PASS,
	[
		\PDO::ATTR_ERRMODE = \PDO::ERRMODE_EXCEPTION
	]
];
