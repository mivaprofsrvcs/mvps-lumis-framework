<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

return [
	/*
	|--------------------------------------------------------------------------
	| Default Database Connection Name
	|--------------------------------------------------------------------------
	|
	| Specify the default database connection to be used for all database
	| operations. This connection will be utilized unless an alternative
	| connection is explicitly specified when executing a query or statement.
	|
	*/
	'default' => env('DB_CONNECTION', 'sqlite'),

	/*
	|--------------------------------------------------------------------------
	| Database Connections
	|--------------------------------------------------------------------------
	|
	| The following section lists all the database connections configured for
	| your application. Each supported database system in Lumis includes an
	| example configuration. Feel free to add or remove connections as needed.
	|
	*/
	'connections' => [
		'sqlite' => [
			'driver' => 'sqlite',
			'url' => env('DB_URL'),
			'database' => env('DB_DATABASE', database_path('database.sqlite')),
			'prefix' => '',
			'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
			'busy_timeout' => null,
			'journal_mode' => null,
			'synchronous' => null,
		],
		'mysql' => [
			'driver' => 'mysql',
			'url' => env('DB_URL'),
			'host' => env('DB_HOST', '127.0.0.1'),
			'port' => env('DB_PORT', '3306'),
			'database' => env('DB_DATABASE', 'lumis'),
			'username' => env('DB_USERNAME', 'root'),
			'password' => env('DB_PASSWORD', ''),
			'unix_socket' => env('DB_SOCKET', ''),
			'charset' => env('DB_CHARSET', 'utf8mb4'),
			'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
			'prefix' => '',
			'prefix_indexes' => true,
			'strict' => true,
			'engine' => null,
			'options' => extension_loaded('pdo_mysql')
				? array_filter([PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')])
				: [],
		],
		'mariadb' => [
			'driver' => 'mariadb',
			'url' => env('DB_URL'),
			'host' => env('DB_HOST', '127.0.0.1'),
			'port' => env('DB_PORT', '3306'),
			'database' => env('DB_DATABASE', 'lumis'),
			'username' => env('DB_USERNAME', 'root'),
			'password' => env('DB_PASSWORD', ''),
			'unix_socket' => env('DB_SOCKET', ''),
			'charset' => env('DB_CHARSET', 'utf8mb4'),
			'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
			'prefix' => '',
			'prefix_indexes' => true,
			'strict' => true,
			'engine' => null,
			'options' => extension_loaded('pdo_mysql')
				? array_filter([PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')])
				: [],
		],
		'pgsql' => [
			'driver' => 'pgsql',
			'url' => env('DB_URL'),
			'host' => env('DB_HOST', '127.0.0.1'),
			'port' => env('DB_PORT', '5432'),
			'database' => env('DB_DATABASE', 'lumis'),
			'username' => env('DB_USERNAME', 'root'),
			'password' => env('DB_PASSWORD', ''),
			'charset' => env('DB_CHARSET', 'utf8'),
			'prefix' => '',
			'prefix_indexes' => true,
			'search_path' => 'public',
			'sslmode' => 'prefer',
		],
		'sqlsrv' => [
			'driver' => 'sqlsrv',
			'url' => env('DB_URL'),
			'host' => env('DB_HOST', 'localhost'),
			'port' => env('DB_PORT', '1433'),
			'database' => env('DB_DATABASE', 'lumis'),
			'username' => env('DB_USERNAME', 'root'),
			'password' => env('DB_PASSWORD', ''),
			'charset' => env('DB_CHARSET', 'utf8'),
			'prefix' => '',
			'prefix_indexes' => true,
			// 'encrypt' => env('DB_ENCRYPT', 'yes'),
			// 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Migration Repository Table
	|--------------------------------------------------------------------------
	|
	| This table records the migration history for the application, tracking
	| which migrations have been executed. This information is used to
	| determine which migrations need to be run during the migration process.
	|
	*/
	'migrations' => [
		'table' => 'migrations',
		'update_date_on_publish' => true,
	],
];
