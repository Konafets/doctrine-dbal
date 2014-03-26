<?php

namespace TYPO3\DoctrineDbal\Tests\Unit\Persistence\Doctrine;

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
use TYPO3\DoctrineDbal\Persistence\Doctrine\DatabaseConnection;

/**
 * Class DatabaseConnectionTest
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class DatabaseConnectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Doctrine\DatabaseConnection
	 */
	private $subject = NULL;

	/**
	 * @var string
	 */
	protected $testTable;

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
	 * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	protected $schemaManager;

	/**
	 * @var array The connection settings for Doctrine
	 */
	protected $connectionParams = array(
		'dbname' => '',
		'user' => '',
		'password' => '',
		'host' => 'localhost',
		'driver' => 'pdo_mysql',
		'port' => 3306,
		'charset' => 'utf8',
	);

	/**
	 * Set the test up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = GeneralUtility::makeInstance('TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\DatabaseConnection');
		$this->subject->setDatabaseName(TYPO3_db);
		$this->subject->setDatabaseUsername(TYPO3_db_username);
		$this->subject->setDatabasePassword(TYPO3_db_password);
		$this->subject->setDatabasePort($GLOBALS['TYPO3_DB']->getDatabasePort());
		$this->subject->setDatabaseHost($GLOBALS['TYPO3_DB']->getDatabaseHost());
		$this->subject->connectDatabase();

		$this->testTable       = 'test_t3lib_dbtest';
		$this->testField       = 'fieldblob';
		$this->testFieldSecond = 'fieldblub';

		$this->schema = $this->subject->getSchema();
		$this->schemaManager = $this->subject->getSchemaManager();


		$this->table = $this->schema->createTable($this->testTable);
		$this->table->addColumn('id', 'integer', array('unsigned' => TRUE, 'autoincrement' => TRUE));
		$this->table->addColumn($this->testField, 'blob', array('default' => NULL, 'notnull' => FALSE));
		$this->table->addColumn($this->testFieldSecond, 'integer', array('default' => NULL, 'notnull' => FALSE));
		$this->table->setPrimaryKey(array('id'));
		$this->schemaManager->dropAndCreateTable($this->table);
	}

	/**
	 * Tear the test down
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->schemaManager->dropTable($this->table);
		$this->subject->close();
		unset($this->subject, $this->table, $this->schemaManager, $this->schema);
	}

	/******************************
	 *
	 * Tests for setter and getter
	 *
	 *****************************/

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
	 */
	public function setAndGetUserNameCorrectly() {
		// Keep the old user name in mind
		$username = $this->subject->getDatabaseUsername();

		$this->subject->setDatabaseUsername('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabaseUsername());

		$this->subject->setDatabaseUsername($username);
	}

	/**
	 * @test
	 */
	public function setAndGetUserPasswordCorrectly() {
		// Keep the old password in mind
		$password = $this->subject->getDatabasePassword();

		$this->subject->setDatabasePassword('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabasePassword());

		$this->subject->setDatabasePassword($password);
	}

	/**
	 * @test
	 */
	public function setAndGetDatabaseName() {
		// Keep the old database name in mind
		$database = $this->subject->getDatabaseName();

		$this->subject->setDatabaseName('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabaseName());

		$this->subject->setDatabaseName($database);
	}

	/**
	 * @test
	 */
	public function setAndGetDatabaseDriver() {
		// Keep the old database driver in mind
		$driver = $this->subject->getDatabaseDriver();

		$this->subject->setDatabaseDriver('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabaseDriver());

		$this->subject->setDatabaseDriver($driver);
	}

	/**
	 * @test
	 */
	public function setDatabaseDriverSetsPortToMySql() {
		$this->subject->setDatabasePort();
		$this->assertEquals('pdo_mysql', $this->subject->getDatabaseDriver());
	}

	/**
	 * @test
	 */
	public function databaseDriverIsSetByDefaultToMySql() {
		$this->assertEquals('pdo_mysql', $this->subject->getDatabaseDriver());
	}

	/**
	 * @test
	 */
	public function setAndGetDatabaseSocket() {
		// Keep the old database socket in mind
		$socket = $this->subject->getDatabaseSocket();

		$this->subject->setDatabaseSocket('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabaseSocket());

		$this->subject->setDatabaseSocket($socket);
	}

	/**
	 * @test
	 */
	public function setAndGetDatabasePort() {
		// Keep the old port in mind
		$port = $this->subject->getDatabasePort();

		$this->subject->setDatabasePort(1234);
		$this->assertEquals(1234, $this->subject->getDatabasePort());

		$this->subject->setDatabasePort($port);
	}

	/**
	 * @test
	 */
	public function setDatabasePortSetsPortTo3306() {
		$this->subject->setDatabasePort();
		$this->assertEquals(3306, $this->subject->getDatabasePort());
	}

	/**
	 * @test
	 */
	public function databasePortIsSetByDefaultTo3306() {
		$this->assertEquals(3306, $this->subject->getDatabasePort());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 */
	public function passedArgumentsAsPortIsNotIntegerThrowsException() {
		$this->subject->setDatabasePort('Foo');
	}

	/**
	 * @test
	 */
	public function passedArgumentsAsPortIsStringButNumeric() {
		$this->subject->setDatabasePort('3306');
	}

	/**
	 * @test
	 */
	public function setAndGetDatabaseHost() {
		// Keep the old host in mind
		$host = $this->subject->getDatabaseHost();

		$this->subject->setDatabaseHost('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabaseHost());

		$this->subject->setDatabaseHost($host);
	}

	/**
	 * @test
	 */
	public function setDatabaseSetDefaultHostToLocalhost() {
		// Keep the old host in mind
		$host = $this->subject->getDatabaseHost();

		$this->subject->setDatabaseHost();
		$this->assertEquals('localhost', $this->subject->getDatabaseHost());

		$this->subject->setDatabaseHost($host);
	}

	/**
	 * @test
	 */
	public function setAndGetDatabaseCharset() {
		// Keep the old charset in mind
		$charset = $this->subject->getDatabaseCharset();

		$this->subject->setDatabaseCharset('Foo');
		$this->assertEquals('Foo', $this->subject->getDatabaseCharset());

		$this->subject->setDatabaseCharset($charset);
	}

	/**
	 * @test
	 */
	public function setDatabaseCharsetSetsCharsetToUtf8() {
		$this->subject->setDatabaseCharset();
		$this->assertEquals('utf8', $this->subject->getDatabaseCharset());
	}

	/**
	 * @test
	 */
	public function databaseCharsetIsSetByDefaultToUtf8() {
		$this->assertEquals('utf8', $this->subject->getDatabaseCharset());
	}

	/**
	 * @test
	 */
	public function setAndGetDatabaseHandle() {
		// Keep the old database handle in mind
		$handle = $this->subject->getDatabaseHandle();

		$this->subject->setDatabaseHandle($this->getConnectionMock());
		$this->assertInstanceOf('\Doctrine\DBAL\Connection', $this->subject->getDatabaseHandle());

		$this->subject->setDatabaseHandle($handle);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 */
	public function passAWrongObjectToSetDataBaseHandleWillThrowException() {
		$this->subject->setDatabaseHandle('Foo');
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForCreateTestTable() {
		$expectedSql = 'CREATE TABLE test_t3lib_dbtest (id INT UNSIGNED AUTO_INCREMENT NOT NULL, fieldblob LONGBLOB DEFAULT NULL, fieldblub INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB';
		$this->assertEquals($expectedSql, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForTruncate() {
		$this->subject->executeTruncateQuery($this->testTable);
		$this->assertEquals('TRUNCATE ' . $this->testTable, $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForDelete() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->executeDeleteQuery($this->testTable, array($this->testField => 'testA'));
		$this->assertEquals('DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' = ?', $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForInsert() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->assertEquals('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (?)', $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForUpdate() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->executeUpdateQuery($this->testTable, array($this->testField => 'testA'), array($this->testFieldSecond => 3));
		$this->assertEquals('UPDATE ' . $this->testTable . ' SET ' . $this->testFieldSecond . ' = ? WHERE ' . $this->testField . ' = ?', $this->subject->getLastStatement());
	}

	/**
	 * @test
	 */
	public function getLastStatementReturnsLastStatementForSelect() {
		$this->markTestIncomplete('Implement Select methods');
		$this->subject->executeTruncateQuery($this->testTable);
		$this->assertEquals('TRUNCATE ' . $this->testTable, $this->subject->getLastStatement());
	}



	/**
	 * @test
	 */
	public function getLastInsertIdReturnsCorrectId() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testB'));
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testC'));

		$this->assertEquals(3, $this->subject->getLastInsertId($this->testTable));
	}

	/**
	 * @test
	 */
	public function getLastInsertIdReturnsInteger() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testA'));

		$this->assertTrue(is_integer($this->subject->getLastInsertId($this->testTable)));
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
	 */
	public function isConnectedReturnsStateOfConnection() {
		$this->assertTrue($this->subject->isConnected());
		$this->subject->disconnectIfConnected();
		$this->assertFalse($this->subject->isConnected());
	}

	/**
	 * @test
	 */
	public function closeClosesConnection() {
		$this->subject->close();

		$this->assertFalse($this->subject->isConnected());
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

	/***************************
	 *
	 * Tests for DELETE queries
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function createDeleteQueryReturnsDeleteQueryObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\DeleteQueryInterface',
				$this->subject->createDeleteQuery()
		);
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
	 *
	 * @return void
	 */
	public function deleteQueryWithoutWhereClauseCreateValidQuery() {
		$expectedSql = 'DELETE FROM ' . $this->testTable;
		$queryGenerated = $this->subject->deleteQuery($this->testTable)->getSql();
		$this->assertSame($expectedSql, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function deleteQueryCreateValidQuery() {
		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . '=Foo';
		$queryGenerated = $this->subject->deleteQuery($this->testTable, array($this->testField => 'Foo'))->getSql();
		$this->assertSame($expectedSql, $queryGenerated);
	}

	/****************************
	 *
	 * Tests for TRUNCATE queries
	 *
	 ****************************/

	/**
	 * @test
	 */
	public function createTruncateQueryReturnsTruncateQueryObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\TruncateQueryInterface',
				$this->subject->createTruncateQuery()
		);
	}

	/***************************
	 *
	 * Tests for UPDATE queries
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function createUpdateQueryReturnsUpdateQueryObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\UpdateQueryInterface',
				$this->subject->createUpdateQuery()
		);
	}

	/**
	 * @test
	 */
	public function executeUpdateQueryUpdatesValues() {
		$this->markTestSkipped('TBD');
	}

	/**
	 * @test
	 */
	public function updateQueryReturnsCorrectSqlQuery() {
		$this->markTestIncomplete('Not ready yet');
		$result = $this->subject->updateQuery($this->testTable, array($this->testField => 3), array($this->testField => 'Foo', $this->testFieldSecond => 8));
		$expectedSql = 'UPDATE ' . $this->testTable .
				' SET ' . $this->testField . ' = ?, ' . $this->testFieldSecond . ' = ?' .
				' WHERE ' . $this->testField . ' = ?';

		$this->assertEquals($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function updateQueryReturnsCorrectSqlQueryWithParameter() {
		$this->markTestIncomplete('Not ready yet');
		$result = $this->subject->updateQuery($this->testTable, array($this->testField => 3), array($this->testField => 'Foo', $this->testFieldSecond => 8), array(), TRUE);
		$expectedSql = 'UPDATE ' . $this->testTable .
				' SET ' . $this->testField . ' = ?, ' . $this->testFieldSecond . ' = ?' .
				' WHERE ' . $this->testField . ' = ? [Foo, 8, 3]';

		$this->assertEquals($expectedSql, $result);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function updateQueryCreateValidQuery() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'foo')));
		$id = $this->subject->getLastInsertId($this->testTable);
		$fieldsValues = array($this->testField => 'May the force be with you.');
		$where = array('id' => $id);
		$queryExpected =
			'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = May the force be with you. WHERE id=' . $id;
		$queryGenerated = $this->subject->updateQuery($this->testTable, $where, $fieldsValues)->getSql();
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/***************************
	 *
	 * Tests for INSERT queries
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function createInsertQueryReturnsInsertQueryObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\InsertQueryInterface',
				$this->subject->createInsertQuery()
		);
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
	public function executeInsertQueryReturnsCorrectAmountOfAffectedRows() {
		$rows = $this->subject->executeInsertQuery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals(1, $rows);
		$this->assertEquals(1, $this->subject->getAffectedRows());
	}

	/***************************
	 *
	 * Tests for SELECT queries
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function createSelectQueryReturnsSelectQueryObject() {
		$this->markTestIncomplete('Implement createSelectQuery');
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\SelectQueryInterface',
				$this->subject->createSelectQuery()
		);
	}

	/***************************
	 *
	 * Tests for Expression object
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function exprReturnsExpressionObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\ExpressionInterface',
				$this->subject->expr()
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function getAffectedRowsReturnsInteger() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'test'));
		$this->assertTrue(is_integer($this->subject->getAffectedRows()));
	}

	/***************************
	 *
	 * Tests for sqlErrorMessage
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function sqlErrorMessageNoError() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testB'));
		$this->assertEquals('', $this->subject->sqlErrorMessage());
	}

	/**
	 * @test
	 *
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'test' in 'field list'
	 */
	public function sqlErrorWhenInsertIntoInexistentField() {
		$this->subject->executeInsertQuery($this->testTable, array('test' => 'test'));
		$this->assertEquals('Unknown column \'test\' in \'field list\'', $this->subject->sqlErrorMessage());
	}

	/***************************
	 *
	 * Tests for SqlErrorCode
	 *
	 ***************************/

	/**
	 * @test
	 */
	public function noSqlErrorCode() {
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => 'testB'));
		$this->assertEquals(0, $this->subject->sqlErrorCode());
	}

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'test' in 'field list'
	 */
	public function sqlErrorNoWhenInsertIntoInexistentField() {
		$this->subject->executeInsertQuery($this->testTable, array('test' => 'testB'));
		$this->assertEquals(1054, $this->subject->sqlErrorCode());
	}

	/***************************
	 *
	 * Mocking objects
	 *
	 ***************************/

	/**
	 * Returns a Connection mock
	 *
	 * @param bool $callConstructor
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	public function getConnectionMock($callConstructor = FALSE) {
		$mock = $this->getMockBuilder('\Doctrine\DBAL\Connection');

		if (!$callConstructor) {
			$mock->disableOriginalConstructor();
		}

		return $mock->getMock();
	}

	/**
	 * @test
	 */
	public function connectDatabaseConnectsDatabase() {
		$this->subject->disconnectIfConnected();
		$this->assertFalse($this->subject->isConnected());
		$this->subject->connectDatabase();
		$this->assertTrue($this->subject->isConnected());
	}


	/**
	 * @test
	 *
	 * @return void
	 */
	public function connectDatabaseConnectsToDatabaseWithoutErrors() {
		$this->subject->close();
		$this->assertFalse($this->subject->isConnected());
		$this->subject->connectDatabase();
		$this->assertTrue($this->subject->isConnected());
	}
	/**
	 * @test
	 */
	public function connectDbConnectsDatabase() {
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
	public function connectDbConnectsToDatabaseWithoutErrors() {
		$this->subject->close();
		$this->assertFalse($this->subject->isConnected());
		$this->subject->connectDB();
		$this->assertTrue($this->subject->isConnected());
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

	/**********************
	 *
	 * Tests for adminQuery
	 *
	 **********************/

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForInsertQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->adminQuery('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForUpdateQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->adminQuery('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$id = $this->subject->getLastInsertId($this->testTable);
		$this->assertEquals(1, $id);
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->adminQuery('UPDATE ' . $this->testTable . ' SET fieldblob=\'bar\' WHERE id=' . $id));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForDeleteQuery() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->adminQuery('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$id = $this->subject->getLastInsertId($this->testTable);
		$this->assertEquals(1, $id);
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->adminQuery('DELETE FROM ' . $this->testTable . ' WHERE id=' . $id));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsResultForSelectQuery() {
		$this->markTestIncomplete('What does this test?');
		$this->assertInstanceOf('Doctrine\\DBAL\\Driver\\Statement', $this->subject->adminQuery('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$stmt = $this->subject->adminQuery('SELECT fieldblob FROM ' . $this->testTable);
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
		$this->assertTrue(is_numeric($this->subject->countTables()));
	}

	/**********************
	 *
	 * Tests for list methods
	 *
	 * - listDatabaseCharsets
	 * - listKeys
	 * - listFields
	 * - listTables
	 * - listDatabases
	 *
	 **********************/

	/**
	 * @test
	 *
	 * @return void
	 */
	public function listDatabaseCharsetsReturnsArrayWithCharsets() {
		$columnsRes = $this->subject->adminQuery('SHOW CHARACTER SET');
		$result = $this->subject->listDatabaseCharsets();
		$this->assertEquals(count($result), $columnsRes->rowCount());

		/** @var array $row */
		while (($row = $this->subject->fetchAssoc($columnsRes))) {
			$this->assertArrayHasKey($row['Charset'], $result);
		}
		$columnsRes->closeCursor();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function listKeysReturnIndexKeysOfTable() {
		$result = $this->subject->listKeys($this->testTable);
		$this->assertEquals('id', $result[0]['Column_name']);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function listFieldsReturnFieldInformationsForTable() {
		$result = $this->subject->listFields($this->testTable);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey($this->testField, $result);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function listTablesReturnAllTablesFromDatabase() {
		$result = $this->subject->listTables();
		$this->assertArrayHasKey('tt_content', $result);
		$this->assertArrayHasKey('pages', $result);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function listDatabasesReturnsAllDatabases() {
		$tempDatabasename = $this->subject->getDatabaseName();
		$databases = $this->subject->adminQuery('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA');
		$result = $this->subject->listDatabases();
		$this->assertSame(count($result), $databases->rowCount());

		$i = 0;
		while ($database = $databases->fetch(\PDO::FETCH_ASSOC)) {
			$this->assertSame($database['SCHEMA_NAME'], $result[$i]);
			$i++;
		}
		$this->subject->setDatabaseName($tempDatabasename);
	}

	/************************************
	 *
	 * Tests concerning getResultRowCount
	 *
	 ************************************/

	/**
	 * Data Provider for getResultRowCountReturnsCorrectAmountOfRows()
	 *
	 * @see getResultRowCountReturnsCorrectAmountOfRows()
	 *
	 * @return array
	 */
	public function getResultRowCountReturnsCorrectAmountOfRowsDataProvider() {
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
	 * @dataProvider getResultRowCountReturnsCorrectAmountOfRowsDataProvider
	 *
	 * @param string $sql
	 * @param string $expectedResult
	 *
	 * @return void
	 */
	public function getResultRowCountReturnsCorrectAmountOfRows($sql, $expectedResult) {
		$this->assertSame(1, $this->subject->executeInsertQuery($this->testTable, array($this->testField => 'foo')));
		$this->assertSame(1, $this->subject->executeInsertQuery($this->testTable, array($this->testField => 'bar')));
		$this->assertSame(1, $this->subject->executeInsertQuery($this->testTable, array($this->testField => 'baz')));

		$res = $this->subject->adminQuery($sql);
		$rowCount = $this->subject->getResultRowCount($res);

		$this->assertSame($expectedResult, $rowCount);
	}

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'test' in 'where clause'
	 */
	public function getResultRowCountReturnsFalse() {
		$res = $this->subject->adminQuery('SELECT * FROM ' . $this->testTable . ' WHERE test=\'baz\'');
		$numRows = $this->subject->getResultRowCount($res);
		$this->assertFalse($numRows);
	}

	/*****************************
	 *
	 * Tests concerning fetchAssoc
	 *
	 *****************************/

	/**
	 * Prepares the test table for the fetch* Tests
	 *
	 * @return void
	 */
	protected function prepareTableForFetchTests() {
		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->adminQuery('ALTER TABLE ' . $this->testTable . '
				ADD name mediumblob;
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->adminQuery('ALTER TABLE ' . $this->testTable . '
				ADD deleted int;
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->adminQuery('ALTER TABLE ' . $this->testTable . '
				ADD street varchar(100);
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->adminQuery('ALTER TABLE ' . $this->testTable . '
				ADD city varchar(50);
			')
		);

		$this->assertInstanceOf(
			'Doctrine\\DBAL\\Driver\\Statement',
			$this->subject->adminQuery('ALTER TABLE ' . $this->testTable . '
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

		$res = $this->subject->adminQuery('SELECT * FROM ' . $this->testTable);
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
		while ($row = $this->subject->fetchAssoc($res)) {
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

	/*****************************
	 *
	 * Tests concerning fetchRow
	 *
	 *****************************/

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFetchRowReturnsNumericArray() {
		$this->prepareTableForFetchTests();
		$res = $this->subject->adminQuery('SELECT * FROM ' . $this->testTable);
		$expectedResult = array(
					array('1', null, null, 'Mr. Smith', '0', 'Oakland Road', 'Los Angeles', 'USA'),
					array('2', null, null, 'Ms. Smith', '0', 'Oakland Road', 'Los Angeles', 'USA'),
					array('3', null, null, 'Alice im Wunderland', '0', 'Große Straße', 'Königreich der Herzen', 'Wunderland'),
					array('4', null, null, 'Agent Smith', '1', 'Unknown', 'Unknown', 'Matrix')
				);
		$i = 0;
		while ($row = $this->subject->fetchRow($res)) {
			$this->assertSame($expectedResult[$i], $row);
			$i++;
		}
	}

	/*****************************
	 *
	 * Tests concerning freeResult
	 *
	 *****************************/

	/**
	 * @test
	 *
	 * @return void
	 * @expectedException \Doctrine\DBAL\DBALException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'baz' in 'where clause'
	 */
	public function sqlFreeResultReturnsFalse() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'baz')));
		$res = $this->subject->adminQuery('SELECT * FROM test_t3lib_dbtest WHERE fieldblob=baz');
		$this->assertFalse($this->subject->freeResult($res));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFreeResultReturnsTrue() {
		$this->assertSame(1, $this->subject->getDatabaseHandle()->insert($this->testTable, array($this->testField => 'baz')));
		$res = $this->subject->adminQuery('SELECT * FROM test_t3lib_dbtest WHERE fieldblob=\'baz\'');
		$this->assertTrue($this->subject->freeResult($res));
	}

	/********************************************
	 *
	 * Write/Read tests for charsets and binaries
	 *
	 ********************************************/

	/**
	 * @test
	 *
	 * @return void
	 */
	public function storedFullAsciiRangeReturnsSameData() {
		$this->markTestIncomplete('Implemement exec_SELECTgetRows for Doctrine');
		$binaryString = '';
		for ($i = 0; $i < 256; $i++) {
			$binaryString .= chr($i);
		}
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => $binaryString));
		$id = $this->subject->getLastInsertId($this->testTable);
		$entry = $this->subject->exec_SELECTgetRows($this->testField, $this->testTable, 'id = ' . $id);
		$this->assertEquals($binaryString, $entry[0][$this->testField]);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function storedGzipCompressedDataReturnsSameData() {
		$this->markTestIncomplete('Implemement exec_SELECTgetRows for Doctrine');
		$testStringWithBinary = @gzcompress('sdfkljer4587');
		$this->subject->executeInsertQuery($this->testTable, array($this->testField => $testStringWithBinary));
		$id = $this->subject->getLastInsertId($this->testTable);
		$entry = $this->subject->exec_SELECTgetRows($this->testField, $this->testTable, 'id = ' . $id);
		$this->assertEquals($testStringWithBinary, $entry[0][$this->testField]);
	}

	/*****************************
	 *
	 * Tests concerning listQuery
	 *
	 *****************************/

	/**
	 * @test
	 *
	 * @return void
	 * @see http://forge.typo3.org/issues/23253
	 */
	public function listQueryWithIntegerCommaAsValue() {
		// Note: 44 = ord(',')
		$this->markTestIncomplete('Implemement listQuery for Doctrine');
		$this->assertEquals($this->subject->listQuery('dummy', 44, 'table'), $this->subject->listQuery('dummy', '44', 'table'));
	}

	/*******************************
	 *
	 * Tests concerning searchQuery
	 *
	 *******************************/

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
		$this->markTestIncomplete('Implemement searchQuery for Doctrine');
		$result = $this->subject->searchQuery($searchWords, $fields, $table, $constraint);
		$this->assertSame($expectedResult, $result);
	}

	/*************************************************
	 *
	 * Tests concerning escapeStringForLikeComparison
	 *
	 *************************************************/

	/**
	 * @test
	 *
	 * @return void
	 */
	public function escapeStringForLikeComparison() {
		$this->markTestIncomplete('Implemement escapeStrForLike for Doctrine');
		$this->assertEquals('foo\\_bar\\%', $this->subject->escapeStrForLike('foo_bar%', 'table'));
	}

	/******************************
	 *
	 * Tests concerning quoteColumn
	 *
	 ******************************/

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

	/******************************
	 *
	 * Tests concerning quoteTable
	 *
	 ******************************/

	/**
	 * @test
	 */
	public function quoteTable() {
		$this->assertEquals('`pages`', $this->subject->quoteTable('pages'));
	}

	/***********************************
	 *
	 * Tests concerning quoteIdentifier
	 *
	 ***********************************/

	/**
	 * @test
	 */
	public function quoteIdentifier(){
		$this->assertEquals('`pages`', $this->subject->quoteIdentifier('pages'));
	}
}