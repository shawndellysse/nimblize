<?php
	
	require_once(dirname(__FILE__) . '/config.php');
	
	
	class TestMigration extends Migration {
		public $tables = array("users", "photos");

		public function up() {
				$table = $this->create_table('users');
					$table->string('name');
					$table->integer('my_int');
					$table->timestamps();
				$table->go();

				$table2 = $this->create_table('photos');
					$table2->belongs_to('user');
					$table2->string('title');
				$table2->go();
			}

			public function down() {
				foreach($this->tables as $t) {
					$this->drop_table($t);
				}
			}



	}


	function reload_database_tables() {
		$m = new Migration();
		$m->drop_database(MYSQL_DATABASE);
		$m->create_database(MYSQL_DATABASE);
	}

	//load the test database
	if(!defined('DATABASE_CREATED')) {
		reload_database_tables();
		define('DATABASE_CREATED', true);
	}

	function run_migration() {
		$g = new TestMigration();
		$g->down();
		$g->up();
	}

	//load the table if it hasn't been loaded

	if(!defined('TABLE_CREATED')) {
		run_migration();
		define('TABLE_CREATED', true);
	}


	function create_users() {
		foreach(range(0,10) as $i) {
			User::create(array('name' => 'names' . $i, 'my_int' => $i));
		}
	}

	function clear_users() {
		Photo::truncate();
		User::truncate();
	}

	function fill_user_photos() {
		$users = User::find_all();
		foreach($users as $user) {
			create_user_photos($user->id);
		}
	}

	function create_user_photos($user_id) {
		foreach(range(0, 10) as $i) {
			Photo::create(array('user_id' => $user_id, 'title' => 'title' . $i));
		}
	}

	function refresh_user_data() {
		clear_users();
		create_users();
		fill_user_photos();
	}

?>