<?php

require_once(dirname(__FILE__) . '/../config.php');

/**
* @package FrameworkTest
*/
class RunnerTest extends PHPUnit_Framework_TestCase {
	
	public function setUp() {
		NimbleRecord::start_transaction();
		MigrationRunner::$dir = dirname(__FILE__) . '/test';
		MigrationRunner::create_migration_table();
		ob_start();
	}
	
	public function testMigrationTable() {
		$this->assertEquals('migrations', MigrationRunner::migration_table_name());
	}
	
	
	public function testMigrateUpTo() {
		MigrationRunner::up(123456788);
		$this->assertEquals(123456788, MigrationRunner::current_version());
	}
	
	public function testMigrate() {
			MigrationRunner::migrate();
			$this->assertEquals(MigrationRunner::current_version(), 123456789);
	}
	
	public function testMigrationTableExsists() {
		$test = MigrationRunner::migration_table_exists();
		$this->assertTrue($test);
	}
	
	public function testMigrateEmptyString() {
		MigrationRunner::migrate('');
		$this->assertEquals(MigrationRunner::current_version(), 123456789);
	}
	
	public function testMigrateZero() {
		MigrationRunner::migrate();
		$this->assertEquals(MigrationRunner::current_version(), 123456789);
	}
	
	public function testLoadFiles() {
		$data = MigrationRunner::load_files();
		$last = 0;
		foreach($data as $version => $class) {
			$this->assertTrue(is_numeric($version));
			//assert sorted
			$this->assertTrue($last < $version);
			$klass = new $class();
			$this->assertTrue(isset($klass));
			$last = $version;
		}
	}
	
	public function testUp() {
		MigrationRunner::up();
		$this->assertEquals(MigrationRunner::current_version(), 123456789);
	}
	
	
	public function testUpThenDown() {
		MigrationRunner::up(123456789);
		$this->assertEquals(MigrationRunner::current_version(), 123456789);
		MigrationRunner::down(123456788);
		$this->assertEquals(MigrationRunner::current_version(), 0);
	}
	
	public function testGetMaxVersion() {
		MigrationRunner::up(123456789);
		$data = MigrationRunner::load_files();
		$max = MigrationRunner::current_version();
		$this->assertEquals(123456789, $max);
	}
	
	public function tearDown() {
		MigrationRunner::drop_migration_table();
		NimbleRecord::end_transaction();
		ob_clean();
	}
	
}

?>