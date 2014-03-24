<?php

namespace TYPO3\DoctrineDbal\Persistence\Doctrine;

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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\DoctrineDbal\Persistence\Database\DatabaseConnectionInterface;
use TYPO3\DoctrineDbal\Persistence\Database\SelectQueryInterface;
use TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException;

/**
 * Class DatabaseConnection
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine;
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class DatabaseConnection implements DatabaseConnectionInterface {
	/**
	 * @var string $databaseUsername Database user to connect with
	 */
	protected $databaseUsername = '';

	/**
	 * @var string $databaseUserPassword Database password to connect with
	 */
	protected $databaseUserPassword = '';

	/**
	 * @var string $databaseName Database name to connect to
	 */
	protected $databaseName = '';

	/**
	 * @var string Database host to connect to
	 */
	protected $databaseHost = '';

	/**
	 * @var integer $databasePort Database port to connect to
	 */
	protected $databasePort = 3306;

	/**
	 * @var string|NULL $databaseSocket Database socket to connect to
	 */
	protected $databaseSocket = NULL;

	/**
	 * Default character set, applies unless character set or collation are explicitly set
	 *
	 * @param string $defaultCharset
	 */
	protected $defaultCharset = 'utf8';

	/**
	 * @var \Doctrine\DBAL\Configuration $databaseConfiguration
	 */
	protected $databaseConfiguration;

	/**
	 * @var array $connectionParams The connection settings for Doctrine
	 */
	protected $connectionParams = array(
		'dbname'      => NULL,
		'user'        => '',
		'password'    => '',
		'host'        => '',
		'driver'      => '',
		'port'        => '',
		'unix_socket' => '',
		'charset'     => 'utf8',
	);

	/**
	 * @var boolean TRUE if database connection should be persistent
	 * @see http://php.net/manual/de/mysqli.persistconns.php
	 */
	protected $persistentDatabaseConnection = FALSE;

	/**
	 * @var boolean TRUE if connection between client and sql server is compressed
	 */
	protected $connectionCompression = FALSE;

	/**
	 * @var array List of commands executed after connection was established
	 */
	protected $initializeCommandsAfterConnect = array();

	/**
	 * @var boolean TRUE if database connection is established
	 */
	protected $isConnected = FALSE;

	/**
	 * @var \Doctrine\DBAL\Connection $link Database connection object
	 */
	protected $link = NULL;

	/**
	 * @var \Doctrine\DBAL\Logging\SQLLogger
	 */
	protected $logger = NULL;

	/**
	 * The database schema
	 *
	 * @var \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
	 */
	protected $schemaManager = NULL;

	/**
	 * The database schema
	 *
	 * @var \Doctrine\DBAL\Schema\Schema $schema
	 */
	protected $schema = NULL;

	/**
	 * The current database platform
	 *
	 * @var \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	protected $platform = NULL;

	/**
	 * Set "TRUE" or "1" if you want database errors outputted. Set to "2" if you also want successful database actions outputted.
	 *
	 * @param int $debugOutput
	 */
	public $debugOutput = FALSE;

	/**
	 * Set "TRUE" or "1" if you want database errors outputted. Set to "2" if you also want successful database actions outputted.
	 *
	 * @var int $isDebugMode
	 */
	protected $isDebugMode = FALSE;

	/**
	 * Set TRUE if you want database errors outputted.
	 *
	 * @var int $showErrors
	 */
	protected $showErrors = FALSE;

	/**
	 * Set to TRUE if you want output successful database queries
	 *
	 * @var bool $inVerboseMode
	 */
	protected $inVerboseMode = FALSE;

	/**
	 * Set to last built query (not necessarily executed...)
	 *
	 * @param string $debug_lastBuiltQuery
	 */
	public $debug_lastBuiltQuery = '';

	/**
	 * Set this to 1 to get queries explained (devIPmask must match). Set the value to 2 to the same but disregarding the devIPmask.
	 * There is an alternative option to enable explain output in the admin panel under "TypoScript", which will produce much nicer output, but only works in FE.
	 *
	 * @param int $explainOutput
	 */
	public $explainOutput = 0;

	/**
	 * Set "TRUE" if you want the last built query to be stored in $debug_lastBuiltQuery independent of $this->debugOutput
	 *
	 * @var bool $store_lastBuiltQuery
	 */
	public $store_lastBuiltQuery = FALSE;

	/**
	 * @var int $affectedRows The affected rows from the last UPDATE, INSERT or DELETE query
	 */
	protected $affectedRows = -1;

	/**
	 * @var array<PostProcessQueryHookInterface> $preProcessHookObjects
	 */
	protected $preProcessHookObjects = array();

	/**
	 * @var array<PreProcessQueryHookInterface> $postProcessHookObjects
	 */
	protected $postProcessHookObjects = array();

	/**
	 * Returns the name of the database system
	 *
	 * @return string
	 * @api
	 */
	public function getName() {
		return $this->link->getDatabasePlatform()->getName();
	}

	/**
	 * Set database username
	 *
	 * @param string $username
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseUsername($username) {
		$this->disconnectIfConnected();
		$this->databaseUsername = $username;
		$this->connectionParams['user'] = $username;

		return $this;
	}

	/**
	 * Returns the database username
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseUsername() {
		return $this->connectionParams['user'];
	}

	/**
	 * Set database password
	 *
	 * @param string $password
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabasePassword($password) {
		$this->disconnectIfConnected();
		$this->databaseUserPassword = $password;
		$this->connectionParams['password'] = $password;

		return $this;
	}

	/**
	 * Returns database password
	 *
	 * @return string
	 * @api
	 */
	public function getDatabasePassword() {
		return $this->connectionParams['password'];
	}

	/**
	 * Set database name
	 *
	 * @param string $name
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseName($name) {
		$this->disconnectIfConnected();
		$this->databaseName = $name;
		$this->connectionParams['dbname'] = $name;

		return $this;
	}

	/**
	 * Returns the name of the database
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseName() {
		return $this->connectionParams['dbname'];
	}

	/**
	 * Set the database driver for Doctrine
	 *
	 * @param string $driver
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseDriver($driver = 'pdo_mysql') {
		$this->connectionParams['driver'] = $driver;

		return $this;
	}

	/**
	 * Returns the database driver
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseDriver() {
		return $this->connectionParams['driver'];
	}

	/**
	 * Set a flag if the SQLLite should live in memory
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function setDatabaseSQLLiteUseMemory($value) {
		$this->connectionParams['memory'] = $value;

		return $this;
	}

	/**
	 * Set the connection parameter array
	 *
	 * @param array $connectionParams
	 */
	public function setConnectionParams(array $connectionParams) {
		$this->connectionParams = $connectionParams;
	}

	/**
	 * Set database socket
	 *
	 * @param string|NULL $socket
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseSocket($socket = NULL) {
		$this->disconnectIfConnected();
		$this->databaseSocket = $socket;
		$this->connectionParams['unix_socket'] = $socket;

		return $this;
	}

	/**
	 * Returns the database socket
	 *
	 * @return NULL|string
	 * @api
	 */
	public function getDatabaseSocket() {
		return $this->connectionParams['unix_socket'];
	}

	/**
	 * Set database port
	 *
	 * @param integer $port
	 *
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 * @return $this
	 * @api
	 */
	public function setDatabasePort($port = 3306) {
		if (!is_numeric($port)) {
			throw new InvalidArgumentException('The argument for port must be an integer.');
		}
		$this->disconnectIfConnected();
		$this->databasePort = (int) $port;
		$this->connectionParams['port'] = (int) $port;

		return $this;
	}

	/**
	 * Returns the database port
	 *
	 * @return int
	 * @api
	 */
	public function getDatabasePort() {
		return (int) $this->connectionParams['port'];
	}

	/**
	 * Set database host
	 *
	 * @param string $host
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseHost($host = 'localhost') {
		$this->disconnectIfConnected();
		$this->databaseHost = $host;
		$this->connectionParams['host'] = $host;

		return $this;
	}

	/**
	 * Returns the host of the database
	 *
	 * @return string
	 * @api
	 */
	public function getDatabaseHost() {
		return $this->connectionParams['host'];
	}

	/**
	 * Set default charset
	 *
	 * @param string $charset
	 *
	 * @return $this
	 * @api
	 */
	public function setDatabaseCharset($charset = 'utf8') {
		$this->connectionParams['charset'] = $charset;

		return $this;
	}

	/**
	 * Returns default charset
	 *
	 * @return $this
	 * @api
	 */
	public function getDatabaseCharset() {
		return $this->connectionParams['charset'];
	}

	/**
	 * Sets the ssl for Postgres databases
	 *
	 * @param string $ssl
	 *
	 * @return $this
	 * @api
	 */
	public function setSslMode($ssl) {
		$this->connectionParams['sslmode'] = $ssl;

		return $this;
	}

	/**
	 * Return the ssl settings
	 *
	 * @return mixed
	 * @api
	 */
	public function getSslMode() {
		return $this->connectionParams['sslmode'];
	}

	/**
	 * Set current database handle
	 *
	 * @param \Doctrine\DBAL\Connection $handle
	 *
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 * @return void
	 * @api
	 */
	public function setDatabaseHandle($handle) {
		if ($handle instanceof \Doctrine\DBAL\Connection || $handle === NULL) {
			$this->link = $handle;
		} else {
			throw new InvalidArgumentException('Wrong type of argument given to setDatabaseHandle. Need to be of type \Doctrine\DBAL\Connection.');
		}

	}

	/**
	 * Returns current database handle
	 *
	 * @return \Doctrine\DBAL\Connection|NULL
	 * @api
	 */
	public function getDatabaseHandle() {
		return $this->link;
	}

	/**
	 * Enables/Disables the storage of the last statement
	 *
	 * @param $value
	 *
	 * @return $this
	 * @api
	 */
	public function setStoreLastBuildQuery($value) {
		$this->store_lastBuiltQuery = (bool)$value;

		return $this;
	}

	/**
	 * Returns the settings if the last build query should be stored
	 *
	 * @return bool
	 * @api
	 */
	public function getStoreLastBuildQuery() {
		return (bool)$this->store_lastBuiltQuery;
	}

	/**
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @api
	 */
	public function getLastStatement() {
		$queries = $this->logger->queries;
		$currentQuery = $this->logger->currentQuery;
		$lastStatement = $queries[$currentQuery]['sql'];

		return $lastStatement;
	}

	/**
	 * Set the connection to connected or disconnected
	 *
	 * @param bool $value
	 */
	public function setConnected($value) {
		$this->isConnected = $value;
	}

	/**
	 * Returns the schema object
	 *
	 * @return \Doctrine\DBAL\Schema\Schema
	 */
	public function getSchema() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return $this->schema;
	}

	/**
	 * Returns the schema manager
	 * 
	 * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	public function getSchemaManager() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return $this->schemaManager;
	}

	/**
	 * Returns the platform object
	 *
	 * @return \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	public function getPlatform() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return $this->platform;
	}

	/**
	 * Set commands to be fired after connection was established
	 *
	 * @param array $commands List of SQL commands to be executed after connect
	 * @return $this
	 * @api
	 */
	public function setInitializeCommandsAfterConnect(array $commands) {
		$this->disconnectIfConnected();
		$this->initializeCommandsAfterConnect = $commands;

		return $this;
	}

	/**
	 * Set connection compression. Might be an advantage, if SQL server is not on localhost
	 *
	 * @param bool $connectionCompression TRUE if connection should be compressed
	 * @return $this
	 */
	public function setConnectionCompression($connectionCompression) {
		$this->disconnectIfConnected();
		$this->connectionCompression = (bool)$connectionCompression;

		return $this;
	}

	/**
	 * Set persistent database connection
	 *
	 * @param boolean $persistentDatabaseConnection
	 *
	 * @return $this
	 * @see http://stackoverflow.com/questions/16217426/is-it-possible-to-use-doctrine-with-persistent-pdo-connections
	 * @see http://www.mysqlperformanceblog.com/2006/11/12/are-php-persistent-connections-evil/
	 */
	public function setPersistentDatabaseConnection($persistentDatabaseConnection) {
		$this->disconnectIfConnected();
		$this->persistentDatabaseConnection = (bool)$persistentDatabaseConnection;

		return $this;
	}

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
	public function setDebugMode($mode){
		$this->debugOutput = $mode;

		return $this;
	}

	/**
	 * Return the debug mode setting
	 *
	 * @return int
	 */
	public function getDebugMode(){
		return (int)$this->debugOutput;
	}

	/**
	 * Connects to database for TYPO3 sites
	 *
	 * @param bool $isInitialInstallationInProgress
	 *
	 * @return void
	 */
	public function connectDatabase($isInitialInstallationInProgress = FALSE) {
		// Early return if connected already
		if ($this->isConnected) {
			return;
		}

		if (!$isInitialInstallationInProgress) {
			$this->checkDatabasePreConditions();
		}

		try {
			$this->link = $this->getConnection();
		} catch (\Exception $e) {
			echo $e->getMessage();
		}

		$this->isConnected = $this->checkConnectivity();

		if (!$isInitialInstallationInProgress) {
			if ($this->isConnected) {
				$this->initCommandsAfterConnect();
				$this->selectDb();
			}

			$this->prepareHooks();
		}
	}

	/**
	 * Checks if the PDO database extension is loaded
	 *
	 * @throws \RuntimeException
	 */
	private function checkForDatabaseExtensionLoaded(){
		if (!extension_loaded('pdo')) {
			throw new \RuntimeException(
				'Database Error: PHP PDO extension not loaded. This is a must to use this extension (ext:doctrine_dbal)!',
				// TODO: Replace with current date for Thesis
				1388496499
			);
		}
	}

	/**
	 * @throws \RuntimeException
	 * @return void
	 */
	private function checkDatabasePreConditions() {
		if (!$this->getDatabaseName()) {
			throw new \RuntimeException(
				'TYPO3 Fatal Error: No database specified!',
				1270853882
			);
		}
	}

	/**
	 * Initialize Doctrine
	 *
	 * @return void
	 */
	private function initDoctrine() {
		$this->databaseConfiguration = GeneralUtility::makeInstance('\\Doctrine\\DBAL\\Configuration');
		$this->databaseConfiguration->setSQLLogger(new DebugStack());
		$this->schema = GeneralUtility::makeInstance('\\Doctrine\\DBAL\\Schema\\Schema');
	}

	/**
	 * Returns the database connection
	 *
	 * @throws \RuntimeException
	 * @return \Doctrine\DBAL\Connection
	 */
	private function getConnection() {
		// Early return if connected already
		if ($this->isConnected) {
			return;
		}

		$this->checkForDatabaseExtensionLoaded();

		$this->initDoctrine();

		// If the user want a persistent connection we have to create the PDO instance by ourself and pass it to Doctrine.
		// See http://stackoverflow.com/questions/16217426/is-it-possible-to-use-doctrine-with-persistent-pdo-connections
		// http://www.mysqlperformanceblog.com/2006/11/12/are-php-persistent-connections-evil/
		if ($this->persistentDatabaseConnection) {
			// pattern: mysql:host=localhost;dbname=databaseName
			$cdn = substr($this->getDatabaseDriver(), 3) . ':host=' . $this->getDatabaseHost() . ';dbname=' . $this->getDatabaseName();
			$pdoHandle = new \PDO($cdn, $this->getDatabaseUsername(), $this->getDatabasePassword(), array(\PDO::ATTR_PERSISTENT => true));
			$this->connectionParams['pdo'] = $pdoHandle;
		}

		$connection = DriverManager::getConnection($this->connectionParams, $this->databaseConfiguration);
		$this->platform = $connection->getDatabasePlatform();

		// Send a query to create a connection
		$connection->query($this->platform->getDummySelectSQL());

		$this->logger = $connection->getConfiguration()->getSQLLogger();

		// We need to map the enum type to string because Doctrine don't support it native
		// This is necessary when the installer loops through all tables of all databases it found using this connection
		// See https://github.com/barryvdh/laravel-ide-helper/issues/19
		$this->platform->registerDoctrineTypeMapping('enum', 'string');
		$this->schemaManager = $connection->getSchemaManager();

		return $connection;
	}

	/**
	 * @throws \RuntimeException
	 * @return bool
	 */
	private function checkConnectivity() {
		$connected = FALSE;
		if ($this->isConnected()) {
			$connected = TRUE;
		} else {
			GeneralUtility::sysLog(
				'Could not connect to ' . $this->getName() . ' server ' . $this->getDatabaseHost() . ' with user ' . $this->getDatabaseUsername() . ': ' . $this->sqlErrorMessage(),
				'Core',
				GeneralUtility::SYSLOG_SEVERITY_FATAL
			);

			$this->close();

			throw new \RuntimeException(
				'TYPO3 Fatal Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!',
				1270853884
			);
		}

		return $connected;
	}

	/**
	 * Prepare user defined objects (if any) for hooks which extend query methods
	 *
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	private function prepareHooks() {
		$this->preProcessHookObjects = array();
		$this->postProcessHookObjects = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors'] as $classRef) {
				$hookObject = GeneralUtility::getUserObj($classRef);
				if (!(
					$hookObject instanceof \TYPO3\CMS\Core\Database\PreProcessQueryHookInterface
					|| $hookObject instanceof \TYPO3\CMS\Core\Database\PostProcessQueryHookInterface
				)) {
					throw new \UnexpectedValueException(
						'$hookObject must either implement interface TYPO3\\CMS\\Core\\Database\\PreProcessQueryHookInterface or interface TYPO3\\CMS\\Core\\Database\\PostProcessQueryHookInterface',
						1299158548
					);
				}
				if ($hookObject instanceof \TYPO3\CMS\Core\Database\PreProcessQueryHookInterface) {
					$this->preProcessHookObjects[] = $hookObject;
				}
				if ($hookObject instanceof \TYPO3\CMS\Core\Database\PostProcessQueryHookInterface) {
					$this->postProcessHookObjects[] = $hookObject;
				}
			}
		}
	}

	/**
	 * Select a SQL database
	 *
	 * @throws \RuntimeException
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function selectDb() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$isConnected = $this->isConnected();

		if (!$isConnected) {
			GeneralUtility::sysLog(
				'Could not select ' . $this->getName() . ' database ' . $this->getDatabaseName() . ': ' . $this->sqlErrorMessage(),
				'Core',
				GeneralUtility::SYSLOG_SEVERITY_FATAL
			);

			throw new \RuntimeException(
				'TYPO3 Fatal Error: Cannot connect to the current database, "' . $this->getDatabaseName() . '"!',
				1270853883
			);
		}

		return $isConnected;
	}

	/**
	 * Send initializing query to the database to prepare the database for TYPO3
	 *
	 * @return void
	 */
	private function initCommandsAfterConnect() {
		foreach ($this->initializeCommandsAfterConnect as $command) {
			if ($this->query($command) === FALSE) {
				GeneralUtility::sysLog(
					'Could not initialize DB connection with query "' . $command . '": ' . $this->sqlErrorMessage(),
					'Core',
					GeneralUtility::SYSLOG_SEVERITY_ERROR
				);
			}
		}

		if ($this->getDatabaseDriver() === 'pdo_mysql') {
			$this->setSqlMode();
		}
	}

	/**
	 * Closes the connection.
	 *
	 * @return void
	 */
	public function close() {
		$this->link->close();
		$this->isConnected = FALSE;
	}

	/**
	 * Fixes the SQL mode by unsetting NO_BACKSLASH_ESCAPES if found.
	 *
	 * @return void
	 * @todo: Test the server with different modes
	 *        see http://dev.mysql.com/doc/refman/5.1/de/server-sql-mode.html
	 */
	protected function setSqlMode() {
		$resource = $this->adminQuery('SELECT @@SESSION.sql_mode;');
		if ($resource) {
			$result = $this->fetchAll($resource);
			if (isset($result[0]) && $result[0] && strpos($result[0]['@@SESSION.sql_mode'], 'NO_BACKSLASH_ESCAPES') !== FALSE) {
				$modes = array_diff(GeneralUtility::trimExplode(',', $result[0]['@@SESSION.sql_mode']), array('NO_BACKSLASH_ESCAPES'));
				$stmt = $this->link->prepare('SET sql_mode = :modes');
				$stmt->bindValue('modes', implode(',', $modes));
				$stmt->execute();
				GeneralUtility::sysLog(
					'NO_BACKSLASH_ESCAPES could not be removed from SQL mode: ' . $this->sqlErrorMessage(),
					'Core',
					GeneralUtility::SYSLOG_SEVERITY_ERROR
				);
			}
		}
	}

	/**
	 * Checks if database is connected
	 *
	 * @return boolean
	 * @api
	 */
	public function isConnected() {
		if (is_object($this->link)) {
			$this->isConnected = $this->link->isConnected();
		}

		return $this->isConnected;
	}

	/**
	 * Disconnect from database if connected
	 *
	 * @return void
	 * @api
	 */
	public function disconnectIfConnected() {
		if ($this->isConnected) {
			$this->close();
		}
	}

	/**
	 * Returns the error number on the last query() execution
	 *
	 * @return integer PDO error number
	 * @api
	 */
	public function sqlErrorCode() {
		return $this->link->errorCode();
	}

	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string PDO error string.
	 * @api
	 */
	public function sqlErrorMessage() {
		$errorMsg = $this->link->errorInfo();

		return $errorMsg[0] === '00000' ? '' : $errorMsg;
	}

	/**
	 * Executes a query against the DBMS
	 *
	 * @param string $query
	 *
	 * @return \Doctrine\DBAL\Statement
	 */
	public function query($query) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$stmt = $this->link->query($query);

		$this->affectedRows = $stmt->rowCount();

		return $stmt;
	}

	/**
	 * Wrapper function for Statement::fetchAll()
	 *
	 * @param \Doctrine\DBAL\Driver\Statement $stmt A PDOStatement object
	 *
	 * @return boolean|array
	 * @api
	 */
	public function fetchAll($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->fetchAll();
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * Wrapper function for Statement::fetch(\PDO::FETCH_ASSOC)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement $stmt A PDOStatement object
	 *
	 * @return boolean|array Associative array of result row.
	 * @api
	 */
	public function fetchAssoc($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->fetch(\PDO::FETCH_ASSOC);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * Wrapper function for Statement::fetch(\PDO::FETCH_NUM)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement $stmt A PDOStatement object
	 *
	 * @return boolean|array Array with result rows.
	 * @api
	 */
	public function fetchRow($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->fetch(\PDO::FETCH_NUM);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains only a single requested column from the next row in the result set
	 * Wrapper function for Statement::fetch(\PDO::FETCH_COLUMN)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement $stmt A PDOStatement object
	 *
	 * @return boolean|array Array with result rows.
	 * @api
	 */
	public function fetchColumn($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->fetch(\PDO::FETCH_COLUMN);
		} else {
			return FALSE;
		}
	}

	/**
	 * Free result memory
	 * Wrapper function for Doctrine/PDO closeCursor()
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt A PDOStatement
	 *
	 * @return boolean Returns NULL on success or FALSE on failure.
	 * @api
	 */
	public function freeResult($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->closeCursor();
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return integer The uid of the last inserted record.
	 * @api
	 */
	public function getLastInsertId() {
		return (int)$this->link->lastInsertId();
	}

	/**
	 * Creates a DELETE query object
	 *
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\DeleteQueryInterface
	 * @api
	 */
	public function createDeleteQuery() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\DeleteQuery', $this->link);
	}

	/**
	 * Executes a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table Database table name
	 * @param array  $where The deletion criteria. An associative array containing column-value pairs eg. array('uid' => 1).
	 * @param array  $types The types of identifiers.
	 *
	 * @return integer The affected rows
	 */
	public function executeDeleteQuery($table, array $where, array $types = array()) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		if (empty($types)) {
			foreach ($where as $key => $value) {
				if (is_int($value)) {
					$types[$key] = \PDO::PARAM_INT;
				} else if (is_string($value)) {
					$types[$key] = \PDO::PARAM_STR;
				}
			}
		}

		$this->affectedRows = $this->link->delete($table, $where, $types);

		if ($this->getDebugMode()) {
			$this->debug('executeDeleteQuery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_DELETEquery_postProcessAction($table, $where, $this);
		}

		return $this->affectedRows;
	}

	/**
	 * Creates and returns a SQL DELETE statement on a table without executes it
	 *
	 * @param string $tableName Database table name
	 * @param array  $where The deletion criteria. An associative array containing column-value pairs eg. array('uid' => 1).
	 * @param array  $types The types of identifiers.
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\DeleteQuery
	 */
	public function deleteQuery($tableName, array $where = array(), array $types = array()) {
		$query = $this->createDeleteQuery();
		$query->delete($tableName);

		$whereArray = array();

		if (count($where)) {
			foreach ($where as $columnName => $value) {
				$whereArray[] = $columnName . '=' . $value;
			}

			call_user_func_array(array($query, 'where'), $whereArray);
		}

		return $query;
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param string $table See exec_TRUNCATEquery()
	 *
	 * @return string|\TYPO3\DoctrineDbal\Persistence\Doctrine\TruncateQuery
	 * @api
	 */
	public function createTruncateQuery($table = '') {
		if ($table === '') {
			return GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\TruncateQuery', $this->link);
		} else  {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->TRUNCATEquery_preProcessAction($table, $this);
			}

			$dbPlatform = $this->link->getDatabasePlatform();
			$query = $dbPlatform->getTruncateTableSQL($table);

			if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
				$this->debug_lastBuiltQuery = $query;
			}

			return $query;
		}
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $table Database table name
	 *
	 * @return integer The affected rows
	 */
	public function executeTruncateQuery($table) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$this->affectedRows = $this->link->executeUpdate($this->createTruncateQuery($table));

		if ($this->getDebugMode()) {
			$this->debug('executeTruncateQuery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_TRUNCATEquery_postProcessAction($table, $this);
		}

		return $this->affectedRows;
	}

	/**
	 * Creates an UPDATE query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\UpdateQueryInterface
	 * @api
	 */
	public function createUpdateQuery() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\UpdateQuery', $this->link);
	}

	/**
	 * Executes an SQL UPDATE statement on a table.
	 *
	 * @param string $tableName The name of the table to update.
	 * @param array  $where     The update criteria. An associative array containing column-value pairs.
	 * @param array  $data      An associative array containing column-value pairs.
	 * @param array  $types     Types of the merged $data and $identifier arrays in that order.
	 *
	 * @return integer The number of affected rows.
	 */
	public function executeUpdateQuery($tableName, array $where, array $data, array $types = array()) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$this->affectedRows = $this->link->update($tableName, $data, $where, $types);

		if ($this->getDebugMode()) {
			$this->debug('executeUpdateQuery');
		}

		foreach($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_UPDATEquery_postProcessAction($tableName, $where, $data, FALSE, $this);
		}

		return $this->affectedRows;
	}

	/**
	 * Creates and returns a SQL UPDATE statement on a table without executes it
	 *
	 * @param string $tableName The name of the table to update.
	 * @param array  $where     The update criteria. An associative array containing column-value pairs.
	 * @param array  $data
	 * @param bool   $noQuoteFields
	 * @param array  $types     Types of the merged $data and $identifier arrays in that order.
	 *
	 * @throws \InvalidArgumentException
	 * @internal param array $data An associative array containing column-value pairs.
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\DeleteQuery
	 */
	public function updateQuery($tableName, array $where, array $data, $noQuoteFields = FALSE, array $types = array()) {
		$query = $this->createUpdateQuery();
		$query->update($tableName);

		foreach ($data as $columnName => $column) {
			$query->set($columnName, $column);
		}
		$whereArray = array();

		if (count($where)) {
			foreach ($where as $columnName => $value) {
				$whereArray[] = $columnName . '=' . $value;
			}
		}

		call_user_func_array(array($query, 'where'), $whereArray);

		return $query;
	}

	/**
	 * Creates an INSERT query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\InsertQueryInterface
	 * @api
	 */
	public function createInsertQuery() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\InsertQuery', $this->link);
	}

	/**
	 * Executes a INSERT SQL-statement for $table where $where-clause
	 *
	 * @param string $table Database table name
	 * @param array  $where The deletion criteria. An associative array containing column-value pairs eg. array('uid' => 1).
	 * @param array  $types The types of identifiers.
	 *
	 * @return integer The affected rows
	 */
	public function executeInsertQuery($table, array $where, array $types = array()) {
		if (!$this->isConnected) {
			$this->connect();
		}

		if (empty($types)) {
			foreach ($where as $key => $value) {
				if (is_int($value)) {
					$types[$key] = \PDO::PARAM_INT;
				} else if (is_string($value)) {
					$types[$key] = \PDO::PARAM_STR;
				}
			}
		}

		$this->affectedRows = $this->link->insert($table, $where, $types);

		if ($this->getDebugMode()) {
			$this->debug('executeInsertQuery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_INSERTquery_postProcessAction($table, $where, FALSE, $this);
		}

		return $this->affectedRows;
	}

	/**
	 * Creates a SELECT query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\SelectQueryInterface
	 * @api
	 */
	public function createSelectQuery() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\SelectQuery', $this->link);
	}

	/**
	 * Creates a SELECT SQL-statement
	 *
	 * @param string $selectFields See exec_SELECTquery()
	 * @param string $fromTable    See exec_SELECTquery()
	 * @param string $whereClause  See exec_SELECTquery()
	 * @param string $groupBy      See exec_SELECTquery()
	 * @param string $orderBy      See exec_SELECTquery()
	 * @param string $limit        See exec_SELECTquery()
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery A select query object
	 */
	public function selectQueryDoctrine($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '') {
		if ($this->isConnected) {
			$this->connectDB();
		}

		$query = $this->createSelectQuery();
		$query->select($selectFields)->from($fromTable)->where($whereClause);
		if (!empty($groupBy)) {
			$query->groupBy($groupBy);
		}

		$direction = SelectQueryInterface::ASC;

		if (!empty($orderBy)) {
			$orderBy = explode(' ', $orderBy);
			if (count($orderBy) > 1) {
				switch ($orderBy[1]) {
					case 'ASC':
						$direction = SelectQueryInterface::ASC;
						break;
					case 'DESC':
						$direction = SelectQueryInterface::DESC;
						break;
					default:
						$direction = SelectQueryInterface::ASC;
				}
			}

			$query->orderBy($orderBy[0], $direction);
		}

		if (!empty($limit)) {
			$query->limit($limit);
		}

		return $query;
	}

	/**
	 * Returns the expressions instance
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\ExpressionInterface
	 * @api
	 */
	public function expr() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\Expression', $this->link);
	}

	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * This is only used as a service function in the (1-2-3 process) of the Install Tool.
	 * In any case a lookup should be done in the _DEFAULT handler DBMS then.
	 * Use in Install Tool only!
	 *
	 * @return array Each entry represents a database name
	 * @throws \RuntimeException
	 */
	public function listDatabases() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		try {
			// SQLite doesn't support this command
			$databases = $this->schemaManager->listDatabases();
			if (empty($databases)) {
				throw new \RuntimeException(
					'MySQL Error: Cannot get databases: "' . $this->sqlErrorMessage() . '"!',
					1378457171
				);
			}
		} catch (\Doctrine\DBAL\DBALException $e) {
			$databases[] = '';
		}


		return $databases;
	}

	/**
	 * Returns the list of tables from the default database
	 *
	 * @return array Array with table names as key and arrays with status information as value
	 */
	public function listTables() {
		$tables = array();
		if ($this->getDatabaseDriver() === 'pdo_pgsql') {
			$tables = $this->schemaManager->listTables();
		} else {
			$stmt = $this->adminQuery('SHOW TABLE STATUS FROM `' . $this->getDatabaseName() . '`');
			if ($stmt !== FALSE) {
				while ($theTable = $this->fetchAssoc($stmt)) {
					$tables[$theTable['Name']] = $theTable;
				}
			}
		}


		// TODO: Figure out how to use this
//		$testTables = array();
//		$tables = $this->schema->listTables();
//		if ($tables !== FALSE) {
//			foreach ($tables as $table) {
//				$testTables[$table->getName()] = array(
//													'columns' => $table->getColumns(),
//													'indices' => $table->getIndexes()
//												);
//			}
//		}

		return $tables;
	}

	/**
	 * This returns the count of the tables from the selected database
	 *
	 * @return int
	 */
	public function countTables() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		return count($this->schemaManager->listTables());
	}

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
	public function listFields($tableName) {
		$fields = array();
		// TODO: Figure out if we could use the function $this->schema->listTableColumns($tableName);
		//       The result is a different from the current. We need to adjust assembleFieldDefinition() from
		//       SqlSchemaMigrationService
		$stmt = $this->adminQuery('SHOW COLUMNS FROM `' . $tableName . '`');

		if ($stmt !== FALSE) {
			while ($fieldRow = $this->fetchAssoc($stmt)) {
				$fields[$fieldRow['Field']] = $fieldRow;
			}
			$stmt->closeCursor();
		}

		return $fields;
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Key information in a associative array
	 */
	public function listKeys($tableName) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$keys = array();

		$stmt = $this->adminQuery('SHOW KEYS FROM `' . $tableName . '`');
		if ($stmt !== FALSE) {
			while ($keyRow = $this->fetchAssoc($stmt)) {
				$keys[] = $keyRow;
			}
			$stmt->closeCursor();
		}

		return $keys;
	}

	/**
	 * Returns information about the character sets supported by the current DBM
	 * This function is important not only for the Install Tool but probably for
	 * DBALs as well since they might need to look up table specific information
	 * in order to construct correct queries. In such cases this information should
	 * probably be cached for quick delivery.
	 *
	 * This is used by the Install Tool to convert tables tables with non-UTF8 charsets
	 * Use in Install Tool only!
	 *
	 * @return array Array with Charset as key and an array of "Charset", "Description", "Default collation", "Maxlen" as values
	 */
	public function listDatabaseCharsets() {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$output = array();

		if ($this->getDatabaseDriver() === 'pdo_pgsql') {
			$stmt = $this->adminQuery('SHOW SERVER_ENCODING');
		} else {
			$stmt = $this->adminQuery('SHOW CHARACTER SET');
		}


		if ($stmt !== FALSE) {
			while ($row = $this->fetchAssoc($stmt)) {
				$output[$row['Charset']] = $row;
			}
			$stmt->closeCursor();
		}

		return $output;
	}

	/**
	 * Doctrine query wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 *
	 * @param string $query Query to execute
	 *
	 * @return boolean|\Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function adminQuery($query) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$stmt = $this->link->query($query);

		if ($this->isDebugMode) {
			$this->debug('adminQuery', $query);
		}

		return $stmt;
	}

	/**
	 * Creates a table using the Doctrine API
	 *
	 * @param string $tableName
	 *
	 * @return \Doctrine\DBAL\Schema\Table
	 */
	public function createTable($tableName) {
		return $this->schema->createTable($tableName);
	}

	/**
	 * Drops a table
	 *
	 * @param string $tableName
	 *
	 * @return void
	 */
	public function dropTable($tableName) {
		$this->schema->dropTable($tableName);
	}

	/**
	 * Will force all entries in the input comma list to integers
	 * Useful when you want to make sure a commalist of supposed integers really contain only integers; You want to know that when you don't trust content that could go into an SQL statement.
	 *
	 * @param string $list List of comma-separated values which should be integers
	 *
	 * @return string The input list but with every value passed through intval()
	 * @see cleanIntArray()
	 */
	public function cleanIntList($list) {
		return implode(',', GeneralUtility::intExplode(',', $list));
	}

	/**
	 * Will convert all values in the one-dimensional array to integers.
	 * Useful when you want to make sure an array contains only integers before imploding them in a select-list.
	 *
	 * @param array $arr Array with values
	 *
	 * @return array The input array with all values passed through intval()
	 * @see cleanIntList()
	 */
	public function cleanIntArray($arr) {
		foreach ($arr as $k => $v) {
			$arr[$k] = (int)$arr[$k];
		}

		return $arr;
	}

	/**
	 * Escaping and quoting values for SQL statements.
	 *
	 * @param string  $string    Input string
	 * @param boolean $allowNull Whether to allow NULL values
	 *
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @api
	 */
	public function quote($string, $allowNull = FALSE) {
		if ($allowNull && $string === NULL) {
			return 'NULL';
		}

		return $this->link->quote($string);
	}

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
	public function quoteColumn($columnName, $tableName = NULL) {
		return ($tableName ? $this->quoteTable($tableName) . '.' : '') .
				$this->quoteIdentifier($columnName);
	}

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
	public function quoteTable($tableName) {
		return $this->quoteIdentifier($tableName);
	}

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
	public function quoteIdentifier($identifier) {
		return '`' . $identifier . '`';
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 *
	 * @return int
	 * @api
	 */
	public function getAffectedRows() {
		return (int)$this->affectedRows;
	}

	/**
	 * Returns the number of selected rows.
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt
	 *
	 * @return integer Number of resulting rows
	 */
	public function getResultRowCount(Statement $stmt){
		if ($this->debug_check_recordset($stmt)) {
			$result = $stmt->rowCount();
		} else {
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * Get the type of the specified field in a result
	 * mysql_field_type() wrapper function
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt   A PDOStatement object
	 * @param                                         $table
	 * @param integer                                 $column Field index.
	 *
	 * @return string Returns the name of the specified field index, or FALSE on error
	 */
	public function getSqlFieldType($stmt, $table, $column) {
		// mysql_field_type compatibility map
		// taken from: http://www.php.net/manual/en/mysqli-result.fetch-field-direct.php#89117
		// Constant numbers see http://php.net/manual/en/mysqli.constants.php

		$mysqlDataTypeHash = array(
			'boolean'      => 'boolean',
			'smallint'     => 'smallint',
			'integer'      => 'int',
			'float'        => 'float',
			'double'       => 'double',
			'timestamp'    => 'timestamp',
			'bigint'       => 'bigint',
			'mediumint'    => 'mediumint',
			'date'         => 'date',
			'time'         => 'time',
			'datetime'     => 'datetime',
			'text'         => 'varchar',
			'string'       => 'varchar',
			'decimal'      => 'decimal',
			'blob'         => 'blob',
			'guid'         => 'guid',
			'object'       => 'object',
			'datetimetz'   => 'datetimetz',
			'json_array'   => 'json_array',
			'simple_array' => 'simple_array',
			'array'        => 'array',
		);

		if ($this->debug_check_recordset($stmt)) {
			if (count($this->schema->getTables()) === 0) {
				$this->schema = $this->link->getSchemaManager()->createSchema();
			}

			$metaInfo = $this->schema
				->getTable($table)
				->getColumn($column)
				->getType()
				->getName();

			if ($metaInfo === FALSE) {
				return FALSE;
			}

			return $mysqlDataTypeHash[$metaInfo];
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the date and time formats compatible with the given database table.
	 *
	 * @param string $table Table name for which to return an empty date. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how date and time should be formatted).
	 *
	 * @return array
	 */
	public function getDateTimeFormats($table) {
		return array(
			'date' => array(
				'empty' => '0000-00-00',
				'format' => 'Y-m-d'
			),
			'datetime' => array(
				'empty' => '0000-00-00 00:00:00',
				'format' => 'Y-m-d H:i:s'
			)
		);
	}

	/******************************
	 *
	 * Debugging
	 *
	 ******************************/
	/**
	 * Debug function: Outputs error if any
	 *
	 * @param string $func Function calling debug()
	 * @param string $query Last query if not last built query
	 * @return void
	 */
	protected function debug($func, $query = '') {
		$errorMessage = $this->sqlErrorMessage();
		$errorCode = $this->sqlErrorCode();
		if ($errorMessage || (int)$this->isDebugMode === 2 || $this->showErrors) {
			DebugUtility::debug(
				array(
					'caller' => get_class() . '::' . $func,
					'Errormessage' => $errorMessage,
					'Errorcode' => $errorCode,
					'lastBuiltQuery' => $query ? $query : $this->getLastStatement(),
					'debug_backtrace' => DebugUtility::debugTrail()
				),
				$func,
				is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'], 'debug')) ? '' : 'DB Error'
			);
		}
	}

	/**
	 * Checks if record set is valid and writes debugging information into devLog if not.
	 *
	 * @param boolean|\mysqli_result|object MySQLi result object / DBAL object
	 *
	 * @return boolean TRUE if the record set is valid, FALSE otherwise
	 * @todo Define visibility
	 */
	public function debug_check_recordset($res) {
		if ($res !== FALSE) {
			return TRUE;
		}
		$msg = 'Invalid database result detected';
		$trace = debug_backtrace();
		array_shift($trace);
		$cnt = count($trace);
		for ($i = 0; $i < $cnt; $i++) {
			// Complete objects are too large for the log
			if (isset($trace['object'])) {
				unset($trace['object']);
			}
		}
		$msg .= ': function ' . get_class() . '->' . $trace[0]['function'] . ' called from file ' . substr($trace[0]['file'], (strlen(PATH_site) + 2)) . ' in line ' . $trace[0]['line'];
		GeneralUtility::sysLog(
			$msg . '. Use a devLog extension to get more details.',
			'Core/Database/DatabaseConnection',
			GeneralUtility::SYSLOG_SEVERITY_ERROR
		);
		// Send to devLog if enabled
		if (TYPO3_DLOG) {
			$debugLogData = array(
				'SQL Error' => $this->sqlErrorMessage(),
				'Backtrace' => $trace
			);
			if ($this->debug_lastBuiltQuery) {
				$debugLogData = array('SQL Query' => $this->getLastStatement()) + $debugLogData;
			}
			GeneralUtility::devLog($msg . '.', 'Core/Database/DatabaseConnection', 3, $debugLogData);
		}

		return FALSE;
	}

	/**
	 * Serialize destructs current connection
	 *
	 * @return array All protected properties that should be saved
	 * @todo Add the missing members here
	 */
	public function __sleep() {
		$this->disconnectIfConnected();
		return array(
			'debugOutput',
			'databaseHost',
			'databasePort',
			'databaseSocket',
			'databaseName',
			'databaseUsername',
			'databaseUserPassword',
			'connectionParams',
			'persistentDatabaseConnection',
			'connectionCompression',
			'initializeCommandsAfterConnect',
			'defaultCharset',
			'logger',
			'link',
			'schema',
			'schemaManager',
			'platform'
		);
	}

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @return void
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @api
	 */
	public function connectDB() {
		$this->connectDatabase();
	}
}

