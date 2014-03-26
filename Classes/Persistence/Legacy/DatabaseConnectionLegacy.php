<?php
namespace TYPO3\DoctrineDbal\Persistence\Legacy;

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

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class DatabaseConnectionLegacy extends \TYPO3\DoctrineDbal\Persistence\Doctrine\DatabaseConnection  {

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
	 * Initialize the database connection
	 *
	 * @return void
	 */
	public function initialize() {
		// Intentionally blank as this will be overloaded by DBAL
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
	 * @deprecated
	 */
	public function connectDB($host = NULL, $username = NULL, $password = NULL, $db = NULL) {
		if ($host || $username || $password || $db) {
			$this->handleDeprecatedConnectArguments($host, $username, $password, $db);
		}

		$this->connectDatabase($host);
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
	 * @deprecated
	 */
	public function sql_pconnect($host = NULL, $username = NULL, $password = NULL) {
		if ($host || $username || $password) {
			$this->handleDeprecatedConnectArguments($host, $username, $password);
		}

		if ($this->isConnected) {
			return $this->link;
		} else {
			$this->connectDatabase();

			return $this->link;
		}
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
	 * @deprecated
	 */
	public function exec_INSERTquery($table, array $fieldsValues, $noQuoteFields = FALSE) {
		$stmt = $this->query($this->INSERTquery($table, $fieldsValues, $noQuoteFields));

		if ($this->getDebugMode()) {
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
	 * @deprecated
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $noQuoteFields = FALSE) {
		$stmt = $this->query($this->INSERTmultipleRows($table, $fields, $rows, $noQuoteFields));
		if ($this->getDebugMode()) {
			$this->debug('exec_INSERTmultipleRows');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_INSERTmultipleRows_postProcessAction($table, $fields, $rows, $noQuoteFields, $this);
		}

		return $stmt;
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
	 * @deprecated
	 */
	public function exec_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '') {
		if ($this->isConnected) {
			$this->connectDb();
		}
		$query = $this->selectQueryDoctrine($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		$stmt = $query->execute();

		if ($this->getDebugMode()) {
			$this->debug('exec_SELECTquery');
		}
		if ($this->explainOutput) {
			// TODO: Look why num_rows not exist
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
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 * @see exec_SELECTquery()
	 * @deprecated
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
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 * @see exec_SELECTquery()
	 * @deprecated
	 */
	public function exec_SELECT_queryArray(array $queryParts) {
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
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 * @deprecated
	 */
	public function exec_SELECTgetRows($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
		$stmt = $this->exec_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);

		if ($this->getDebugMode()) {
			$this->debug('exec_SELECTquery');
		}
		if (!$this->sqlErrorMessage()) {
			$output = array();
			if ($uidIndexField) {
				while ($tempRow = $this->fetchAssoc($stmt)) {
					$output[$tempRow[$uidIndexField]] = $tempRow;
				}
			} else {
				while ($output[] = $this->fetchAssoc($stmt)) {

				}
				array_pop($output);
			}
			$this->freeResult($stmt);
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
	 * @deprecated
	 */
	public function exec_SELECTgetSingleRow($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $numIndex = FALSE) {
		$stmt = $this->exec_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, '1');

		if ($this->getDebugMode()) {
			$this->debug('exec_SELECTquery');
		}
		$output = NULL;
		if ($stmt !== FALSE) {
			if ($numIndex) {
				$output = $this->fetchAssoc($stmt);
			} else {
				$output = $this->fetchAssoc($stmt);
			}
			$this->freeResult($stmt);
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
	 * @deprecated
	 */
	public function exec_SELECTcountRows($field, $table, $where = '') {
		$count = FALSE;
		$resultSet = $this->exec_SELECTquery('COUNT(' . $field . ')', $table, $where);
		if ($resultSet !== FALSE) {
			list($count) = $this->fetchRow($resultSet);
			$count = intval($count);
			$this->freeResult($resultSet);
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
	 * @deprecated
	 */
	public function exec_UPDATEquery($table, $where, array $fieldsValues, $noQuoteFields = FALSE) {
		$stmt = $this->query($this->UPDATEquery($table, $where, $fieldsValues, $noQuoteFields));

		if ($this->getDebugMode()) {
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
	 * @deprecated
	 */
	public function exec_TRUNCATEquery($table) {
		$stmt = $this->query($this->TRUNCATEquery($table));

		if ($this->getDebugMode()) {
			$this->debug('exec_TRUNCATEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_TRUNCATEquery_postProcessAction($table, $this);
		}

		return $stmt;
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table Database tablename
	 * @param string $where WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 *
	 * @return \Doctrine\DBAL\Driver\Statement A PDOStatement object
	 * @deprecated
	 */
	public function exec_DELETEquery($table, $where) {
		$stmt = $this->query($this->DELETEquery($table, $where));

		if ($this->getDebugMode()) {
			$this->debug('exec_DELETEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_DELETEquery_postProcessAction($table, $where, $this);
		}

		return $stmt;
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
	 * @deprecated
	 */
	public function INSERTquery($table, array $fieldsValues, $noQuoteFields = FALSE) {
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
			if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
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
	 * @deprecated
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
			if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
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
	 * @deprecated
	 */
	public function SELECTquery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '') {
		foreach ($this->preProcessHookObjects as $hookObject) {
			/** @var $hookObject PreProcessQueryHookInterface */
			$hookObject->SELECTquery_preProcessAction($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit, $this);
		}

		$query = $this->selectQueryDoctrine($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		$sql = $query->getSql();

		// Return query
		if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
			$this->debug_lastBuiltQuery = $sql;
		}

		return $sql;
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
	 * @deprecated
	 */
	public function SELECTsubquery($selectFields, $fromTable, $whereClause) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'SELECT ' . $selectFields . ' FROM ' . $fromTable . (strlen($whereClause) > 0 ? ' WHERE ' . $whereClause : '');
		// Return query
		if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
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
	 * @deprecated
	 */
	public function UPDATEquery($table, $where, array $fieldsValues, $noQuoteFields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->UPDATEquery_preProcessAction($table, $where, $fieldsValues, $noQuoteFields, $this);
			}
			$fields = array();
			if (count($fieldsValues)) {
				// Quote and escape values
				$nArr = $this->fullQuoteArray($fieldsValues, $table, $noQuoteFields, TRUE);
				foreach ($nArr as $k => $v) {
					$fields[] = $k . '=' . $v;
				}
			}
			// Build query
			$query = 'UPDATE ' . $table . ' SET ' . join(',', $fields) . ((string)$where !== '' ? ' WHERE ' . $where : '');
			if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
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
	 * @deprecated
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
		if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
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
	 * @deprecated
	 */
	public function DELETEquery($table, $where) {
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->DELETEquery_preProcessAction($table, $where, $this);
			}
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM ' . $table . (strlen($where) > 0 ? ' WHERE ' . $where : '');
			if ($this->getDebugMode() || $this->getStoreLastBuildQuery()) {
				$this->debug_lastBuiltQuery = $query;
			}

			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !', 1270853881);
		}
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
	 * @deprecated
	 */
	public function listQuery($field, $value, $table) {
		$value = (string) $value;
		if (strpos($value, ',') !== FALSE) {
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
	public function searchQuery(array $searchWords, array $fields, $table, $constraint = self::AND_Constraint) {
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
	/**
	 * @param        $selectFields
	 * @param        $fromTable
	 * @param        $whereClause
	 * @param string $groupBy
	 * @param string $orderBy
	 * @param string $limit
	 *
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @deprecated
	 */
	public function preparedSelectQuery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = ''){
		if (!$this->isConnected) {
			$this->connectDatabase();
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
	 * @deprecated
	 */
	public function exec_PREPAREDquery($query, array $queryComponents) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}
		$res = $this->link->query($query);
		if ($this->getDebugMode()) {
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
	 * @deprecated
	 */
	public function prepare_SELECTquery($selectFields, $fromTable, $whereClause, $groupBy = '', $orderBy = '', $limit = '', array $inputParameters = array()) {
		$query = $this->SELECTquery($selectFields, $fromTable, $whereClause, $groupBy, $orderBy, $limit);
		/** @var $preparedStatement \TYPO3\CMS\Core\Database\PreparedStatement */
		$preparedStatement = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\PreparedStatement', $query, $fromTable, array());
		// Bind values to parameters
		foreach ($inputParameters as $key => $value) {
			$preparedStatement->bindValue($key, $value, \TYPO3\DoctrineDbal\Persistence\Legacy\PreparedStatementLegacy::PARAM_AUTOTYPE);
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
	 * @deprecated
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
	 * @deprecated
	 */
	public function sql_affected_rows() {
		return $this->getAffectedRows();
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
	 * @deprecated
	 */
	public function sql_errno() {
		return $this->sqlErrorCode();
	}


	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string PDO error string.
	 * @deprecated
	 */
	public function sql_error() {
		return $this->sqlErrorMessage();
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * Wrapper function for Doctrine/PDO fetch(\PDO::FETCH_ASSOC)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement $stmt A PDOStatement object
	 *
	 * @return boolean|array Associative array of result row.
	 * @deprecated
	 */
	public function sql_fetch_assoc($stmt) {
		return $this->fetchAssoc($stmt);
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
		return parent::fetchAssoc($stmt);
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * Wrapper function for Doctrine/PDO fetch(\PDO::FETCH_NUM)
	 *
	 * @param \Doctrine\DBAL\Driver\Statement $stmt A PDOStatement object
	 *
	 * @return boolean|array Array with result rows.
	 * @deprecated
	 */
	public function sql_fetch_row($stmt) {
		return $this->fetchRow($stmt);
	}

	/**
	 * Free result memory
	 * Wrapper function for Doctrine/PDO closeCursor()
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt A PDOStatement
	 *
	 * @return boolean Returns NULL on success or FALSE on failure.
	 * @deprecated
	 */
	public function sql_free_result($stmt) {
		return $this->freeResult($stmt);
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return integer The uid of the last inserted record.
	 * @deprecated
	 */
	public function sql_insert_id() {
		return $this->getLastInsertId();
	}

	/**
	 * Returns the number of selected rows.
	 *
	 * @param boolean|\Doctrine\DBAL\Driver\Statement $stmt
	 *
	 * @return integer Number of resulting rows
	 * @deprecated
	 */
	public function sql_num_rows($stmt) {
		return $this->getResultRowCount($stmt);
	}

	/**
	 * Select a SQL database
	 *
	 * @param string $TYPO3_db Deprecated since 6.1, will be removed in two versions. Database to connect to.
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 * @deprecated
	 */
	public function sql_select_db($TYPO3_db = NULL) {
		if ($TYPO3_db) {
			GeneralUtility::deprecationLog(
				'DatabaseConnection->sql_select_db() should be called without arguments.' .
					' Use the setDatabaseName() before. Will be removed two versions after 6.1.'
			);
			$this->setDatabaseName($TYPO3_db);
		}

		return $this->selectDb();
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
	 * @deprecated
	 */
	public function sql_query($query) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$stmt = $this->link->query($query);

		if ($this->getDebugMode()) {
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
	 * @return int
	 */
	public function adminCountTables() {
		return $this->countTables();
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @return array Array with Charset as key and an array of "Charset", "Description", "Default collation", "Maxlen" as values
	 * @see adminGetCharsets()
	 * @deprecated
	 */
	public function admin_get_charsets() {
		return $this->listDatabaseCharsets();
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @return array Each entry represents a database name
	 * @throws \RuntimeException
	 * @see adminGetDatabases
	 * @deprecated
	 */
	public function admin_get_dbs() {
		return $this->listDatabases();
	}

	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * This is only used as a service function in the (1-2-3 process) of the Install Tool.
	 * In any case a lookup should be done in the _DEFAULT handler DBMS then.
	 * Use in Install Tool only!
	 *
	 * @return array Each entry represents a database name
	 * @throws \RuntimeException
	 * @deprecated
	 */
	public function adminGetDatabases() {
		return $this->listDatabases();
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Field information in an associative array with fieldname => field row
	 * @see adminGetFields()
	 * @deprecated
	 */
	public function admin_get_fields($tableName) {
		return $this->listFields($tableName);
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
	 * @deprecated
	 */
	public function adminGetFields($tableName) {
		return $this->listFields($tableName);
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Key information in a associative array
	 * @see adminGetKeys()
	 * @deprecated
	 */
	public function admin_get_keys($tableName) {
		return $this->listKeys($tableName);
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param string $tableName Table name
	 *
	 * @return array Key information in a associative array
	 * @deprecated
	 */
	public function adminGetKeys($tableName) {
		return $this->listKeys($tableName);
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @return array Array with table names as key and arrays with status information as value
	 * @see adminGetTables()
	 * @deprecated
	 */
	public function admin_get_tables() {
		return $this->listTables();
	}

	/**
	 * This is the old version of the method. It make usage of the new one which follows the naming convention for method names
	 * Please use the new one instead of this one.
	 *
	 * @param string $query Query to execute
	 *
	 * @return boolean|\Doctrine\DBAL\Driver\Statement A PDOStatement object
	 * @see adminQuery()
	 * @deprecated
	 */
	public function admin_query($query) {
		return $this->adminQuery($query);
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
	 * @param string  $str       Input string
	 * @param string  $table     Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @param boolean $allowNull Whether to allow NULL values
	 *
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see  quoteStr()
	 * @todo The $table parameter seems unused
	 * @todo This method used "return '\'' . $this->link->real_escape_string($str) . '\'';"
	 *       for escaping and quoting.
	 * @deprecated
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
	 * @deprecated
	 */
	public function fullQuoteArray(array $arr, $table, $noQuote = FALSE, $allowNull = FALSE) {
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
	 * @deprecated
	 */
	public function quoteStr($str, $table) {
		if (!$this->isConnected) {
			$this->connectDatabase();
		}

		$quotedResult = $this->quote($str);

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
	 * Removes the prefix "ORDER BY" from the input string.
	 * This function is used when you call the exec_SELECTquery() function and want to pass the ORDER BY parameter by can't guarantee that "ORDER BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "ORDER BY title, uid
	 *
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripGroupBy()
	 * @deprecated
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
	 * @deprecated
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
	 * @deprecated
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
	 * Handle deprecated arguments for sql_pconnect() and connectDB()
	 *
	 * @param string|null $host     Database host[:port]
	 * @param string|null $username Database user name
	 * @param string|null $password User password
	 * @param string|null $db       Database
	 *
	 * @return void
	 * @deprecated
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
			$this->freeResult($res);
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
				$isTable = $this->getResultRowCount($tableRes);
				if ($isTable) {
					$res = $this->sql_query('SHOW INDEX FROM ' . $table, $this->link);
					if (is_a($res, '\\mysqli_result')) {
						while ($tempRow = $this->sql_fetch_assoc($res)) {
							$indicesOutput[] = $tempRow;
						}
						$this->freeResult($res);
					}
				}
				$this->freeResult($tableRes);
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
			'link',
			'schema',
			'schemaManager',
			'platform'
		);
	}
}
