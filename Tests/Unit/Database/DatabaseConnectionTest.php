<?php
namespace TYPO3\DoctrineDbal\Tests\Unit\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Ernesto Baschny (ernst@cron-it.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for TYPO3\CMS\Core\Database\DatabaseConnection
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class DatabaseConnectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $subject = NULL;

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
	 * Set the test up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = $GLOBALS['TYPO3_DB'];
		$this->testTable = 'test_t3lib_dbtest';
		$this->testField = 'fieldblob';
		$this->testFieldSecond = 'fieldblub';
		$this->subject->sql_query('CREATE TABLE ' . $this->testTable . ' (
			id int(11) unsigned NOT NULL auto_increment,' .
			$this->testField . ' mediumblob,' .
			$this->testFieldSecond . ' mediumblob,
			PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}

	/**
	 * Tear the test down
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->subject->sql_query('DROP TABLE ' . $this->testTable . ';');
		unset($this->subject);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectDbReturnsTrue() {
		$this->assertTrue($this->subject->sql_select_db());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage TYPO3 Fatal Error: Cannot connect to the current database, "Foo"!
	 * @return void
	 */
	public function selectDbReturnsFalse() {
		$this->subject->setDatabaseName('Foo');
		$this->assertFalse($this->subject->sql_select_db());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlAffectedRowsReturnsCorrectAmountOfRows() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals(1, $this->subject->sql_affected_rows());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlInsertIdReturnsCorrectId() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals(1, $this->subject->sql_insert_id());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function noSqlError() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals('', $this->subject->sql_error());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlErrorWhenInsertIntoInexistentField() {
		$this->subject->exec_INSERTquery($this->testTable, array('test' => 'test'));
		$this->assertEquals('Unknown column \'test\' in \'field list\'', $this->subject->sql_error());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function noSqlErrorCode() {
		$this->subject->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$this->assertEquals(0, $this->subject->sql_errno());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlErrorNoWhenInsertIntoInexistentField() {
		$this->subject->exec_INSERTquery($this->testTable, array('test' => 'test'));
		$this->assertEquals(1054, $this->subject->sql_errno());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlPconnectReturnsInstanceOfMySqli() {
		$this->assertInstanceOf('mysqli', $this->subject->sql_pconnect());
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function connectDbConnectsToDatabaseWithoutErrors() {
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
			'NULL string with ReturnNull is allowed' => array(array(NULL,TRUE), 'NULL'),
			'NULL string with ReturnNull is false' => array(array(NULL,FALSE), '\'\''),
			'Normal string' => array(array('Foo',FALSE), '\'Foo\''),
			'Single quoted string' => array(array("'Hello'",FALSE), "'\\'Hello\\''"),
			'Double quoted string' => array(array('"Hello"',FALSE), '\'\\"Hello\\"\''),
			'String with internal single tick' => array(array('It\'s me',FALSE), '\'It\\\'s me\''),
			'Slashes' => array(array('/var/log/syslog.log',FALSE), '\'/var/log/syslog.log\''),
			'Backslashes' => array(array('\var\log\syslog.log',FALSE), '\'\\\var\\\log\\\syslog.log\''),
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
			'Double Quotes' => array('"Hello"', '\\"Hello\\"'),
			'single Quotes' => array('\'Hello\'', '\\\'Hello\\\''),
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
		$this->assertTrue($this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForUpdateQuery() {
		$this->assertTrue($this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$id = $this->subject->sql_insert_id();
		$this->assertTrue($this->subject->admin_query('UPDATE ' . $this->testTable . ' SET fieldblob=\'bar\' WHERE id=' . $id));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsTrueForDeleteQuery() {
		$this->assertTrue($this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$id = $this->subject->sql_insert_id();
		$this->assertTrue($this->subject->admin_query('DELETE FROM ' . $this->testTable . ' WHERE id=' . $id));
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminQueryReturnsResultForSelectQuery() {
		$this->assertTrue($this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (fieldblob) VALUES (\'foo\')'));
		$res = $this->subject->admin_query('SELECT fieldblob FROM ' . $this->testTable);
		$this->assertInstanceOf('mysqli_result', $res);
		$result = $res->fetch_assoc();
		$this->assertEquals('foo', $result[$this->testField]);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function adminGetCharsetsReturnsArrayWithCharsets() {
		$columnsRes = $this->subject->admin_query('SHOW CHARACTER SET');
		$result = $this->subject->admin_get_charsets();
		$this->assertEquals(count($result), $columnsRes->num_rows);

		/** @var array $row */
		while (($row = $columnsRes->fetch_assoc())) {
			$this->assertArrayHasKey($row['Charset'], $result);
		}
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
		$databases = $this->subject->admin_query('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA');
		$result = $this->subject->admin_get_dbs();
		$this->assertSame(count($result), $databases->num_rows);

		$i = 0;
		while ($database = $databases->fetch_assoc()) {
			$this->assertSame($database['SCHEMA_NAME'], $result[$i]);
			$i++;
		}
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
		$id = $this->subject->sql_insert_id();
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
		$id = $this->subject->sql_insert_id();
		$where = 'id=' . $id;
		$limit = '1,2';
		$queryExpected =
			'SELECT ' . $this->testField . ' FROM ' . $this->testTable . ' WHERE id=' . $id . ' LIMIT 1,2';
		$queryGenerated = $this->subject->SELECTquery($this->testField, $this->testTable, $where, '', '', $limit);
		$this->assertSame($queryExpected, $queryGenerated);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function selectSubQueryCreateValidQuery() {
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);
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
		$this->assertTrue(
			$this->subject->admin_query('INSERT INTO ' . $this->testTable . ' (' . $this->testField . ') VALUES (\'foo\')')
		);

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
	public function prepareSelectQueryCreateValidQuery() {
		$this->markTestIncomplete('Needs implemented');
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function prepareSelectQueryArrayCreateValidQuery() {
		$this->markTestIncomplete('Needs implemented');
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlNumRowsReturnsCorrectAmountOfRows() {
		$this->markTestIncomplete('Needs implemented');
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFetchAssocReturnsAssocArray() {
		$this->markTestIncomplete('Needs implemented');
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFetchRowReturnsNumericArray() {
		$this->markTestIncomplete('Needs implemented');
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFreeResultReturnsTrue() {
		$this->markTestIncomplete('Needs implemented');
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function sqlFreeResultReturnsFalse() {
		$this->markTestIncomplete('Needs implemented');
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
		$this->assertSame($expectedResult, $this->subject->searchQuery($searchWords, $fields, $table, $constraint));
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
			'normal WEHRE clause' => array('uid=123 GROUP BY title ORDER BY title LIMIT 5,2', array(
																								'WHERE'   => ' uid=123',
																								'GROUPBY' => 'title',
																								'ORDERBY' => 'title',
																								'LIMIT'   => '5,2'
																							)),
			'no WHERE in clause' => array('GROUP BY title ORDER BY title LIMIT 5,2', array(
																								'WHERE'   => '',
																								'GROUPBY' => 'title',
																								'ORDERBY' => 'title',
																								'LIMIT'   => '5,2'
																							)),
			'no GROUP BY in clause' => array('uid=123 ORDER BY title LIMIT 5,2', array(
																								'WHERE'   => ' uid=123',
																								'GROUPBY' => '',
																								'ORDERBY' => 'title',
																								'LIMIT'   => '5,2'
																							)),
			'no ORDER BY clause' => array('uid=123 GROUP BY title LIMIT 5,2', array(
																								'WHERE'   => ' uid=123',
																								'GROUPBY' => 'title',
																								'ORDERBY' => '',
																								'LIMIT'   => '5,2'
																							)),
			'no LIMIT clause' => array('uid=123 GROUP BY title ORDER BY title', array(
																								'WHERE'   => ' uid=123',
																								'GROUPBY' => 'title',
																								'ORDERBY' => 'title',
																								'LIMIT'   => ''
																							)),
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
}
