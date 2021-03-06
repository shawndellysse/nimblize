<?php
	require_once(dirname(__FILE__) . '/test_config.php');
  /**
  * @package FrameworkTest
  */
	class AssociationsTest extends PHPUnit_Framework_TestCase {
	
		public function testBelongsTo() {
			$u = new User();
			$u->belongs_to('photo', 'cats', 'dogs');
			$ass = NimbleAssociation::$associations['User']['belongs_to'];
			foreach($ass as $obj) {
				$this->assertTrue(is_a($obj, 'NimbleAssociationBuilder'));
			}
		}
		
		public function testHasMany() {
			$u = new User();
			$u->has_many('dogs', 'deer', 'ducks');
			$vals = array('photos', 'dogs', 'deer', 'ducks');
			$ass = NimbleAssociation::$associations['User']['has_many'];
			foreach($ass as $obj) {
				$this->assertTrue(is_a($obj, 'NimbleAssociationBuilder'));
			}
		}
	
		public function testHasAndBelongsToMany() {
			$u = new User();
			$u->has_and_belongs_to_many('dogs', 'deer', 'ducks');
			$vals = array('dogs', 'deer', 'ducks');
			$ass = NimbleAssociation::$associations['User']['has_and_belongs_to_many'];
			foreach($ass as $obj) {
				$this->assertTrue(is_a($obj, 'NimbleAssociationBuilder'));
			}
		}
			
		public function testAssociationAutoLoad() {
			User::create(array('name' => 'bob', 'my_int' => 5));
			$u = User::find('first');
			$u->photos;
			$this->assertTrue(isset(NimbleAssociation::$associations['User']['has_many']));
			$this->assertTrue(isset(NimbleAssociation::$associations['User']['has_many']['photos']));
		}
		
		
		public function testForeignKeyfunction() {
			$key = NimbleAssociation::foreign_key('User');
			$this->assertEquals('user_id', $key);
		}
		
		public function testForeignKeyCustomSuffix() {
			$old = 'id';
			NimbleRecord::$foreign_key_suffix = 'foo';
			$key = NimbleAssociation::foreign_key('User');
			$this->assertEquals('user_foo', $key);
			NimbleRecord::$foreign_key_suffix = $old;
		}
		
		public function testBelongsToFindsUser() {
			$photo = Photo::find(1);
			$this->assertEquals(1, $photo->user->id);
		}
		
		public function testAssociationModel() {
			$name = NimbleAssociation::model('photos');
			$this->assertEquals('Photo', $name);
		}
		
		public function testAssociationLoadsData() {		
			$users = User::find_all(array('include' => 'photos'));
			foreach($users as $user) {
				foreach($user->photos as $photo) {
					$this->assertEquals($user->id, $photo->user_id);
				}
			}
		}
		
		public function testPolymorphicHasManyLoad() {
			$photo = Photo::find(1);
			$this->assertEquals(2, $photo->comments->length);
		}
		
		public function testPolymorphicBelongsToLoad() {
			$comment = Comment::find(1);
			$this->assertEquals(1, $comment->commentable->id);
		}
		
		public function testPolymorphicBelongsToLoadGeneric() {
			$comment = Comment::find(1);
			$p2 = $comment->commentable;
			$this->assertEquals(1, $p2->id);
			$this->assertTrue(is_a($p2, 'Photo'));
		}
		
		public function testFinder() {
			$u = User::find('first', array('conditions' => 'my_int = 3'));
			$this->assertEquals($u->my_int, '3');
		}
		
		public function testMakesCorrectJoinTableName() {
			$name = NimbleAssociation::generate_join_table_name(array('foo', 'bars'));
			$this->assertEquals('bar_foos', $name);
		}
		
		public function testUserHasClubs() {
			$user = User::find(1);
			$this->assertEquals(11, $user->clubs->length);
		}
		
		public function testClubHasUsers() {
			$club = Club::find(1);
			$this->assertEquals(10, $club->users->length);
		}
		
		public function testNewPhotoSavesThroughUser() {
			$user = User::find(1);
			$user->photos = array(array('title' => 'my_new_user'));
			$user->save();
			$this->assertTrue(Photo::exists('title', 'my_new_user'));
		}
		
		public function testNewClubOnUser() {
			$user = User::find(1);
			$user->clubs = array(array('name' => 'my_cool_club'));
			$user->save();
			User::reset_cache();
			$this->assertTrue($user->clubs->includes(Club::find_by_name('my_cool_club')));
		}
		
		public function testHasManyThrough() {
			$user = User::find(1);
			$this->assertEquals(22, $user->comments->length);
		}
		
		public function setUp() {
			NimbleRecord::start_transaction();
		}
		
		public function tearDown() {
			NimbleRecord::rollback_transaction();
		}
	}
?>