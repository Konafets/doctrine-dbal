<?php

namespace TYPO3\DoctrineDbal\Persistence\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefano Kowalke <blueduck@gmx.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Interface DatabaseConnectionInterface
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Database
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
interface DatabaseConnectionInterface {
	/**
	 * Returns the name of the database system
	 *
	 * @return string
	 * @api
	 */
	public function getName();

	/**
	 * Set database username
	 *
	 * @param string $username
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseUsername($username);

	/**
	 * Returns the database username
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseUsername();

	/**
	 * Set database password
	 *
	 * @param string $password
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabasePassword($password);

	/**
	 * Returns database password
	 *
	 * @return string
	 * @api
	 */
	public function getDatabasePassword();

	/**
	 * Set database name
	 *
	 * @param string $name
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseName($name);

	/**
	 * Returns the name of the database
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseName();

	/**
	 * Set the database driver for Doctrine
	 *
	 * @param string $driver
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseDriver($driver = 'pdo_mysql');

	/**
	 * Returns the database driver
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseDriver();

	/**
	 * Set database socket
	 *
	 * @param string|NULL $socket
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseSocket($socket = NULL);

	/**
	 * Returns the database socket
	 *
	 * @return NULL|string
	 * @api
	 */
	public function getDatabaseSocket();

	/**
	 * Set database port
	 *
	 * @param integer $port
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabasePort($port = 3306);

	/**
	 * Returns the database port
	 *
	 * @return int
	 * @api
	 */
	public function getDatabasePort();

	/**
	 * Set database host
	 *
	 * @param string $host
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseHost($host = 'localhost');

	/**
	 * Returns the host of the database
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseHost();

	/**
	 * Set default charset
	 *
	 * @param string $charset
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseCharset($charset = 'utf8');

	/**
	 * Returns default charset
	 *
	 * @return $this
	 * @api
	 */
	public function getDatabaseCharset();

	/**
	 * Set current database handle
	 *
	 * @param \Doctrine\DBAL\Connection $handle
	 *
	 * @return void
	 * @api
	 */
	public function setDatabaseHandle($handle);

	/**
	 * Returns current database handle
	 *
	 * @return \Doctrine\DBAL\Connection|NULL
	 * @api
	 */
	public function getDatabaseHandle();

	/**
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @api
	 */
	public function getLastStatement();

	/**
	 * Set commands to be fired after connection was established
	 *
	 * @param array $commands List of SQL commands to be executed after connect
	 * @api
	 */
	public function setInitializeCommandsAfterConnect(array $commands);

	/**
	 * Set connection compression. Might be an advantage, if SQL server is not on localhost
	 *
	 * @param bool $connectionCompression TRUE if connection should be compressed
	 * @api
	 */
	public function setConnectionCompression($connectionCompression);

	/**
	 * Set persistent database connection
	 *
	 * @param boolean $persistentDatabaseConnection
	 * @see http://php.net/manual/de/mysqli.persistconns.php
	 * @api
	 */
	public function setPersistentDatabaseConnection($persistentDatabaseConnection);

	/**
	 * Set the debug mode.
	 *
	 * Possible values are:
	 *
	 * - 0|FALSE: deactivate debug mode
	 * - 1|TRUE:  activate debug mode
	 * - 2     :  output also successful database actions
	 *
	 * @param int $mode
	 *
	 * @return $this
	 */
	public function setDebugMode($mode);

	/**
	 * Return the debug mode setting
	 *
	 * @return int
	 */
	public function getDebugMode();

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @return void
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @api
	 */
	public function connectDatabase();

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @return void
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @api
	 */
	public function connectDB();

	/**
	 * Closes the connection.
	 *
	 * @return void
	 */
	public function close();

	/**
	 * Checks if database is connected
	 *
	 * @return boolean
	 * @api
	 */
	public function isConnected();

	/**
	 * Disconnect from database if connected
	 *
	 * @return void
	 * @api
	 */
	public function disconnectIfConnected();

