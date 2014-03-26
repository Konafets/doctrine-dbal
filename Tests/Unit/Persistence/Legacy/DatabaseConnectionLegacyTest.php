<?php

namespace TYPO3\DoctrineDbal\Tests\Unit\Persistence\Legacy;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseConnectionTest
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class DatabaseConnectionLegacyTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Legacy\DatabaseConnectionLegacy
	 */
	private $subject = NULL;

	/**
	 * @var string
	 */
	protected $testTable;

	/**
	 * @var string
	 */
	protected $testTableMm;

	/**
	 * @var string
	 */
	protected $testTableForeign;

	/**
	 * @var string
	 */
	protected $testField;

	/**
	 * @var string
	 */
	protected $testFieldSecond;

	/**
	 * @var \Doctrine\DBAL\Schema\Schema
	 */
	protected $schema;

	/**
	 * @var \Doctrine\DBAL\Schema\Table
	 */
	protected $table;

	/**
	 * @var \Doctrine\DBAL\Schema\Table
	 */
	protected $mmTable;

	/**
	 * @var \Doctrine\DBAL\Schema\Table
	 */
	protected $foreignTable;

	/**
	 * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	protected $schemaManager;

	/**
	 * @var array The connection settings for Doctrine
	 */
	protected $connectionParams = array(
		'dbname'   => '',
		'user'     => '',
		'password' => '',
		'host'     => 'localhost',
		'driver'   => 'pdo_mysql',
		'port'     => 3306,
		'charset'  => 'utf8',
	);

	/**
	 * Set the test up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = GeneralUtility::makeInstance('TYPO3\\DoctrineDbal\\Persistence\\Legacy\\DatabaseConnectionLegacy');
		$this->subject->setDatabaseName(TYPO3_db);
		$this->subject->setDatabaseUsername(TYPO3_db_username);
		$this->subject->setDatabasePassword(TYPO3_db_password);
		$this->subject->setDatabasePort($GLOBALS['TYPO3_DB']->getDatabasePort());
		$this->subject->setDatabaseHost($GLOBALS['TYPO3_DB']->getDatabaseHost());
		$this->subject->connectDB();

		$this->testTable        = 'test_t3lib_dbtest';
		$this->testTableMm      = 'test_t3lib_dbtest_mm';
		$this->testTableForeign = 'test_t3lib_dbtest_foreign';
		$this->testField        = 'fieldblob';
		$this->testFieldSecond  = 'fieldblub';

		$this->schema = $this->subject->getSchema();
		$this->schemaManager = $this->subject->getSchemaManager();


		$this->table = $this->schema->createTable($this->testTable);
		$this->table->addColumn('id', 'integer', array('unsigned' => TRUE, 'autoincrement' => TRUE));
		$this->table->addColumn('uid', 'integer', array('unsigned' => TRUE));
		$this->table->addColumn($this->testField, 'blob', array('default' => NULL, 'notnull' => FALSE, 'collate'=>'utf8_general_ci'));
		$this->table->addColumn($this->testFieldSecond, 'integer', array('default' => NULL, 'notnull' => FALSE, 'collate'=>'utf8_general_ci'));
		$this->table->setPrimaryKey(array('id'));
		$this->schemaManager->dropAndCreateTable($this->table);

		$this->mmTable = $this->schema->createTable($this->testTableMm);
		$this->mmTable->addColumn('id', 'integer', array('unsigned' => TRUE, 'autoincrement' => TRUE));
		$this->mmTable->addColumn('uid_local', 'integer', array('unsigned' => TRUE));
		$this->mmTable->addColumn('uid_foreign', 'integer', array('unsigned' => TRUE));
		$this->mmTable->addColumn($this->testField, 'blob', array('default' => NULL, 'notnull' => FALSE, 'collate'=>'utf8_general_ci'));
		$this->mmTable->addColumn($this->testFieldSecond, 'integer', array('default' => NULL, 'notnull' => FALSE, 'collate'=>'utf8_general_ci'));
		$this->mmTable->setPrimaryKey(array('id'));

		$this->schemaManager->dropAndCreateTable($this->mmTable);

		$this->foreignTable = $this->schema->createTable($this->testTableForeign);
		$this->foreignTable->addColumn('id', 'integer', array('unsigned' => TRUE, 'autoincrement' => TRUE));
		$this->foreignTable->addColumn('uid', 'integer', array('unsigned' => TRUE));
		$this->foreignTable->addColumn($this->testField, 'blob', array('default' => NULL, 'notnull' => FALSE, 'collate'=>'utf8_general_ci'));
		$this->foreignTable->addColumn($this->testFieldSecond, 'integer', array('default' => NULL, 'notnull' => FALSE, 'collate'=>'utf8_general_ci'));
		$this->foreignTable->setPrimaryKey(array('id'));

		$this->schemaManager->dropAndCreateTable($this->foreignTable);
	}

	/**
	 * Tear the test down
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->schemaManager->dropTable($this->table);
		$this->schemaManager->dropTable($this->mmTable);
		$this->schemaManager->dropTable($this->foreignTable);
		$this->subject->close();
		unset($this->subject, $this->table, $this->mmTable, $this->foreignTable, $this->schemaManager, $this->schema);
	}

	/**
	 * @test
	 */
	public function getNameReturnsTheNameOfTheCurrentDatabaseSystem() {
		$driver = $this->subject->getDatabaseDriver();
		$driver = substr($driver, 4);
		$this->assertEquals($driver, $this->subject->getName());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectDbReturnsTrue() {
		$this->assertTrue($this->subject->selectDb());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @return void
	 */
	public function selectDbReturnsFalse() {
		$this->subject->setDatabaseName('Foo');
		$this->subject->selectDb();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sql_select_dbReturnsTrue() {
		$this->assertTrue($this->subject->sql_select_db());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @return void
	 */
	public function sql_select_dbReturnsFalse() {
		$database = $this->subject->getDatabaseName();
		$this->subject->setDatabaseName('Foo');
		$this->subject->sql_select_db();
		$this->subject->setDatabaseName($database);
	}

	/**
	 * @test
	 */
	public function connectDBConnectsDatabase() {
		$this->subject->disconnectIfConnected();
		$this->assertFalse($this->subject->isConnected());
		$this->subject->connectDB();
		$this->assertTrue($this->subject->isConnected());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sql_affected_rowsReturnsCorrectAmountOfRows() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals(1, $this->subject->sql_affected_rows());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sql_insert_idReturnsCorrectId() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals(1, $this->subject->sql_insert_id());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sql_insert_idReturnsInteger() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertTrue(is_numeric($this->subject->sql_insert_id()));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sql_errorNoError() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'testA'));
		$this->assertEquals('', $this->subject->sql_error());
	}

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'test' in 'field list'
	 */
	public function sql_errorWhenInsertIntoInexistentField() {
		$this->subject->exec_INSERTquery($this->testTable, array('test' => 'test'));
		$this->assertEquals('Unknown column \'test\' in \'field list\'', $this->subject->sql_error());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sql_errnoNoSqlErrorCode() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'testA'));
		$this->assertEquals(0, $this->subject->sql_errno());
	}

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'test' in 'field list'
	 */
	public function sql_errnoSqlErrorNoWhenInsertIntoInexistentField() {
		$this->subject->exec_INSERTquery($this->testTable, array('test' => 'testA'));
		$this->assertEquals(1054, $this->subject->sql_errno());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlPconnectReturnsInstanceOfConnection() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Connection', $this->subject->sql_pconnect());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function connectDbConnectsToDatabaseWithoutErrors() {
		$this->subject->close();
		$this->assertFalse($this->subject->isConnected());
		$this->subject->connectDB();
		$this->assertTrue($this->subject->isConnected());
	}

	/**
	 * Data Provider for fullQuoteStrReturnsQuotedString()
	 *
	 * @see fullQuoteStrReturnsQuotedString()
	 *
	 * @return array
	 */
	public function fullQuoteStrReturnsQuotedStringDataProvider() {
		return array(
			'NULL string with ReturnNull is allowed' => array(array(NULL, TRUE), 'NULL'),
			'NULL string with ReturnNull is false' => array(array(NULL, FALSE), '\'\''),
			'Normal string' => array(array('Foo', FALSE), '\'Foo\''),
			'Single quoted string' => array(array("'Hello'", FALSE), "'\\'Hello\\''"),
			'Double quoted string' => array(array('"Hello"', FALSE), '\'\\"Hello\\"\''),
			'String with internal single tick' => array(array('It\'s me', FALSE), '\'It\\\'s me\''),
			'Slashes' => array(array('/var/log/syslog.log', FALSE), '\'/var/log/syslog.log\''),
			'Backslashes' => array(array('\var\log\syslog.log', FALSE), '\'\\\var\\\log\\\syslog.log\''),
		);
	}

	/**
	 * @test
	 * @dataProvider fullQuoteStrReturnsQuotedStringDataProvider
	 *
	 * @param string $values
	 * @param string $expectedResult
	 *
	 * @return void
	 */
	public function fullQuoteStrReturnsQuotedString($values, $expectedResult) {
		$quotedStr = $this->subject->fullQuoteStr($values[0], $this->testTable, $values[1]);
		$this->assertEquals($expectedResult, $quotedStr);
	}

	/**
	 * Data Provider for fullQuoteArrayQuotesArray()
	 *
	 * @see fullQuoteArrayQuotesArray()
	 *
	 * @return array
	 */
	public function fullQuoteArrayQuotesArrayDataProvider() {
		return array(
			'NULL array with ReturnNull is allowed' => array(
				array(
					array(NULL,NULL),
					FALSE,
					TRUE
				),
				array('NULL', 'NULL')
			),

			'NULL array with ReturnNull is false' => array(
				array(
					array(NULL,NULL),
					FALSE,
					FALSE
				),
				array('\'\'', '\'\'')
			),

			'Strings in array' => array(
				array(
					array('Foo', 'Bar'),
					FALSE,
					FALSE
				),
				array('\'Foo\'', '\'Bar\'')
			),

			'Single quotes in array' => array(
				array(
					array("'Hello'"),
					FALSE,
					FALSE
				),
				array("'\\'Hello\\''")
			),

			'Double quotes in array' => array(
				array(
					array('"Hello"'),
					FALSE,
					FALSE
				),
				array('\'\\"Hello\\"\'')
			),

			'Slashes in array' => array(
				array(
					array('/var/log/syslog.log'),
					FALSE,
					FALSE
				),
				array('\'/var/log/syslog.log\'')
			),

			'Backslashes in array' => array(
				array(
					array('\var\log\syslog.log'),
					FALSE,
					FALSE
				),
				array('\'\\\var\\\log\\\syslog.log\'')
			),

			'Strings with internal single tick' => array(
				array(
					array('Hey!', 'It\'s me'),
					FALSE,
					FALSE
				),
				array('\'Hey!\'', '\'It\\\'s me\'')
			),

			'no quotes strings from array' => array(
				array(
						array(
							'First' => 'Hey!',
							'Second' => 'It\'s me',
							'Third' => 'O\' Reily'
						),
						array('First', 'Third'),
						FALSE
				),
				array('First' =>'Hey!', 'Second' => '\'It\\\'s me\'', 'Third' => 'O\' Reily')
			),

			'no quotes strings from string' => array(
				array(
						array(
							'First' => 'Hey!',
							'Second' => 'It\'s me',
							'Third' => 'O\' Reily'
						),
						'First,Third',
						FALSE
				),
				array('First' =>'Hey!', 'Second' => '\'It\\\'s me\'', 'Third' => 'O\' Reily')
			),
		);
	}

	/**
	 * @test
	 * @dataProvider fullQuoteArrayQuotesArrayDataProvider
	 *
	 * @param string $values
	 * @param string $expectedResult
	 *
	 * @return void
	 */
	public function fullQuoteArrayQuotesArray($values, $expectedResult) {
		$quotedResult = $this->subject->fullQuoteArray($values[0], $this->testTable, $values[1], $values[2]);
		$this->assertSame($expectedResult, $quotedResult);
	}

	/**
	 * Data Provider for quoteStrQuotesDoubleQuotesCorrectly()
	 *
	 * @see quoteStrQuotesDoubleQuotesCorrectly()
	 *
	 * @return array
	 */
	public function quoteStrQuotesCorrectlyDataProvider() {
		return array(
			'Single Quotes' => array('\'Hello\'', '\\\'Hello\\\''),
			'Double Quotes' => array('"Hello"', '\\"Hello\\"'),
			'Slashes' => array('/var/log/syslog.log', '/var/log/syslog.log'),
			'BackSlashes' => array('\var\log\syslog.log', '\\\var\\\log\\\syslog.log')
		);
	}

	/**
	 * @test
	 * @dataProvider quoteStrQuotesCorrectlyDataProvider
	 *
	 * @param string $string String to quote
	 * @param string $expectedResult Quoted string we expect
	 *
	 * @return void
	 */
	public function quoteStrQuotesDoubleQuotesCorrectly($string, $expectedResult) {
		$quotedString = $this->subject->quoteStr($string, $this->testTable);
		$this->assertSame($expectedResult, $quotedString);
	}

	/**
	 * Data Provider for cleanIntArrayReturnsCleanedArray()
	 *
	 * @see cleanIntArrayReturnsCleanedArray()
	 *
	 * @return array
	 */
	public function cleanIntArrayReturnsCleanedArrayDataProvider() {
		return array(
			'Simple numbers' => array(array('234', '-434', 4.3, '4.3'), array(234, -434, 4, 4)),
		);
	}

	/**
	 * @test
	 * @dataProvider cleanIntArrayReturnsCleanedArrayDataProvider
	 *
	 * @param string $values
	 * @param string $exptectedResult
	 *
	 * @return void
	 */
	public function cleanIntArrayReturnsCleanedArray($values, $exptectedResult) {
		$cleanedResult = $this->subject->cleanIntArray($values);
		$this->assertSame($exptectedResult, $cleanedResult);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function cleanIntListReturnsCleanedString() {
		$str = '234,-434,4.3,0, 1';
		$result = $this->subject->cleanIntList($str);
		$this->assertSame('234,-434,4,0,1', $result);
	}


	/**
	 * @test
	 *
	 * @return void
	 */
	public function disconnectIfConnectedDisconnects() {
		$this->assertTrue($this->subject->isConnected());
		$this->subject->setDatabaseHost('127.0.0.1');
		$this->assertFalse($this->subject->isConnected());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForInsertQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForUpdateQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$id = $this->subject->sql_insert_id();
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->admin_query('UPDATE ' . $this->testTable . ' SET fieldblob=\'bar\' WHERE id=' . $id));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForDeleteQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$id = $this->subject->sql_insert_id();
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->admin_query('DELETE FROM ' . $this->testTable . ' WHERE id=' . $id));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsResultForSelectQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$stmt = $this->subject->admin_query('SELECT fieldblob FROM ' . $this->testTable);
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $stmt);
		$result = $stmt->fetch(\PDO::FETCH_ASSOC);
		$this->assertEquals('foo', $result[$this->testField]);
		$stmt->closeCursor();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminCountTablesReturnsNumericValue() {
		$this->assertTrue(is_numeric($this->subject->adminCountTables()));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminGetCharsetsReturnsArrayWithCharsets() {
		$columnsRes = $this->subject->admin_query('SHOW CHARACTER SET');
		$result = $this->subject->admin_get_charsets();
		$this->assertEquals(count($result), $columnsRes->rowCount());

		/** @var array $row */
		while (($row = $columnsRes->fetch(\PDO::FETCH_ASSOC))) {
			$this->assertArrayHasKey($row['Charset'], $result);
		}
		$columnsRes->closeCursor();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminGetKeysReturnIndexKeysOfTable() {
		$result = $this->subject->admin_get_keys($this->testTable);
		$this->assertEquals('id', $result[0]['Column_name']);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminGetFieldsReturnFieldInformationsForTable() {
		$result = $this->subject->admin_get_fields($this->testTable);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey($this->testField, $result);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminGetTablesReturnAllTablesFromDatabase() {
		$result = $this->subject->admin_get_tables();
		$this->assertArrayHasKey('tt_content', $result);
		$this->assertArrayHasKey('pages', $result);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminGetDbsReturnsAllDatabases() {
		$tempDatabasename = $this->subject->getDatabaseName();
		$databases = $this->subject->admin_query('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA');
		$result = $this->subject->admin_get_dbs();
		$this->assertSame(count($result), $databases->rowCount());

		$i = 0;
		while ($database = $databases->fetch(\PDO::FETCH_ASSOC)) {
			$this->assertSame($database['SCHEMA_NAME'], $result[$i]);
			$i++;
		}
		$this->subject->setDatabaseName($tempDatabasename);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function insertQueryCreateValidQuery() {
		$fieldValues = array($this->testField => 'Foo');
		$queryExpected = 'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'Foo\')';
		$queryGenerated = $this->subject->INSERTquery($this->testTable, $fieldValues);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function insertQueryCreateValidQueryFromMultipleValues() {
		$fieldValues = array(
				$this->testField => 'Foo',
				$this->testFieldSecond => 'Bar'
		);
		$queryExpected =
			'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ',' . $this->testFieldSecond . ') VALUES (\'Foo\',\'Bar\')';
		$queryGenerated = $this->subject->INSERTquery($this->testTable, $fieldValues);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function insertMultipleRowsCreateValidQuery() {
		$fields = array($this->testField, $this->testFieldSecond);
		$values = array(
			array('Foo', 100),
			array('Bar', 200),
			array('Baz', 300),
		);
		$queryExpected =
			'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ', ' . $this->testFieldSecond . ') VALUES (\'Foo\', \'100\'), (\'Bar\', \'200\'), (\'Baz\', \'300\')';
		$queryGenerated = $this->subject->INSERTmultipleRows($this->testTable, $fields, $values);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function updateQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$fieldsValues = array($this->testField => 'May the force be with you.');
		$where = 'id=' . $id;
		$queryExpected =
			'UPDATE ' . $this->testTable . ' SET ' . $this->testField . '=\'May the force be with you.\' WHERE id=' . $id;
		$queryGenerated = $this->subject->UPDATEquery($this->testTable, $where, $fieldsValues);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function deleteQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$where = 'id=' . $id;
		$queryExpected =
			'DELETE FROM ' . $this->testTable . ' WHERE id=' . $id;
		$queryGenerated = $this->subject->DELETEquery($this->testTable, $where);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$where = 'id=' . $id;
		$queryExpected =
			'SELECT ' . $this->testField . ' FROM ' . $this->testTable . ' WHERE id=' . $id;
		$queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, $where);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectQueryCreateValidQueryWithEmptyWhereClause() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$where = '';
		$queryExpected =
			'SELECT ' . $this->testField . ' FROM ' . $this->testTable;
		$queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, $where);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectQueryCreateValidQueryWithGroupByClause() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$where = 'id=' . $id;
		$groupBy = 'id';
		$queryExpected =
			'SELECT ' . $this->testField . ' FROM ' . $this->testTable . ' WHERE id=' . $id . ' GROUP BY id';
		$queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, $where, $groupBy);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectQueryCreateValidQueryWithOrderByClause() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$where = 'id=' . $id;
		$orderBy = 'id';
		$queryExpected =
			'SELECT ' . $this->testField . ' FROM ' . $this->testTable . ' WHERE id=' . $id . ' ORDER BY id';
		$queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, $where, '', $orderBy);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectQueryCreateValidQueryWithLimitClause() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, 'id=' . $id, '', '', '1,2');
		$queryExpected =
					'SELECT ' . $this->testField . ' FROM ' . $this->testTable . ' WHERE id=' . $id . ' LIMIT 1,2';
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectSubQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->sql_insert_id();
		$where = 'id=' . $id;
		$queryExpected =
			'SELECT ' . $this->testField . ' FROM ' . $this->testTable . ' WHERE id=' . $id;
		$queryGenerated = $this->subject->SELECTsubquery($this->testField, $this->testTable, $where);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function truncateQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$queryExpected =
			'TRUNCATE TABLE ' . $this->testTable;
		$queryGenerated = $this->subject->TRUNCATEquery($this->testTable);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function createTruncateQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$queryExpected =
			'TRUNCATE ' . $this->testTable;
		$queryGenerated = $this->subject->createTruncateQuery($this->testTable);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function prepareSelectQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$preparedQuery = $this->subject->prepare_SELECTquery('fieldblob,fieldblub', $this->testTable, 'id=:id', '', '', '', array(':id' => 1));
		$preparedQuery->execute();
		$result = $preparedQuery->fetch();
		$expectedResult = array(
			'fieldblob' => 'foo',
			'fieldblub' => null
		);
		$this->assertSame($expectedResult['fieldblob'], $result['fieldblob']);
		$this->assertSame($expectedResult['fieldblub'], $result['fieldblub']);
	}

	/**
	 * Data Provider for sqlNumRowsReturnsCorrectAmountOfRows()
	 *
	 * @see sqlNumRowsReturnsCorrectAmountOfRows()
	 *
	 * @return array
	 */
	public function sqlNumRowsReturnsCorrectAmountOfRowsProvider() {
		$sql1 = 'SELECT * FROM test_t3lib_dbtest WHERE fieldblob=\'baz\'';
		$sql2 = 'SELECT * FROM test_t3lib_dbtest WHERE fieldblob=\'baz\' OR fieldblob=\'bar\'';
		$sql3 = 'SELECT * FROM test_t3lib_dbtest WHERE fieldblob=\'baz\' OR fieldblob=\'bar\' OR fieldblob=\'foo\'';

		return array(
			'One result' => array($sql1, 1),
			'Two results' => array($sql2, 2),
			'Three results' => array($sql3, 3),
		);
	}


	/**
	 * @test
	 */
	public function getLastInsertIdReturnsCorrectId() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testB'));
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testC'));

		$this->assertEquals(3, $this->subject->sql_insert_id());
	}

	/**
	 * @test
	 */
	public function getLastInsertIdReturnsInteger() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));

		$this->assertTrue(is_integer($this->subject->sql_insert_id()));
	}

	/**
	 * @test
	 * @dataProvider sqlNumRowsReturnsCorrectAmountOfRowsProvider
	 *
	 * @param string $sql
	 * @param string $expectedResult
	 *
	 * @return void
	 */
	public function sqlNumRowsReturnsCorrectAmountOfRows($sql, $expectedResult) {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'bar')));
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'baz')));

		$res = $this->subject->admin_query($sql);
		$numRows = $this->subject->sql_num_rows($res);

		$this->assertSame($expectedResult, $numRows);
	}

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'test' in 'where clause'
	 */
	public function sqlNumRowsReturnsFalse() {
		$res = $this->subject->admin_query('SELECT * FROM ' . $this->testTable . ' WHERE test=\'baz\'');
		$numRows = $this->subject->sql_num_rows($res);
		$this->assertFalse($numRows);
	}

	/**
	 * Prepares the test table for the fetch* Tests
	 *
	 * @return void
	 */
	protected function prepareTableForFetchTests() {
		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->sql_query('ALTER TABLE ' . $this->testTable . '
				ADD name mediumblob;
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->sql_query('ALTER TABLE ' . $this->testTable . '
				ADD deleted int;
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->sql_query('ALTER TABLE ' . $this->testTable . '
				ADD street varchar(100);
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->sql_query('ALTER TABLE ' . $this->testTable . '
				ADD city varchar(50);
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->sql_query('ALTER TABLE ' . $this->testTable . '
				ADD country varchar(100);
			')
		);

		$values = array(
			'name' => 'Mr. Smith',
			'street' => 'Oakland Road',
			'city' => 'Los Angeles',
			'country' => 'USA',
			'deleted' => 0
		);
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, $values));

		$values = array(
			'name' => 'Ms. Smith',
			'street' => 'Oakland Road',
			'city' => 'Los Angeles',
			'country' => 'USA',
			'deleted' => 0
		);
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, $values));

		$values = array(
			'name' => 'Alice im Wunderland',
			'street' => 'Große Straße',
			'city' => 'Königreich der Herzen',
			'country' => 'Wunderland',
			'deleted' => 0
		);
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, $values));

		$values = array(
			'name' => 'Agent Smith',
			'street' => 'Unknown',
			'city' => 'Unknown',
			'country' => 'Matrix',
			'deleted' => 1
		);
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, $values));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFetchAssocReturnsAssocArray() {
		$this->prepareTableForFetchTests();

		$res = $this->subject->admin_query('SELECT * FROM ' . $this->testTable);
		$expectedResult = array(
			array(
				'id' => '1',
				'fieldblob' => null,
				'fieldblub' => null,
				'name'      => 'Mr. Smith',
				'deleted'   => '0',
				'street'    => 'Oakland Road',
				'city'      => 'Los Angeles',
				'country'   => 'USA'
			),
			array(
				'id' => '2',
				'fieldblob' => null,
				'fieldblub' => null,
				'name'      => 'Ms. Smith',
				'deleted'   => '0',
				'street'    => 'Oakland Road',
				'city'      => 'Los Angeles',
				'country'   => 'USA'
			),
			array(
				'id' => '3',
				'fieldblob' => null,
				'fieldblub' => null,
				'name'      => 'Alice im Wunderland',
				'deleted'   => '0',
				'street'    => 'Große Straße',
				'city'      => 'Königreich der Herzen',
				'country'   => 'Wunderland'
			),
			array(
				'id' => '4',
				'fieldblob' => null,
				'fieldblub' => null,
				'name'      => 'Agent Smith',
				'deleted'   => '1',
				'street'    => 'Unknown',
				'city'      => 'Unknown',
				'country'   => 'Matrix'
			)
		);
		$i = 0;
		while ($row = $this->subject->sql_fetch_assoc($res)) {
			$this->assertSame($expectedResult[$i]['id'], $row['id']);
			$this->assertSame($expectedResult[$i]['fieldblob'], $row['fieldblob']);
			$this->assertSame($expectedResult[$i]['fieldblub'], $row['fieldblub']);
			$this->assertSame($expectedResult[$i]['name'], $row['name']);
			$this->assertSame($expectedResult[$i]['deleted'], $row['deleted']);
			$this->assertSame($expectedResult[$i]['street'], $row['street']);
			$this->assertSame($expectedResult[$i]['city'], $row['city']);
			$this->assertSame($expectedResult[$i]['country'], $row['country']);
			$i++;
		}
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFetchRowReturnsNumericArray() {
		$this->prepareTableForFetchTests();
		$res = $this->subject->admin_query('SELECT * FROM ' . $this->testTable);
		$expectedResult = array(
					array('1', '0', null, null, 'Mr. Smith', '0', 'Oakland Road', 'Los Angeles', 'USA'),
					array('2', '0', null, null, 'Ms. Smith', '0', 'Oakland Road', 'Los Angeles', 'USA'),
					array('3', '0', null, null, 'Alice im Wunderland', '0', 'Große Straße', 'Königreich der Herzen', 'Wunderland'),
					array('4', '0', null, null, 'Agent Smith', '1', 'Unknown', 'Unknown', 'Matrix')
				);
		$i = 0;
		while ($row = $this->subject->fetchRow($res)) {
			$this->assertSame($expectedResult[$i], $row);
			$i++;
		}
	}

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'baz' in 'where clause'
	 */
	public function sqlFreeResultReturnsFalse() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'baz')));
		$res = $this->subject->admin_query('SELECT * FROM test_t3lib_dbtest WHERE fieldblob=baz');
		$this->assertFalse($this->subject->sql_free_result($res));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFreeResultReturnsTrue() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'baz')));
		$res = $this->subject->admin_query('SELECT * FROM test_t3lib_dbtest WHERE fieldblob=\'baz\'');
		$this->assertTrue($this->subject->sql_free_result($res));
	}

	//////////////////////////////////////////////////
	// Write/Read tests for charsets and binaries
	//////////////////////////////////////////////////
	/**
	 * @test
	 *
	 * @return void
	 */
	public function storedFullAsciiRangeReturnsSameData() {
		$binaryString = '';
		for ($i = 0; $i < 256; $i++) {
			$binaryString .= chr($i);
		}
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => $binaryString));
		$id = $this->subject->sql_insert_id();
		$entry = $this->subject->exec_SELECTgetRows($this->testField, $this->testTable, 'id = ' . $id);
		$this->assertEquals($binaryString, $entry[0][$this->testField]);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function storedGzipCompressedDataReturnsSameData() {
		$testStringWithBinary = @gzcompress('sdfkljer4587');
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => $testStringWithBinary));
		$id = $this->subject->sql_insert_id();
		$entry = $this->subject->exec_SELECTgetRows($this->testField, $this->testTable, 'id = ' . $id);
		$this->assertEquals($testStringWithBinary, $entry[0][$this->testField]);
	}

	////////////////////////////////
	// Tests concerning listQuery
	////////////////////////////////
	/**
	 * @test
	 *
	 * @return void
	 * @see http://forge.typo3.org/issues/23253
	 */
	public function listQueryWithIntegerCommaAsValue() {
		// Note: 44 = ord(',')
		$this->assertEquals($this->subject->listQuery('dummy', 44, 'table'), $this->subject->listQuery('dummy', '44', 'table'));
	}

	////////////////////////////////
	// Tests concerning searchQuery
	////////////////////////////////

	/**
	 * Data provider for searchQueryCreatesQuery
	 *
	 * @return array
	 */
	public function searchQueryDataProvider() {
		return array(
			'One search word in one field' => array(
				'(pages.title LIKE \'%TYPO3%\')',
				array('TYPO3'),
				array('title'),
				'pages',
				'AND'
			),

			'One search word in multiple fields' => array(
				'(pages.title LIKE \'%TYPO3%\' OR pages.keyword LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\')',
				array('TYPO3'),
				array('title', 'keyword', 'description'),
				'pages',
				'AND'
			),

			'Multiple search words in one field with AND constraint' => array(
				'(pages.title LIKE \'%TYPO3%\') AND (pages.title LIKE \'%is%\') AND (pages.title LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title'),
				'pages',
				'AND'
			),

			'Multiple search words in one field with OR constraint' => array(
				'(pages.title LIKE \'%TYPO3%\') OR (pages.title LIKE \'%is%\') OR (pages.title LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title'),
				'pages',
				'OR'
			),

			'Multiple search words in multiple fields with AND constraint' => array(
				'(pages.title LIKE \'%TYPO3%\' OR pages.keywords LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\') AND ' .
					'(pages.title LIKE \'%is%\' OR pages.keywords LIKE \'%is%\' OR pages.description LIKE \'%is%\') AND ' .
					'(pages.title LIKE \'%great%\' OR pages.keywords LIKE \'%great%\' OR pages.description LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title', 'keywords', 'description'),
				'pages',
				'AND'
			),

			'Multiple search words in multiple fields with OR constraint' => array(
				'(pages.title LIKE \'%TYPO3%\' OR pages.keywords LIKE \'%TYPO3%\' OR pages.description LIKE \'%TYPO3%\') OR ' .
					'(pages.title LIKE \'%is%\' OR pages.keywords LIKE \'%is%\' OR pages.description LIKE \'%is%\') OR ' .
					'(pages.title LIKE \'%great%\' OR pages.keywords LIKE \'%great%\' OR pages.description LIKE \'%great%\')',
				array('TYPO3', 'is', 'great'),
				array('title', 'keywords', 'description'),
				'pages',
				'OR'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider searchQueryDataProvider
	 *
	 * @param $expectedResult
	 * @param $searchWords
	 * @param $fields
	 * @param $table
	 * @param $constraint
	 *
	 * @return void
	 */
	public function searchQueryCreatesQuery($expectedResult, $searchWords, $fields, $table, $constraint) {
		$result = $this->subject->searchQuery($searchWords, $fields, $table, $constraint);
		$this->assertSame($expectedResult, $result);
	}

	/////////////////////////////////////////////////
	// Tests concerning escapeStringForLikeComparison
	/////////////////////////////////////////////////
	/**
	 * @test
	 *
	 * @return void
	 */
	public function escapeStringForLikeComparison() {
		$this->assertEquals('foo\\_bar\\%', $this->subject->escapeStrForLike('foo_bar%', 'table'));
	}

	/////////////////////////////////////////////////
	// Tests concerning stripOrderByForOrderByKeyword
	/////////////////////////////////////////////////


	/**
	 * Data Provider for stripGroupByForGroupByKeyword()
	 *
	 * @see stripOrderByForOrderByKeyword()
	 * @return array
	 */
	public function stripOrderByForOrderByKeywordDataProvider() {
		return array(
			'single ORDER BY' => array('ORDER BY name, tstamp', 'name, tstamp'),
			'single ORDER BY in lower case' => array('order by name, tstamp', 'name, tstamp'),
			'ORDER BY with additional space behind' => array('ORDER BY  name, tstamp', 'name, tstamp'),
			'ORDER BY without space between the words' => array('ORDERBY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice' => array('ORDER BY ORDER BY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces in the first occurrence' => array('ORDERBY ORDER BY  name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces in the second occurrence' => array('ORDER BYORDERBY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces' => array('ORDERBYORDERBY name, tstamp', 'name, tstamp'),
			'ORDER BY added twice without spaces afterwards' => array('ORDERBYORDERBYname, tstamp', 'name, tstamp'),
		);
	}

	/**
	 * @test
	 * @dataProvider stripOrderByForOrderByKeywordDataProvider
	 *
	 * @param string $orderByClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function stripOrderByForOrderByKeyword($orderByClause, $expectedResult) {
		$strippedQuery = $this->subject->stripOrderBy($orderByClause);
		$this->assertEquals($expectedResult, $strippedQuery);
	}

	/////////////////////////////////////////////////
	// Tests concerning stripGroupByForGroupByKeyword
	/////////////////////////////////////////////////

	/**
	 * Data Provider for stripGroupByForGroupByKeyword()
	 *
	 * @see stripGroupByForGroupByKeyword()
	 *
	 * @return array
	 */
	public function stripGroupByForGroupByKeywordDataProvider() {
		return array(
			'single GROUP BY' => array('GROUP BY name, tstamp', 'name, tstamp'),
			'single GROUP BY in lower case' => array('group by name, tstamp', 'name, tstamp'),
			'GROUP BY with additional space behind' => array('GROUP BY  name, tstamp', 'name, tstamp'),
			'GROUP BY without space between the words' => array('GROUPBY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice' => array('GROUP BY GROUP BY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces in the first occurrence' => array('GROUPBY GROUP BY  name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces in the second occurrence' => array('GROUP BYGROUPBY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces' => array('GROUPBYGROUPBY name, tstamp', 'name, tstamp'),
			'GROUP BY added twice without spaces afterwards' => array('GROUPBYGROUPBYname, tstamp', 'name, tstamp'),
		);
	}

	/**
	 * @test
	 * @dataProvider stripGroupByForGroupByKeywordDataProvider
	 *
	 * @param string $groupByClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function stripGroupByForGroupByKeyword($groupByClause, $expectedResult) {
		$strippedQuery = $this->subject->stripGroupBy($groupByClause);
		$this->assertEquals($expectedResult, $strippedQuery);
	}


	/**
	 * Data Provider for splitGroupOrderLimitStripsLastPartOfQueryIntoArray()
	 *
	 * @see splitGroupOrderLimitStripsLastPartOfQueryIntoArray()
	 *
	 * @return array
	 */
	public function splitGroupOrderLimitDataProvider() {
		return array(
			'normal WEHRE clause' => array(
				'uid=123 GROUP BY title ORDER BY title LIMIT 5,2',
				array(
					'WHERE'   => ' uid=123',
					'GROUPBY' => 'title',
					'ORDERBY' => 'title',
					'LIMIT'   => '5,2'
				)
			),

			'no WHERE in clause' => array(
				'GROUP BY title ORDER BY title LIMIT 5,2',
				array(
					'WHERE'   => '',
					'GROUPBY' => 'title',
					'ORDERBY' => 'title',
					'LIMIT'   => '5,2'
				)
			),

			'no GROUP BY in clause' => array(
				'uid=123 ORDER BY title LIMIT 5,2',
				array(
					'WHERE'   => ' uid=123',
					'GROUPBY' => '',
					'ORDERBY' => 'title',
					'LIMIT'   => '5,2'
				)
			),

			'no ORDER BY clause' => array(
				'uid=123 GROUP BY title LIMIT 5,2',
				array(
					'WHERE'   => ' uid=123',
					'GROUPBY' => 'title',
					'ORDERBY' => '',
					'LIMIT'   => '5,2'
				)
			),

			'no LIMIT clause' => array(
				'uid=123 GROUP BY title ORDER BY title',
				array(
					'WHERE'   => ' uid=123',
					'GROUPBY' => 'title',
					'ORDERBY' => 'title',
					'LIMIT'   => ''
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider splitGroupOrderLimitDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function splitGroupOrderLimitStripsLastPartOfQueryIntoArray($whereClause, $expectedResult) {
		$generatedResult = $this->subject->splitGroupOrderLimit($whereClause);
		$this->assertSame($expectedResult, $generatedResult);
	}

	/**
	 * @test
	 */
	public function executeDeleteQueryReturnsInsertRows() {
		$fields = array(
				$this->testField => 'Foo',
				$this->testFieldSecond => 'Bar'
			);

		$inserted = $this->subject->executeInsertQuery($this->testTable, $fields);
		$this->assertSame(1, $inserted);

		$deleted = $this->subject->executeDeleteQuery($this->testTable, $fields);
		$this->assertSame(1, $deleted);
	}

	/**
	 * @test
	 */
	public function executeInsertQueryReturnsInsertRows() {
		$fields = array(
				$this->testField => 'Foo',
				$this->testFieldSecond => 'Bar'
			);

		$result = $this->subject->executeInsertQuery($this->testTable, $fields);
		$this->assertSame(1, $result);
	}

	/**
	 * @test
	 */
	public function quoteColumnWithoutTableName() {
		$this->assertEquals('`column`', $this->subject->quoteColumn('column'));
	}

	/**
	 * @test
	 */
	public function quoteColumnWithTableName() {
		$this->assertEquals('`pages`.`column`', $this->subject->quoteColumn('column', 'pages'));
	}

	/**
	 * @test
	 */
	public function quoteTable() {
		$this->assertEquals('`pages`', $this->subject->quoteTable('pages'));
	}

	/**
	 * @test
	 */
	public function quoteIdentifier(){
		$this->assertEquals('`pages`', $this->subject->quoteIdentifier('pages'));
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForCreateTestTable() {
		$expectedSql = 'CREATE TABLE test_t3lib_dbtest_foreign (id INT UNSIGNED AUTO_INCREMENT NOT NULL, uid INT UNSIGNED NOT NULL, fieldblob LONGBLOB DEFAULT NULL, fieldblub INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB';
		$this->assertEquals($expectedSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_TRUNCATEquery() {
		$this->subject->exec_TRUNCATEquery($this->testTable);
		$this->assertEquals('TRUNCATE TABLE ' . $this->testTable, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_DELETEquery() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->exec_DELETEquery($this->testTable, $this->testField . '=\'testA\'');
		$this->assertEquals('DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . '=\'testA\'', $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_INSERTquery() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'testA'));
		$this->assertEquals('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'testA\')', $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_INSERTmultipleRows() {
		$columns = array();
		$columns[] = $this->testField;
		$columns[] = $this->testFieldSecond;
		$tags = array('A', 'B', 'C', 'D');
		$tagsRows = array();
		foreach ($tags as $tag) {
			$tagsRow = array();
			$tagsRow[] = 'Identifier';
			$tagsRow[] = $tag;
			$tagsRows[] = $tagsRow;
		}

		$this->subject->exec_INSERTmultipleRows($this->testTable, $columns, $tagsRows);
		$expectingSql = 'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ', ' . $this->testFieldSecond . ') VALUES (\'Identifier\', \'A\'), (\'Identifier\', \'B\'), (\'Identifier\', \'C\'), (\'Identifier\', \'D\')';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_UPDATEquery() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->exec_UPDATEquery($this->testTable, $this->testField . '=\'testA\'', array($this->testFieldSecond => 3));
		$expectingSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testFieldSecond . '=\'3\' WHERE ' . $this->testField . '=\'testA\'';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_SELECTquery() {
		$this->subject->exec_SELECTquery($this->testField . ',' . $this->testFieldSecond, $this->testTable, $this->testField . '= 98');
		$expectingSql = 'SELECT ' . $this->testField . ',' . $this->testFieldSecond . ' FROM ' . $this->testTable . ' WHERE ' . $this->testField . '= 98';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_SELECT_mm_query() {
		$this->subject->exec_SELECT_mm_query($this->testTable . '.*', $this->testTable, $this->testTableMm, $this->testTableForeign, 'AND 1 = 1');
		$expectingSql = 'SELECT ' . $this->testTable .
				'.* FROM ' . $this->testTable . ',' . $this->testTableMm . ',' . $this->testTableForeign .
				' WHERE ' . $this->testTable . '.uid=' . $this->testTableMm . '.uid_local AND ' .
				$this->testTableForeign  . '.uid=' . $this->testTableMm . '.uid_foreign AND 1 = 1';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_SELECTcountRows() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testB'));

		$result = $this->subject->exec_SELECTcountRows($this->testField, $this->testTable, $this->testField . '= \'testA\'');
		$expectingSql = 'SELECT COUNT(' . $this->testField . ') FROM ' . $this->testTable . ' WHERE ' . $this->testField . '= \'testA\'';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
		$this->assertEquals(2, $result);
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_SELECT_queryArray() {
		$selectFields = array();
		$selectFields[] = $this->testField;
		$selectFields[] = $this->testFieldSecond;

		$sqlQueryParts = array(
			'SELECT' => join(',', $selectFields),
			'FROM'   => $this->testTable,
			'WHERE'  => '1 = 1'
		);
		$this->subject->exec_SELECT_queryArray($sqlQueryParts);
		$expectingSql = 'SELECT ' . $this->testField . ',' . $this->testFieldSecond . ' FROM ' . $this->testTable . ' WHERE 1 = 1';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_SELECTgetRows() {
		$this->subject->exec_SELECTgetRows($this->testField . ',' . $this->testFieldSecond, $this->testTable, $this->testField . '= 98');
		$expectingSql = 'SELECT ' . $this->testField . ',' . $this->testFieldSecond . ' FROM ' . $this->testTable . ' WHERE ' . $this->testField . '= 98';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForExec_SELECTgetSingleRow() {
		$this->subject->exec_SELECTgetSingleRow($this->testField . ',' . $this->testFieldSecond, $this->testTable, $this->testField . '= 98');
		$expectingSql = 'SELECT ' . $this->testField . ',' . $this->testFieldSecond . ' FROM ' . $this->testTable . ' WHERE ' . $this->testField . '= 98 LIMIT 1';
		$this->assertEquals($expectingSql, $this->subject->getLastStatement());
	}
}