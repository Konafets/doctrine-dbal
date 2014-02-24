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
use TYPO3\DoctrineDbal\Persistence\Legacy\DatabaseConnection;

/**
 * Class DatabaseConnectionTest
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class DatabaseConnectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var DatabaseConnection
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
		$this->subject = GeneralUtility::makeInstance('TYPO3\\DoctrineDbal\\Persistence\\Legacy\\DatabaseConnection');
		$this->subject->setDatabaseName(TYPO3_db);
		$this->subject->setDatabaseUsername(TYPO3_db_username);
		$this->subject->setDatabasePassword(TYPO3_db_password);
		$this->subject->setDatabasePort($GLOBALS['TYPO3_DB']->getDatabasePort());
		$this->subject->setDatabaseHost($GLOBALS['TYPO3_DB']->getDatabaseHost());
		$this->subject->connectDB();

		$this->testTable       = 'test_t3lib_dbtest';
		$this->testField       = 'fieldblob';
		$this->testFieldSecond = 'fieldblub';

		$table = $this->subject->createTable($this->testTable);
		$table->addColumn('id', 'integer', array('unsigned' => TRUE));
		$table->addColumn($this->testField, 'blob');
		$table->addColumn($this->testFieldSecond, 'integer');
		$table->setPrimaryKey(array('id'));
	}

	/**
	 * Tear the test down
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->subject->dropTable($this->testTable);
		$this->subject->close();
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function getNameReturnsTheNameOfTheCurrentDatabaseSystem() {
		$this->markTestIncomplete();

		$this->assertEquals('pdo_mysql', $this->subject->getName());
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
	public function getDatabaseReturnsDefaultValueLocalHost() {
		$this->assertEquals('127.0.0.1', $this->subject->getDatabaseHost());
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
	public function setAndGetLastStatement() {
		$this->markTestIncomplete();
		// Keep the last statement in mind
		$statement = $this->subject->getLastStatement();

		$this->subject->setLastStatement('Foo');
		$this->assertEquals('Foo', $this->subject->getLastStatement());

		$this->subject->setLastStatement($statement);
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
	public function createTruncateQueryReturnsTruncateQueryObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\TruncateQueryInterface',
				$this->subject->createTruncateQuery()
		);
	}

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
	public function createInsertQueryReturnsInsertQueryObject() {
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\InsertQueryInterface',
				$this->subject->createInsertQuery()
		);
	}

	/**
	 * @test
	 */
	public function createSelectQueryReturnsSelectQueryObject() {
		$this->markTestIncomplete();
		$this->assertInstanceOf(
				'\TYPO3\DoctrineDbal\Persistence\Database\SelectQueryInterface',
				$this->subject->createSelectQuery()
		);
	}

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
	 * Tests for legacy methods
	 */

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlPconnectReturnsCorrectInstance() {
		$this->assertInstanceOf('Doctrine\\DBAL\\Connection', $this->subject->sql_pconnect());
	}

	/**
	 * @test
	 */
	public function connectDBConnectsDatabase() {
		$this->subject->disconnectIfConnected();
		$this->assertFalse($this->subject->isConnected());
		$this->subject->connectDB();
//		$this->assertTrue($this->subject->isConnected());
	}
}