	/**
	 * Returns the error number on the last query() execution
	 *
	 * @return integer PDO error number
	 * @api
	 */
	public function sqlErrorCode();

	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string PDO error string
	 * @api
	 */
	public function sqlErrorMessage();

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * Wrapper function for Doctrine/PDO fetch(\PDO::FETCH_ASSOC)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 *
	 * @return boolean|array Associative array of result row.
	 * @api
	 */
	public function fetchAssoc($stmt);

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * Wrapper function for Doctrine/PDO fetch(\PDO::FETCH_NUM)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 *
	 * @return boolean|array Array with result rows.
	 * @api
	 */
	public function fetchRow($stmt);

	/**
	 * Free result memory
	 * Wrapper function for Doctrine/PDO closeCursor()
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt A PDOStatement
	 *
	 * @return boolean Returns NULL on success or FALSE on failure.
	 * @api
	 */
	public function freeResult($stmt);

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return integer The uid of the last inserted record.
	 * @api
	 */
	public function getLastInsertId();

	/**
	 * Creates a DELETE query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\DeleteQueryInterface
	 * @api
	 */
	public function createDeleteQuery();

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param string $table See exec_TRUNCATEquery()
	 *
	 * @return string|\TYPO3\DoctrineDbal\Persistence\Doctrine\TruncateQuery
	 * @api
	 */
	public function createTruncateQuery($table = '');

	/**
	 * Creates an UPDATE query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\UpdateQueryInterface
	 * @api
	 */
	public function createUpdateQuery();

	/**
	 * Creates an INSERT query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\InsertQueryInterface
	 * @api
	 */
	public function createInsertQuery();

	/**
	 * Creates a SELECT query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\SelectQueryInterface
	 * @api
	 */
	public function createSelectQuery();

	/**
	 * Returns the expressions instance
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\ExpressionInterface
	 * @api
	 */
	public function expr();

	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * This is only used as a service function in the (1-2-3 process) of the Install Tool.
	 * In any case a lookup should be done in the _DEFAULT handler DBMS then.
	 * Use in Install Tool only!
	 *
	 * @return array Each entry represents a database name
	 * @throws \RuntimeException
	 */
	public function listDatabases();

	/**
	 * Returns the list of tables from the default database
	 *
	 * @return array Array with table names as key and arrays with status information as value
	 */
	public function listTables();

	/**
	 * Returns information about each field in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 * This function is important not only for the Install Tool but probably for
	 * DBALs as well since they might need to look up table specific information
	 * in order to construct correct queries. In such cases this information should
	 * probably be cached for quick delivery.
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Field information in an associative array with fieldname => field row
	 */
	public function listFields($tableName);

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Key information in a associative array
	 */
	public function listKeys($tableName);

	/**
	 * Escaping and quoting values for SQL statements.
	 *
	 * @param string  $string    Input string
	 * @param boolean $allowNull Whether to allow NULL values
	 *
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @api
	 */
	public function quote($string, $allowNull = FALSE);

	/**
	 * Returns a qualified identifier for $columnName in $tableName
	 *
	 * Example:
	 * <code><br>
	 * // if no $tableName is given it returns: `column`<br>
	 * $GLOBALS['TYPO3_DB']->quoteTable('column');<br><br>
	 *
	 * // if $tableName is given it returns: `pages`.`column`<br>
	 * $GLOBALS['TYPO3_DB']->quoteTable('column', 'pages');<br>
	 * </code>
	 *
	 * @param string $columnName
	 * @param string $tableName
	 *
	 * @return string
	 * @api
	 */
	public function quoteColumn($columnName, $tableName = NULL);

	/**
	 * Returns a qualified identifier for $tablename
	 *
	 * Example:
	 * <code><br>
	 * // returns: `pages`<br>
	 * $GLOBALS['TYPO3_DB']->quoteTable('pages');<br>
	 * </code>
	 *
	 * @param string $tableName
	 *
	 * @return string
	 * @api
	 */
	public function quoteTable($tableName);

	/**
	 * Custom quote identifier method
	 *
	 * Example:
	 * <code><br>
	 * // returns `column`<br>
	 * $GLOBALS['TYPO3_DB']->quoteIdentifier('column');<br>
	 * </code>
	 *
	 * @param string $identifier
	 *
	 * @return string
	 * @api
	 */
	public function quoteIdentifier($identifier);
}