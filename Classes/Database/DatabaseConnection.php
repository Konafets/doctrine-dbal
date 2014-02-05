<?php
namespace TYPO3\DoctrineDbal\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\DoctrineDbal\Persistence\Doctrine\Query;

/**
 * Contains the class "DatabaseConnection" containing functions for building SQL queries
 * and mysqli wrappers, thus providing a foundational API to all database
 * interaction.
 * This class is instantiated globally as $TYPO3_DB in TYPO3 scripts.
 *
 * TYPO3 "database wrapper" class (new in 3.6.0)
 * This class contains
 * - abstraction functions for executing INSERT/UPDATE/DELETE/SELECT queries ("Query execution"; These are REQUIRED for all future connectivity to the database, thus ensuring DBAL compliance!)
 * - functions for building SQL queries (INSERT/UPDATE/DELETE/SELECT) ("Query building"); These are transitional functions for building SQL queries in a more automated way. Use these to build queries instead of doing it manually in your code!
 * - mysqli wrapper functions; These are transitional functions. By a simple search/replace you should be able to substitute all mysql*() calls with $GLOBALS['TYPO3_DB']->sql*() and your application will work out of the box. YOU CANNOT (legally) use any mysqli functions not found as wrapper functions in this class!
 * See the Project Coding Guidelines (doc_core_cgl) for more instructions on best-practise
 *
 * This class is not in itself a complete database abstraction layer but can be extended to be a DBAL (by extensions, see "dbal" for example)
 * ALL connectivity to the database in TYPO3 must be done through this class!
 * The points of this class are:
 * - To direct all database calls through this class so it becomes possible to implement DBAL with extensions.
 * - To keep it very easy to use for developers used to MySQL in PHP - and preserve as much performance as possible when TYPO3 is used with MySQL directly...
 * - To create an interface for DBAL implemented by extensions; (Eg. making possible escaping characters, clob/blob handling, reserved words handling)
 * - Benchmarking the DB bottleneck queries will become much easier; Will make it easier to find optimization possibilities.
 *
 * USE:
 * In all TYPO3 scripts the global variable $TYPO3_DB is an instance of this class. Use that.
 * Eg. $GLOBALS['TYPO3_DB']->sql_fetch_assoc()
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DatabaseConnection extends \TYPO3\CMS\Core\Database\DatabaseConnection {

	/**
	 * The AND constraint in where clause
	 *
	 * @var string
	 */
	const AND_Constraint = 'AND';

	/**
	 * The OR constraint in where clause
	 *
	 * @var string
	 */
	const OR_Constraint = 'OR';

	/**
	 * Set "TRUE" or "1" if you want database errors outputted. Set to "2" if you also want successful database actions outputted.
	 *
	 * @todo Define visibility
	 */
	public $debugOutput = FALSE;

	/**
	 * Internally: Set to last built query (not necessarily executed...)
	 *
	 * @todo Define visibility
	 */
	public $debug_lastBuiltQuery = '';

	/**
	 * Set "TRUE" if you want the last built query to be stored in $debug_lastBuiltQuery independent of $this->debugOutput
	 *
	 * @todo Define visibility
	 */
	public $store_lastBuiltQuery = FALSE;

	/**
	 * Set this to 1 to get queries explained (devIPmask must match). Set the value to 2 to the same but disregarding the devIPmask.
	 * There is an alternative option to enable explain output in the admin panel under "TypoScript", which will produce much nicer output, but only works in FE.
	 *
	 * @todo Define visibility
	 */
	public $explainOutput = 0;

	/**
	 * @var string Database host to connect to
	 */
	protected $databaseHost = '';

	/**
	 * @var integer Database port to connect to
	 */
	protected $databasePort = 3306;

	/**
	 * @var string|NULL Database socket to connect to
	 */
	protected $databaseSocket = NULL;

	/**
	 * @var string Database name to connect to
	 */
	protected $databaseName = '';

	/**
	 * @var string Database user to connect with
	 */
	protected $databaseUsername = '';

	/**
	 * @var string Database password to connect with
	 */
	protected $databaseUserPassword = '';

	/**
	 * @var string The configuration for Doctrine DBAL
	 */
	protected $connectionConfig = '';

	/**
	 * @var array The connection settings for Doctrine
	 */
	protected $connectionParams = array(
		'dbname' => '',
		'user' => '',
		'password' => '',
		'host' => '',
		'driver' => 'pdo_mysql',
		'port' => NULL,
		'charset' => 'utf8',
	);

	/**
	 * The database schema
	 *
	 * @var \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
	 */
	protected $schema;

	/**
	 * The last executed statement object
	 *
	 * @var \Doctrine\DBAL\Statement $lastStatement
	 */
	protected $lastStatement = '';

	/**
	 * The table form last query
	 *
	 * @var string $table
	 */
	protected $table = '';

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
	protected $logger = '';

	/**
	 * @var int The affected rows from the last UPDATE, INSERT or DELETE query
	 */
	protected $affectedRows = -1;

	/**
	 * Default character set, applies unless character set or collation are explicitly set
	 *
	 * @todo Define visibility
	 */
	public $defaultCharset = 'utf8';

	/**
	 * @var array<PostProcessQueryHookInterface>
	 */
	protected $preProcessHookObjects = array();

	/**
	 * @var array<PreProcessQueryHookInterface>
	 */
	protected $postProcessHookObjects = array();

	/******************************
	 *
	 * Setters / Getters
	 *
	 ******************************/

	/**
	 * Set database username
	 *
	 * @param string $username
	 * @return $this
	 */
	public function setDatabaseUsername($username) {
		$this->disconnectIfConnected();
		$this->databaseUsername = $username;
		$this->connectionParams['user'] = $username;

		return $this;
	}

	/**
	 * Returns the database username
	 * @return string
	 */
	public function getDatabaseUsername() {
		return $this->connectionParams['user'];
	}

	/**
	 * Set database password
	 *
	 * @param string $password
	 * @return $this
	 */
	public function setDatabasePassword($password) {
		$this->disconnectIfConnected();
		$this->databaseUserPassword = $password;
		$this->connectionParams['password'] = $password;

		return $this;
	}

	/**
	 * Set database name
	 *
	 * @param string $name
	 * @return $this
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
	 */
	public function getDatabaseDriver() {
		return $this->connectionParams['driver'];
	}

	/**
	 * Set database socket
	 *
	 * @param string|NULL $socket
	 * @return $this
	 */
	public function setDatabaseSocket($socket = NULL) {
		$this->disconnectIfConnected();
		$this->databaseSocket = $socket;
		//$this->connectionParams['unix_socket'] = $socket;

		return $this;
	}

	/**
	 * Returns the database socket
	 *
	 * @return NULL|string
	 */
	public function getDatabaseSocket() {
		return $this->databaseSocket;
	}

	/**
	 * Set database port
	 *
	 * @param integer $port
	 * @return $this
	 */
	public function setDatabasePort($port = 3306) {
		$this->disconnectIfConnected();
		$this->databasePort = (int) $port;
		$this->connectionParams['port'] = (int) $port;

		return $this;
	}

	/**
	 * Returns the database port
	 * @return int
	 */
	public function getDatabasePort() {
		return (int) $this->connectionParams['port'];
	}

	/**
	 * Set database host
	 *
	 * @param string $host
	 * @return $this
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
	 */
	public function getDatabaseHost() {
		return $this->connectionParams['host'];
	}

	/**
	 * Set default charset
	 *
	 * @param string $charset
	 * @return $this
	 */
	public function setDatabaseCharset($charset = 'utf8') {
		$this->defaultCharset = $charset;

		return $this;
	}

	/**
	 * Returns the default charset
	 *
	 * @return mixed
	 */
	public function getDatabaseCharset() {
		return $this->defaultCharset;
	}

	/**
	 * Set current database handle, usually \mysqli
	 *
	 * @param \Doctrine\DBAL\Connection $handle
	 *
	 * @return void
	 */
	public function setDatabaseHandle($handle) {
		$this->link = $handle;
	}

	/**
	 * Returns current database handle
	 *
	 * @return \Doctrine\DBAL\Connection|NULL
	 */
	public function getDatabaseHandle() {
		return $this->link;
	}

	/**
	 * @param \Doctrine\DBAL\Driver\Statement $lastStatement
	 */
	public function setLastStatement($lastStatement) {
		$this->lastStatement = $lastStatement;
	}

	/**
	 * @return \Doctrine\DBAL\Driver\Statement
	 */
	public function getLastStatement() {
		$queries = $this->logger->queries;
		$currentQuery = $this->logger->currentQuery;
		$lastStatement = $queries[$currentQuery]['sql'];

		return $lastStatement;
	}

	/**
	 * Set commands to be fired after connection was established
	 *
	 * @param array $commands List of SQL commands to be executed after connect
	 */
	public function setInitializeCommandsAfterConnect(array $commands) {
		$this->disconnectIfConnected();
		$this->initializeCommandsAfterConnect = $commands;
	}

	/**
	 * Set connection compression. Might be an advantage, if SQL server is not on localhost
	 *
	 * @param bool $connectionCompression TRUE if connection should be compressed
	 */
	public function setConnectionCompression($connectionCompression) {
		$this->disconnectIfConnected();
		$this->connectionCompression = (bool)$connectionCompression;
	}

	/**
	 * Set persistent database connection
	 *
	 * @param boolean $persistentDatabaseConnection
	 *
	 * @see http://php.net/manual/de/mysqli.persistconns.php
	 */
	public function setPersistentDatabaseConnection($persistentDatabaseConnection) {
		$this->disconnectIfConnected();
		$this->persistentDatabaseConnection = (bool)$persistentDatabaseConnection;
	}

	/**
	 * Initialize Doctrine
	 *
	 * @param array $params The parameters.
	 */
	public function initDoctrine(array $params = array()) {
		//$this->connectionParams = $params;
		$this->connectionConfig = GeneralUtility::makeInstance('Doctrine\\DBAL\\Configuration');
		$this->connectionConfig->setSQLLogger(new DebugStack());
		$this->link = DriverManager::getConnection($this->connectionParams, $this->connectionConfig);
		$this->logger = $this->link->getConfiguration()->getSQLLogger();

		// We need to map the enum type to string because Doctrine don't support it native
		// This is necessary when the installer loops through all tables of all databases it found using this connection
		// See https://github.com/barryvdh/laravel-ide-helper/issues/19
		$this->link->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		$this->schema = $this->link->getSchemaManager();
	}

	/******************************
	 *
	 * Connect handling
	 *
	 ******************************/
	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @param string $host     Deprecated since 6.1, will be removed in two versions Database. host IP/domain[:port]
	 * @param string $username Deprecated since 6.1, will be removed in two versions. Username to connect with
	 * @param string $password Deprecated since 6.1, will be removed in two versions. Password to connect with
	 * @param string $db       Deprecated since 6.1, will be removed in two versions. Database name to connect to
	 *
	 * @return void
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 */
	public function connectDB($host = NULL, $username = NULL, $password = NULL, $db = NULL) {
		// Early return if connected already
		if ($this->isConnected) {
			return;
		}

		if (!$this->getDatabaseName() && !$db) {
			throw new \RuntimeException(
				'TYPO3 Fatal Error: No database selected!',
				1270853882
			);
		}

		if ($host || $username || $password || $db) {
			$this->handleDeprecatedConnectArguments($host, $username, $password, $db);
		}

		if ($this->sql_pconnect()) {
			if (!$this->sql_select_db()) {
				throw new \RuntimeException(
					'TYPO3 Fatal Error: Cannot connect to the current database, "' . $this->getDatabaseName() . '"!',
					1270853883
				);
			}
		} else {
			throw new \RuntimeException(
				'TYPO3 Fatal Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!',
				1270853884
			);
		}

		// Prepare user defined objects (if any) for hooks which extend query methods
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
	 * Open a (persistent) connection to a MySQL server
	 *
	 * @param string $host     Deprecated since 6.1, will be removed in two versions. Database host IP/domain[:port]
	 * @param string $username Deprecated since 6.1, will be removed in two versions. Username to connect with.
	 * @param string $password Deprecated since 6.1, will be removed in two versions. Password to connect with.
	 *
	 * @return boolean|void
	 * @throws \RuntimeException
	 */
	public function sql_pconnect($host = NULL, $username = NULL, $password = NULL) {
		if ($this->isConnected) {
			return $this->link;
		}

		if (!extension_loaded('pdo')) {
			throw new \RuntimeException(
				'Database Error: PHP PDO extension not loaded. This is a must to use this extension (ext:doctrine_dbal)!',
				// TODO: Replace with current date for Thesis
				1388496499
			);
		}

		if ($host || $username || $password) {
			$this->handleDeprecatedConnectArguments($host, $username, $password);
		}

		// TODO: Is this needed for Doctrine too?
		// TODO: This handles persistent database connection which established with a "p:" before the host for mysqli
		//       connections. For Doctrine this won't work. If the user want a persistent connection we have to create
		//       the PDO instance by ourself and pass it to Doctrine.
		//       See http://stackoverflow.com/questions/16217426/is-it-possible-to-use-doctrine-with-persistent-pdo-connections
		//           http://www.mysqlperformanceblog.com/2006/11/12/are-php-persistent-connections-evil/
		//$host = $this->persistentDatabaseConnection ? 'p:' . $this->databaseHost : $this->databaseHost;
		//$this->setDatabaseHost($this->databaseHost);

		$this->initDoctrine();

		// We need to map the enum type to string because Doctrine don't support it native
		// This is necessary when the installer loops through all tables of all databases it found using this connection
		// See https://github.com/barryvdh/laravel-ide-helper/issues/19
		$this->link->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		$this->schema = $this->link->getSchemaManager();

		try {
			$this->schema->listDatabases();
		} catch (\PDOException $e) {
			return FALSE;
		}

		$connected = $this->link->isConnected();
		if ($connected) {
			$this->isConnected = TRUE;
			foreach ($this->initializeCommandsAfterConnect as $command) {
				if ($this->link->query($command) === FALSE) {
					GeneralUtility::sysLog(
						'Could not initialize DB connection with query "' . $command . '": ' . $this->sqlErrorMessage(),
						'Core',
						GeneralUtility::SYSLOG_SEVERITY_ERROR
					);
				}
			}
			$this->setSqlMode();
		} else {
			// @TODO: This should raise an exception. Would be useful especially to work during installation.
			$this->link = NULL;
			GeneralUtility::sysLog(
				// TODO: Replace the term "MySQL" in the next log message with the current platform name
				'Could not connect to MySQL server ' . $this->getDatabaseHost() . ' with user ' . $this->getDatabaseUsername() . ': ' . $this->sqlErrorMessage(),
				'Core',
				GeneralUtility::SYSLOG_SEVERITY_FATAL
			);
		}

		return $this->link;
	}

	/**
	 * Checks if database is connected
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return $this->isConnected;
	}

	/**
	 * Disconnect from database if connected
	 *
	 * @return void
	 */
	protected function disconnectIfConnected() {
		if ($this->isConnected()) {
			$this->link->close();
			$this->isConnected = FALSE;
		}
	}

	/**
	 * Fixes the SQL mode by unsetting NO_BACKSLASH_ESCAPES if found.
	 *
	 * @return void
	 * @todo: Test the server with different modes
	 *        see http://dev.mysql.com/doc/refman/5.1/de/server-sql-mode.html
	 */
	protected function setSqlMode() {
		$resource = $this->sql_query('SELECT @@SESSION.sql_mode;');
		if ($resource) {
			// TODO: Abstract the direct fetchAll() call
			$result = $resource->fetchAll();
			if (isset($result[0]) && $result[0] && strpos($result[0]['@@SESSION.sql_mode'], 'NO_BACKSLASH_ESCAPES') !== FALSE) {
				$modes = array_diff(GeneralUtility::trimExplode(',', $result[0]['@@SESSION.sql_mode']), array('NO_BACKSLASH_ESCAPES'));
				// TODO: Make the prepared Statements working
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

	/************************************
	 * Fluent API
	 *
	 *
	 ************************************/

	/**
	 * Returns a new Doctrine QueryBuilder instance
	 *
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 */
	public function query(){
		return $this->link->createQueryBuilder();
	}

	/**
	 * Returns a new Query object
	 *
	 * @return Query
	 */
	public function createQuery() {
		if (!$this->isConnected()) {
			$this->connectDB();
		}
		return new Query($this->link);
	}

	/************************************
	 *
	 * Query execution
	 *
	 * These functions are the RECOMMENDED DBAL functions for use in your applications
	 * Using these functions will allow the DBAL to use alternative ways of accessing data (contrary to if a query is returned!)
	 * They compile a query AND execute it immediately and then return the result
	 * This principle heightens our ability to create various forms of DBAL of the functions.
	 * Generally: We want to return a result pointer/object, never queries.
	 * Also, having the table name together with the actual query execution allows us to direct the request to other databases.
	 *
	 **************************************/

	/**
	 * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fieldsValues.
	 * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
	 *
	 * @param string  $table         Table name
	 * @param array   $fieldsValues  Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param boolean $noQuoteFields See fullQuoteArray()
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function exec_INSERTquery($table, $fieldsValues, $noQuoteFields = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$stmt = $this->link->query($this->INSERTquery($table, $fieldsValues, $noQuoteFields));
		$this->setLastStatement($stmt);

		if ($this->debugOutput) {
			$this->debug('exec_INSERTquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_INSERTquery_postProcessAction($table, $fieldsValues, $noQuoteFields, $this);
		}

		return $stmt;
	}

	/**
	 * Creates and executes an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string  $table         Table name
	 * @param array   $fields        Field names
	 * @param array   $rows          Table rows. Each row should be an array with field values mapping to $fields
	 * @param boolean $noQuoteFields See fullQuoteArray()
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $noQuoteFields = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($this->INSERTmultipleRows($table, $fields, $rows, $noQuoteFields));
		if ($this->debugOutput) {
			$this->debug('exec_INSERTmultipleRows');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_INSERTmultipleRows_postProcessAction($table, $fields, $rows, $noQuoteFields, $this);
		}

		return $res;
	}

	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 *
	 * @param string $selectFields List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param string $fromTable    Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param string $whereClause  Additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param string $groupBy      Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy      Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit        Optional LIMIT value ([begin,]max), if none, supply blank string.
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function exec_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '') {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$query = $this->SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		$stmt = $this->link->query($query);

		$this->setLastStatement($stmt);
		$this->table = $fromTable;

		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		if ($this->explainOutput) {
			$this->explain($query, $fromTable, $stmt->num_rows);
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_SELECTquery_postProcessAction($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '', $this);
		}

		return $stmt;
	}

	/**
	 * Creates and executes a SELECT query, selecting fields ($select) from two/three tables joined
	 * Use $mmTable together with $localTable or $foreignTable to select over two tables. Or use all three tables to select the full MM-relation.
	 * The JOIN is done with [$localTable].uid <--> [$mmTable].uid_local  / [$mmTable].uid_foreign <--> [$foreignTable].uid
	 * The function is very useful for selecting MM-relations between tables adhering to the MM-format used by TCE (TYPO3 Core Engine). See the section on $GLOBALS['TCA'] in Inside TYPO3 for more details.
	 *
	 * @param string $select       Field list for SELECT
	 * @param string $localTable   Tablename, local table
	 * @param string $mmTable      Tablename, relation table
	 * @param string $foreignTable Tablename, foreign table
	 * @param string $whereClause  Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT! You have to prepend 'AND ' to this parameter yourself!
	 * @param string $groupBy      Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy      Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit        Optional LIMIT value ([begin,]max), if none, supply blank string.
	 *
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_mm_query($select, $localTable, $mmTable, $foreignTable, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '') {
		if ($foreignTable == $localTable) {
			$foreignTableAs = $foreignTable . uniqid('_join');
		}
		$mmWhere = $localTable ? $localTable . '.uid=' . $mmTable . '.uid_local' : '';
		$mmWhere .= ($localTable and $foreignTable) ? ' AND ' : '';
		$tables = ($localTable ? $localTable . ',' : '') . $mmTable;
		if ($foreignTable) {
			$mmWhere .= ($foreignTableAs ? $foreignTableAs : $foreignTable) . '.uid=' . $mmTable . '.uid_foreign';
			$tables .= ',' . $foreignTable . ($foreignTableAs ? ' AS ' . $foreignTableAs : '');
		}

		return $this->exec_SELECTquery($select, $tables, $mmWhere . ' ' . $whereClause, $groupBy, $orderBy, $limit);
	}

	/**
	 * Executes a select based on input query parts array
	 *
	 * @param array $queryParts Query parts array
	 *
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_queryArray($queryParts) {
		return $this->exec_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND traverse result set and returns array with records in.
	 *
	 * @param string $selectFields  See exec_SELECTquery()
	 * @param string $fromTable     See exec_SELECTquery()
	 * @param string $whereClause   See exec_SELECTquery()
	 * @param string $groupBy       See exec_SELECTquery()
	 * @param string $orderBy       See exec_SELECTquery()
	 * @param string $limit         See exec_SELECTquery()
	 * @param string $uidIndexField If set, the result array will carry this field names value as index. Requires that field to be selected of course!
	 *
	 * @return array|NULL Array of rows, or NULL in case of SQL error
	 */
	public function exec_SELECTgetRows($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
		$stmt = $this->exec_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		$this->setLastStatement($stmt);
		$this->table = $fromTable;

		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		if (!$this->sqlErrorMessage()) {
			$output = array();
			if ($uidIndexField) {
				while ($tempRow = $this->sql_fetch_assoc($stmt)) {
					$output[$tempRow[$uidIndexField]] = $tempRow;
				}
			} else {
				while ($output[] = $this->sql_fetch_assoc($stmt)) {

				}
				array_pop($output);
			}
			$this->sql_free_result($stmt);
		} else {
			$output = NULL;
		}

		return $output;
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND gets a result set and returns an array with a single record in.
	 * LIMIT is automatically set to 1 and can not be overridden.
	 *
	 * @param string  $selectFields List of fields to select from the table.
	 * @param string  $fromTable    Table(s) from which to select.
	 * @param string  $whereClause  Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param string  $groupBy      Optional GROUP BY field(s), if none, supply blank string.
	 * @param string  $orderBy      Optional ORDER BY field(s), if none, supply blank string.
	 * @param boolean $numIndex     If set, the result will be fetched with sql_fetch_row, otherwise sql_fetch_assoc will be used.
	 *
	 * @return array Single row or NULL if it fails.
	 */
	public function exec_SELECTgetSingleRow($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $numIndex = FALSE) {
		$stmt = $this->exec_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, '1');
		$this->setLastStatement($stmt);
		$this->table = $fromTable;

		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		$output = NULL;
		if ($stmt !== FALSE) {
			if ($numIndex) {
				$output = $this->sql_fetch_row($stmt);
			} else {
				$output = $this->sql_fetch_assoc($stmt);
			}
			$this->sql_free_result($stmt);
		}

		return $output;
	}

	/**
	 * Counts the number of rows in a table.
	 *
	 * @param string $field Name of the field to use in the COUNT() expression (e.g. '*')
	 * @param string $table Name of the table to count rows for
	 * @param string $where Optional WHERE statement of the query
	 *
	 * @return mixed Number of rows counter (integer) or FALSE if something went wrong (boolean)
	 */
	public function exec_SELECTcountRows($field, $table, $where = '') {
		$count = FALSE;
		$resultSet = $this->exec_SELECTquery('COUNT(' . $field . ')', $table, $where);
		if ($resultSet !== FALSE) {
			list($count) = $this->sql_fetch_row($resultSet);
			$count = intval($count);
			$this->sql_free_result($resultSet);
		}

		return $count;
	}

	/**
	 * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fieldsValues.
	 * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
	 *
	 * @param string  $table         Database tablename
	 * @param string  $where         WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param array   $fieldsValues  Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param boolean $noQuoteFields See fullQuoteArray()
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function exec_UPDATEquery($table, $where, $fieldsValues, $noQuoteFields = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$stmt = $this->link->query($this->UPDATEquery($table, $where, $fieldsValues, $noQuoteFields));
		$this->setLastStatement($stmt);

		if ($this->debugOutput) {
			$this->debug('exec_UPDATEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_UPDATEquery_postProcessAction($table, $where, $fieldsValues, $noQuoteFields, $this);
		}

		return $stmt;
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $table Database table name
	 *
	 * @return mixed Result from handler
	 */
	public function exec_TRUNCATEquery($table) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$stmt = $this->link->query($this->TRUNCATEquery($table));
		$this->setLastStatement($stmt);
		$this->table = $table;

		if ($this->debugOutput) {
			$this->debug('exec_TRUNCATEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_TRUNCATEquery_postProcessAction($table, $this);
		}

		return $stmt;
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
			$this->connectDB();
		}

		$this->affectedRows = $this->link->executeUpdate($this->createTruncateQuery($table));
		$this->table = $table;

		if ($this->debugOutput) {
			$this->debug('executeTruncateQuery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_TRUNCATEquery_postProcessAction($table, $this);
		}

		return $this->affectedRows;
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table Database tablename
	 * @param string $where WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function exec_DELETEquery($table, $where) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$stmt = $this->link->query($this->DELETEquery($table, $where));
		$this->setLastStatement($stmt);

		if ($this->debugOutput) {
			$this->debug('exec_DELETEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_DELETEquery_postProcessAction($table, $where, $this);
		}

		return $stmt;
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
			$this->connectDB();
		}

		$this->affectedRows = $this->link->delete($table, $where, $types);

		if ($this->debugOutput) {
			$this->debug('executeDeleteQuery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_DELETEquery_postProcessAction($table, $where, $this);
		}

		return $this->affectedRows;
	}

	/**************************************
	 *
	 * Query building
	 *
	 **************************************/
	/**
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fieldsValues.
	 *
	 * @param string  $table         See exec_INSERTquery()
	 * @param array   $fieldsValues  See exec_INSERTquery()
	 * @param boolean $noQuoteFields See fullQuoteArray()
	 *
	 * @return string Full SQL query for INSERT (unless $fieldsValues does not contain any elements in which case it will be FALSE)
	 */
	public function INSERTquery($table, $fieldsValues, $noQuoteFields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (is_array($fieldsValues) && count($fieldsValues)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				$hookObject->INSERTquery_preProcessAction($table, $fieldsValues, $noQuoteFields, $this);
			}
			// Quote and escape values
			$fieldsValues = $this->fullQuoteArray($fieldsValues, $table, $noQuoteFields);
			// Build query
			$query = 'INSERT INTO ' . $table . ' (' . implode(',', array_keys($fieldsValues)) . ') VALUES ' . '(' . implode(',', $fieldsValues) . ')';

			// Return query
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}

			return $query;
		}
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string  $table         Table name
	 * @param array   $fields        Field names
	 * @param array   $rows          Table rows. Each row should be an array with field values mapping to $fields
	 * @param boolean $noQuoteFields See fullQuoteArray()
	 *
	 * @return string Full SQL query for INSERT (unless $rows does not contain any elements in which case it will be FALSE)
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $noQuoteFields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (count($rows)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->INSERTmultipleRows_preProcessAction($table, $fields, $rows, $noQuoteFields, $this);
			}
			// Build query
			$query = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES ';
			$rowSQL = array();
			foreach ($rows as $row) {
				// Quote and escape values
				$row = $this->fullQuoteArray($row, $table, $noQuoteFields);
				$rowSQL[] = '(' . implode(', ', $row) . ')';
			}
			$query .= implode(', ', $rowSQL);
			// Return query
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}

			return $query;
		}
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
	 * @return string Full SQL query for SELECT
	 */
	public function SELECTquery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '') {
		foreach ($this->preProcessHookObjects as $hookObject) {
			/** @var $hookObject PreProcessQueryHookInterface */
			$hookObject->SELECTquery_preProcessAction($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit, $this);
		}
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query
		$query = 'SELECT ' . $selectFields . ' FROM ' . $fromTable . (strlen($whereClause) > 0 ? ' WHERE ' . $whereClause : '');
		// Group by
		$query .= strlen($groupBy) > 0 ? ' GROUP BY ' . $groupBy : '';
		// Order by
		$query .= strlen($orderBy) > 0 ? ' ORDER BY ' . $orderBy : '';
		// Group by
		$query .= strlen($limit) > 0 ? ' LIMIT ' . $limit : '';
		// Return query
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}

	/**
	 * Creates a SELECT SQL-statement to be used as subquery within another query.
	 * BEWARE: This method should not be overriden within DBAL to prevent quoting from happening.
	 *
	 * @param string $selectFields List of fields to select from the table.
	 * @param string $fromTable    Table from which to select.
	 * @param string $whereClause  Conditional WHERE statement
	 *
	 * @return string Full SQL query for SELECT
	 */
	public function SELECTsubquery($selectFields, $fromTable, $whereClause) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'SELECT ' . $selectFields . ' FROM ' . $fromTable . (strlen($whereClause) > 0 ? ' WHERE ' . $whereClause : '');
		// Return query
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fieldsValues.
	 *
	 *
	 * @param string  $table         See exec_UPDATEquery()
	 * @param string  $where         See exec_UPDATEquery()
	 * @param array   $fieldsValues  See exec_UPDATEquery()
	 * @param boolean $noQuoteFields See fullQuoteArray()
	 *
	 * @return string Full SQL query for UPDATE*
	 * @throws \InvalidArgumentException
	 */
	public function UPDATEquery($table, $where, $fieldsValues, $noQuoteFields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->UPDATEquery_preProcessAction($table, $where, $fieldsValues, $noQuoteFields, $this);
			}
			$fields = array();
			if (is_array($fieldsValues) && count($fieldsValues)) {
				// Quote and escape values
				$nArr = $this->fullQuoteArray($fieldsValues, $table, $noQuoteFields, TRUE);
				foreach ($nArr as $k => $v) {
					$fields[] = $k . '=' . $v;
				}
			}
			// Build query
			$query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) . (strlen($where) > 0 ? ' WHERE ' . $where : '');
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !', 1270853880);
		}
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param string $table See exec_TRUNCATEquery()
	 *
	 * @return string Full SQL query for TRUNCATE TABLE
	 */
	public function TRUNCATEquery($table) {
		foreach ($this->preProcessHookObjects as $hookObject) {
			/** @var $hookObject PreProcessQueryHookInterface */
			$hookObject->TRUNCATEquery_preProcessAction($table, $this);
		}
		// Table should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'TRUNCATE TABLE ' . $table;
		// Return query:
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement via the Doctrine2 API
	 *
	 * @param string $table See exec_TRUNCATEquery()
	 *
	 * @return string Full SQL query for TRUNCATE TABLE
	 */
	public function createTruncateQuery($table) {
		foreach ($this->preProcessHookObjects as $hookObject) {
			/** @var $hookObject PreProcessQueryHookInterface */
			$hookObject->TRUNCATEquery_preProcessAction($table, $this);
		}

		$dbPlatform = $this->link->getDatabasePlatform();
		$query = $dbPlatform->getTruncateTableSQL($table);

		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table See exec_DELETEquery()
	 * @param string $where See exec_DELETEquery()
	 *
	 * @return string Full SQL query for DELETE
	 * @throws \InvalidArgumentException
	 */
	public function DELETEquery($table, $where) {
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->DELETEquery_preProcessAction($table, $where, $this);
			}
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM ' . $table . (strlen($where) > 0 ? ' WHERE ' . $where : '');
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !', 1270853881);
		}
	}

	public function createDeleteQuery($table, array $where, array $types = array()) {
		$criteria = array();

		foreach (array_keys($where) as $columnName) {
			//$criteria
		}

//		$query = $this->queryBuilder
//					->delete($table, $table)
//					->where($where)
//					->setParameters($parameters);
//		$sql = $query->getSQL();
//		return $sql;
	}

	/**
	 * Returns a WHERE clause that can find a value ($value) in a list field ($field)
	 * For instance a record in the database might contain a list of numbers,
	 * "34,234,5" (with no spaces between). This query would be able to select that
	 * record based on the value "34", "234" or "5" regardless of their position in
	 * the list (left, middle or right).
	 * The value must not contain a comma (,)
	 * Is nice to look up list-relations to records or files in TYPO3 database tables.
	 *
	 * @param string $field Field name
	 * @param string $value Value to find in list
	 * @param string $table Table in which we are searching (for DBAL detection of quoteStr() method)
	 *
	 * @return string WHERE clause for a query
	 * @throws \InvalidArgumentException
	 */
	public function listQuery($field, $value, $table) {
		$value = (string) $value;
		if (strpos(',', $value) !== FALSE) {
			throw new \InvalidArgumentException('$value must not contain a comma (,) in $this->listQuery() !', 1294585862);
		}
		$pattern = $this->quoteStr($value, $table);
		$where = 'FIND_IN_SET(\'' . $pattern . '\',' . $field . ')';

		return $where;
	}

	/**
	 * Returns a WHERE clause which will make an AND or OR search for the words in the $searchWords array in any of the fields in array $fields.
	 *
	 * @param array  $searchWords Array of search words
	 * @param array  $fields      Array of fields
	 * @param string $table       Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @param string $constraint  How multiple search words have to match ('AND' or 'OR')
	 *
	 * @return string WHERE clause for search
	 */
	public function searchQuery($searchWords, $fields, $table, $constraint = self::AND_Constraint) {
		switch ($constraint) {
			case self::OR_Constraint:
				$constraint = 'OR';
				break;
			default:
				$constraint = 'AND';
		}

		$queryParts = array();
		foreach ($searchWords as $sw) {
			$like = ' LIKE \'%' . $this->quoteStr($sw, $table) . '%\'';
			$queryParts[] = $table . '.' . implode(($like . ' OR ' . $table . '.'), $fields) . $like;
		}
		$query = '(' . implode(') ' . $constraint . ' (', $queryParts) . ')';

		return $query;
	}

	/**************************************
	 *
	 * Prepared Query Support
	 *
	 **************************************/
	public function preparedSelectQuery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = ''){
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$sql = $this->SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		$stmt = $this->link->prepare($sql);

		return $stmt;
	}

	/**
	 * Executes a prepared query.
	 * This method may only be called by \TYPO3\CMS\Core\Database\PreparedStatement
	 *
	 * @param string $query           The query to execute
	 * @param array  $queryComponents The components of the query to execute
	 *
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_PREPAREDquery($query, array $queryComponents) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($query);
		if ($this->debugOutput) {
			$this->debug('stmt_execute', $query);
		}

		return $res;
	}

	/**
	 * Creates a SELECT prepared SQL statement.
	 *
	 * @param string $selectFields    See exec_SELECTquery()
	 * @param string $fromTable       See exec_SELECTquery()
	 * @param string $whereClause     See exec_SELECTquery()
	 * @param string $groupBy         See exec_SELECTquery()
	 * @param string $orderBy         See exec_SELECTquery()
	 * @param string $limit           See exec_SELECTquery()
	 * @param array  $inputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE.
	 *
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement Prepared statement
	 */
	public function prepare_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '', array $inputParameters = array()) {
		$query = $this->SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		/** @var $preparedStatement \TYPO3\CMS\Core\Database\PreparedStatement */
		$preparedStatement = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\PreparedStatement', $query, $fromTable, array());
		// Bind values to parameters
		foreach ($inputParameters as $key => $value) {
			$preparedStatement->bindValue($key, $value, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE);
		}

		// Return prepared statement
		return $preparedStatement;
	}

	/**
	 * Creates a SELECT prepared SQL statement based on input query parts array
	 *
	 * @param array $queryParts      Query parts array
	 * @param array $inputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE.
	 *
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement Prepared statement
	 */
	public function prepare_SELECTqueryArray(array $queryParts, array $inputParameters = array()) {
		return $this->prepare_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT'], $inputParameters);
	}

	/**************************************
	 *
	 * Doctrine / PDO wrapper functions
	 * (For use in your applications)
	 *
	 **************************************/

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 *
	 * @return integer Number of rows affected by last query
	 */
	public function sql_affected_rows() {
		$result = $this->affectedRows;
		return $this->lastStatement->rowCount();
	}

	/**
	 * Move internal result pointer
	 *
	 * @param boolean|\mysqli_result|object $res  MySQLi result object / DBAL object
	 * @param integer                       $seek Seek result number.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_data_seek($res, $seek) {
		if ($this->debug_check_recordset($res)) {
			return $res->data_seek($seek);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the error number on the last query() execution
	 *
	 * @return integer PDO error number
	 */
	public function sql_errno() {
		return $this->sqlErrorCode();
	}

	/**
	 * Returns the error number on the last query() execution
	 *
	 * @return integer PDO error number
	 */
	public function sqlErrorCode() {
		return $this->link->errorCode();
	}

	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string PDO error string.
	 */
	public function sql_error() {
		return $this->sqlErrorMessage();
	}

	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string PDO error string.
	 */
	public function sqlErrorMessage() {
		$errorMsg = $this->link->errorInfo();

		return $errorMsg[0] === '00000' ? '' : $errorMsg;
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * Wrapper function for Doctrine/PDO fetch(\PDO::FETCH_ASSOC)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 *
	 * @return boolean|array Associative array of result row.
	 */
	public function sql_fetch_assoc($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->fetch(\PDO::FETCH_ASSOC);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * Wrapper function for Doctrine/PDO fetch(\PDO::FETCH_NUM)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 *
	 * @return boolean|array Array with result rows.
	 */
	public function sql_fetch_row($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			return $stmt->fetch(\PDO::FETCH_NUM);
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the type of the specified field in a result
	 * mysql_field_type() wrapper function
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt    A PDOStatement object
	 * @param integer                          $pointer Field index.
	 *
	 * @return string Returns the name of the specified field index, or FALSE on error
	 */
	public function sql_field_type($stmt, $pointer) {
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
			$columns = $this->schema->listTableColumns($this->table);

			$i = 0;
			foreach ($columns as $column) {
				if ($i === $pointer) {
					// TODO: Figure out if this is ok like it is and clean up the rest of this mess
					//$pdoTypeId = $column->getType()->getBindingType();
					//$typeArray = $column->toArray();
					$metaInfo = $column->getType()->getName();
				}
				$i++;
			}
			if ($metaInfo === FALSE) {
				return FALSE;
			}

			return $mysqlDataTypeHash[$metaInfo];
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
	 */
	public function sql_free_result($stmt) {
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
	 */
	// TODO Write a test to prove that this method returns an integer
	public function sql_insert_id() {
		return (integer) $this->link->lastInsertId();
	}

	/**
	 * Returns the number of selected rows.
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt
	 *
	 * @return integer Number of resulting rows
	 */
	public function sql_num_rows($stmt) {
		if ($this->debug_check_recordset($stmt)) {
			$result = $stmt->rowCount();
		} else {
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * Select a SQL database
	 *
	 * @param string $TYPO3_db Deprecated since 6.1, will be removed in two versions. Database to connect to.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_select_db($TYPO3_db = NULL) {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		if ($TYPO3_db) {
			GeneralUtility::deprecationLog(
				'DatabaseConnection->sql_select_db() should be called without arguments.' .
					' Use the setDatabaseName() before. Will be removed two versions after 6.1.'
			);
			$this->setDatabaseName($TYPO3_db);
		}

		$isConnected = $this->isConnected();
		if (!$isConnected) {
			GeneralUtility::sysLog(
				// TODO: Replace the term "MySQL" in the next log message with the current platform name
				'Could not select MySQL database ' . $this->getDatabaseName() . ': ' . $this->sqlErrorMessage(),
				'Core',
				GeneralUtility::SYSLOG_SEVERITY_FATAL
			);
		}

		return $isConnected;
	}

	/**
	 * Executes query
	 * Doctrine/PDO query() wrapper function
	 * Beware: Use of this method should be avoided. You should consider
	 * using exec_SELECTquery() and similar methods instead.
	 *
	 * @param string $query Query to execute
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 */
	public function sql_query($query) {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$stmt = $this->link->query($query);
		$this->setLastStatement($stmt);

		if ($this->debugOutput) {
			$this->debug('sql_query', $query);
		}

		return $stmt;
	}

	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * This returns the count of the tables from the selected database
	 *
	 * @return array
	 */
	public function adminCountTables() {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$result[0] = -1;
		$sql = 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :databaseName';

		$statement = $this->link->prepare($sql);
		$statement->bindValue('databaseName', $this->getDatabaseName());
		$isQuerySuccess = $statement->execute();

		if ($isQuerySuccess !== FALSE) {
			$result = $statement->fetchAll(\PDO::FETCH_COLUMN);
		}

		return $result[0];
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @return array Array with Charset as key and an array of "Charset", "Description", "Default collation", "Maxlen" as values
	 * @see adminGetCharsets()
	 */
	public function admin_get_charsets() {
		return $this->adminGetCharset();
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
	public function adminGetCharset() {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$output = array();
		$stmt = $this->adminQuery('SHOW CHARACTER SET');

		if ($stmt !== FALSE) {
			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$output[$row['Charset']] = $row;
			}
			$stmt->closeCursor();
		}

		return $output;
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @return array Each entry represents a database name
	 * @throws \RuntimeException
	 * @see adminGetDatabases
	 */
	public function admin_get_dbs() {
		return $this->adminGetDatabases();
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
	public function adminGetDatabases() {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$databases = $this->schema->listDatabases();
		if (empty($databases)) {
			throw new \RuntimeException(
				'MySQL Error: Cannot get databases: "' . $this->sqlErrorMessage() . '"!',
				1378457171
			);
		}

		return $databases;
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Field information in an associative array with fieldname => field row
	 * @see adminGetFields()
	 */
	public function admin_get_fields($tableName) {
		return $this->adminGetFields($tableName);
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
	public function adminGetFields($tableName) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$output = array();
		// TODO: Figure out if we could use the function $this->schema->listTableColumns($tableName);
		//       The result is a different from the current. We need to adjust assembleFieldDefinition() from
		//       SqlSchemaMigrationService
		$stmt = $this->adminQuery('SHOW COLUMNS FROM `' . $tableName . '`');

		if ($stmt !== FALSE) {
			while ($fieldRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$output[$fieldRow['Field']] = $fieldRow;
			}
			$stmt->closeCursor();
		}

		return $output;
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Key information in a associative array
	 * @see adminGetKeys()
	 */
	public function admin_get_keys($tableName) {
		return $this->adminGetKeys($tableName);
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Key information in a associative array
	 */
	public function adminGetKeys($tableName) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$output = array();

		$stmt = $this->adminQuery('SHOW KEYS FROM `' . $tableName . '`');
		if ($stmt !== FALSE) {
			while ($keyRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$output[] = $keyRow;
			}
			$stmt->closeCursor();
		}

		return $output;
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @return array Array with table names as key and arrays with status information as value
	 * @see adminGetTables()
	 */
	public function admin_get_tables() {
		return $this->adminGetTables();
	}

	/**
	 * Returns the list of tables from the default database, TYPO3_db (quering the DBMS)
	 * In a DBAL this method should 1) look up all tables from the DBMS  of
	 * the _DEFAULT handler and then 2) add all tables *configured* to be managed by other handlers
	 *
	 * @return array Array with table names as key and arrays with status information as value
	 */
	public function adminGetTables() {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$whichTables = array();
		$tablesResult = $this->adminQuery('SHOW TABLE STATUS FROM `' . $this->getDatabaseName() . '`');
		if ($tablesResult !== FALSE) {
			while ($theTable = $tablesResult->fetch(\PDO::FETCH_ASSOC)) {
				$whichTables[$theTable['Name']] = $theTable;
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

		return $whichTables;
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @param string $query Query to execute
	 *
	 * @return boolean|\Doctrine\DBAL\Driver\Statement A PDOStatement object
	 * @see adminQuery()
	 */
	public function admin_query($query) {
		return $this->adminQuery($query);
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
			$this->connectDB();
		}
		$stmt = $this->link->query($query);
		$this->setLastStatement($stmt);
		if ($this->debugOutput) {
			$this->debug('adminQuery', $query);
		}

		return $stmt;
	}

	/**************************************
	 *
	 * Various helper functions
	 *
	 * Functions recommended to be used for
	 * - escaping values,
	 * - cleaning lists of values,
	 * - stripping of excess ORDER BY/GROUP BY keywords
	 *
	 **************************************/
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
	 * Escaping and quoting values for SQL statements.
	 *
	 * @param string  $str       Input string
	 * @param string  $table     Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @param boolean $allowNull Whether to allow NULL values
	 *
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see  quoteStr()
	 * @todo The $table parameter seems unused
	 * @todo This method used "return '\'' . $this->link->real_escape_string($str) . '\'';"
	 *       for escaping and quoting.
	 */
	public function fullQuoteStr($str, $table, $allowNull = FALSE) {
		if ($allowNull && $str === NULL) {
			return 'NULL';
		}

		return '\'' . $this->quoteStr($str, $table) . '\'';
	}

	/**
	 * Will fullquote all values in the one-dimensional array so they are ready to "implode" for an sql query.
	 *
	 * @param array         $arr       Array with values (either associative or non-associative array)
	 * @param string        $table     Table name for which to quote
	 * @param boolean|array $noQuote   List/array of keys NOT to quote (eg. SQL functions) - ONLY for associative arrays
	 * @param boolean       $allowNull Whether to allow NULL values
	 *
	 * @return array The input array with the values quoted
	 * @see cleanIntArray()
	 */
	public function fullQuoteArray($arr, $table, $noQuote = FALSE, $allowNull = FALSE) {
		if (is_string($noQuote)) {
			$noQuote = explode(',', $noQuote);
		} elseif (!is_array($noQuote)) {
			$noQuote = FALSE;
		}
		foreach ($arr as $k => $v) {
			if ($noQuote === FALSE || !in_array($k, $noQuote)) {
				$arr[$k] = $this->fullQuoteStr($v, $table, $allowNull);
			}
		}

		return $arr;
	}

	/**
	 * Substitution for PHP function "addslashes()"
	 * Use this function instead of the PHP addslashes() function when you build queries - this will prepare your code for DBAL.
	 * NOTICE: You must wrap the output of this function in SINGLE QUOTES to be DBAL compatible. Unless you have to apply the single quotes yourself you should rather use ->fullQuoteStr()!
	 *
	 * @param string $str   Input string
	 * @param string $table Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 *
	 * @return string Output string; Quotes (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 */
	public function quoteStr($str, $table) {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		$quotedResult = $this->link->quote($str);

		if ($quotedResult[0] == '\'') {
			$quotedResult = substr($quotedResult, 1);
		}
		if ($quotedResult[strlen($quotedResult) - 1] == '\'') {
			$quotedResult = substr($quotedResult, 0, strlen($quotedResult) - 1);
		}

		return $quotedResult;
	}

	/**
	 * Escaping values for SQL LIKE statements.
	 *
	 * @param string $str   Input string
	 * @param string $table Table name for which to escape string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 *
	 * @return string Output string; % and _ will be escaped with \ (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function escapeStrForLike($str, $table) {
		return addcslashes($str, '_%');
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
			$arr[$k] = intval($arr[$k]);
		}

		return $arr;
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
	 * Removes the prefix "ORDER BY" from the input string.
	 * This function is used when you call the exec_SELECTquery() function and want to pass the ORDER BY parameter by can't guarantee that "ORDER BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "ORDER BY title, uid
	 *
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripGroupBy()
	 */
	public function stripOrderBy($str) {
		return preg_replace('/^(?:ORDER[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
	}

	/**
	 * Removes the prefix "GROUP BY" from the input string.
	 * This function is used when you call the SELECTquery() function and want to pass the GROUP BY parameter by can't guarantee that "GROUP BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "GROUP BY title, uid
	 *
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripOrderBy()
	 */
	public function stripGroupBy($str) {
		return preg_replace('/^(?:GROUP[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
	}

	/**
	 * Takes the last part of a query, eg. "... uid=123 GROUP BY title ORDER BY title LIMIT 5,2" and splits each part into a table (WHERE, GROUPBY, ORDERBY, LIMIT)
	 * Work-around function for use where you know some userdefined end to an SQL clause is supplied and you need to separate these factors.
	 *
	 * @param string $str Input string
	 *
	 * @return array
	 */
	public function splitGroupOrderLimit($str) {
		// Prepending a space to make sure "[[:space:]]+" will find a space there
		// for the first element.
		$str = ' ' . $str;
		// Init output array:
		$wgolParts = array(
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);
		// Find LIMIT
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+LIMIT[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['LIMIT'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Find ORDER BY
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+ORDER[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['ORDERBY'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Find GROUP BY
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+GROUP[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['GROUPBY'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Rest is assumed to be "WHERE" clause
		$wgolParts['WHERE'] = $str;

		return $wgolParts;
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

	/**
	 * Handle deprecated arguments for sql_pconnect() and connectDB()
	 *
	 * @param string|null $host     Database host[:port]
	 * @param string|null $username Database user name
	 * @param string|null $password User password
	 * @param string|null $db       Database
	 *
	 * @return void
	 */
	protected function handleDeprecatedConnectArguments($host = NULL, $username = NULL, $password = NULL, $db = NULL) {
		GeneralUtility::deprecationLog(
			'DatabaseConnection->sql_pconnect() and DatabaseConnection->connectDB() should be ' .
			'called without arguments. Use the setters instead.'
		);
		if ($host) {
			if (strpos($host, ':') > 0) {
				list($databaseHost, $databasePort) = explode(':', $host);
				$this->setDatabaseHost($databaseHost);
				$this->setDatabasePort($databasePort);
			} else {
				$this->setDatabaseHost($host);
			}
		}
		if ($username) {
			$this->setDatabaseUsername($username);
		}
		if ($password) {
			$this->setDatabasePassword($password);
		}
		if ($db) {
			$this->setDatabaseName($db);
		}
	}

	/******************************
	 *
	 * Debugging
	 *
	 ******************************/
	/**
	 * Debug function: Outputs error if any
	 *
	 * @param string $func  Function calling debug()
	 * @param string $query Last query if not last built query
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function debug($func, $query = '') {
		$error = $this->sqlErrorMessage();
		if ($error || (int) $this->debugOutput === 2) {
			DebugUtility::debug(
				array(
					'caller' => 'TYPO3\\CMS\\Core\\Database\\DatabaseConnection::' . $func,
					'ERROR' => $error,
					'lastBuiltQuery' => $query ? $query : $this->debug_lastBuiltQuery,
					'debug_backtrace' => DebugUtility::debugTrail()
				),
				$func,
				is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'], 'debug'))
					? ''
					: 'DB Error'
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
		$msg .= ': function TYPO3\\CMS\\Core\\Database\\DatabaseConnection->' . $trace[0]['function'] . ' called from file ' . substr($trace[0]['file'], (strlen(PATH_site) + 2)) . ' in line ' . $trace[0]['line'];
		GeneralUtility::sysLog(
			$msg . '. Use a devLog extension to get more details.',
			'Core/t3lib_db',
			GeneralUtility::SYSLOG_SEVERITY_ERROR
		);
		// Send to devLog if enabled
		if (TYPO3_DLOG) {
			$debugLogData = array(
				'SQL Error' => $this->sqlErrorMessage(),
				'Backtrace' => $trace
			);
			if ($this->debug_lastBuiltQuery) {
				$debugLogData = array('SQL Query' => $this->debug_lastBuiltQuery) + $debugLogData;
			}
			GeneralUtility::devLog($msg . '.', 'Core/t3lib_db', 3, $debugLogData);
		}

		return FALSE;
	}

	/**
	 * Explain select queries
	 * If $this->explainOutput is set, SELECT queries will be explained here. Only queries with more than one possible result row will be displayed.
	 * The output is either printed as raw HTML output or embedded into the TS admin panel (checkbox must be enabled!)
	 *
	 * TODO: Feature is not DBAL-compliant
	 *
	 * @param string  $query     SQL query
	 * @param string  $fromTable Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param integer $rowCount  Number of resulting rows
	 *
	 * @return boolean TRUE if explain was run, FALSE otherwise
	 */
	protected function explain($query, $fromTable, $rowCount) {
		$debugAllowedForIp = GeneralUtility::cmpIP(
			GeneralUtility::getIndpEnv('REMOTE_ADDR'),
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
		);
		if (
			(int)$this->explainOutput == 1
			|| ((int)$this->explainOutput == 2 && $debugAllowedForIp)
		) {
			// Raw HTML output
			$explainMode = 1;
		} elseif ((int) $this->explainOutput == 3 && is_object($GLOBALS['TT'])) {
			// Embed the output into the TS admin panel
			$explainMode = 2;
		} else {
			return FALSE;
		}
		$error = $this->sqlErrorMessage();
		$trail = DebugUtility::debugTrail();
		$explainTables = array();
		$explainOutput = array();
		$res = $this->sql_query('EXPLAIN ' . $query, $this->link);
		if (is_a($res, '\\mysqli_result')) {
			while ($tempRow = $this->sql_fetch_assoc($res)) {
				$explainOutput[] = $tempRow;
				$explainTables[] = $tempRow['table'];
			}
			$this->sql_free_result($res);
		}
		$indicesOutput = array();
		// Notice: Rows are skipped if there is only one result, or if no conditions are set
		if (
			$explainOutput[0]['rows'] > 1
			|| GeneralUtility::inList('ALL', $explainOutput[0]['type'])
		) {
			// Only enable output if it's really useful
			$debug = TRUE;
			foreach ($explainTables as $table) {
				$tableRes = $this->sql_query('SHOW TABLE STATUS LIKE \'' . $table . '\'');
				$isTable = $this->sql_num_rows($tableRes);
				if ($isTable) {
					$res = $this->sql_query('SHOW INDEX FROM ' . $table, $this->link);
					if (is_a($res, '\\mysqli_result')) {
						while ($tempRow = $this->sql_fetch_assoc($res)) {
							$indicesOutput[] = $tempRow;
						}
						$this->sql_free_result($res);
					}
				}
				$this->sql_free_result($tableRes);
			}
		} else {
			$debug = FALSE;
		}
		if ($debug) {
			if ($explainMode) {
				$data = array();
				$data['query'] = $query;
				$data['trail'] = $trail;
				$data['row_count'] = $rowCount;
				if ($error) {
					$data['error'] = $error;
				}
				if (count($explainOutput)) {
					$data['explain'] = $explainOutput;
				}
				if (count($indicesOutput)) {
					$data['indices'] = $indicesOutput;
				}
				if ($explainMode == 1) {
					DebugUtility::debug($data, 'Tables: ' . $fromTable, 'DB SQL EXPLAIN');
				} elseif ($explainMode == 2) {
					$GLOBALS['TT']->setTSselectQuery($data);
				}
			}
			return TRUE;
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
			'explainOutput',
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
			'link'
		);
	}
}