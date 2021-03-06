<?php
/**
 * Creates and executes the Association Finders
 * @package NimbleRecord
 */
class NimbleAssociation {
  public static $types = array('has_many', 'has_one', 'belongs_to', 'has_and_belongs_to_many');
  public static $associations = array();
  const INNER_JOIN_SQL = "INNER JOIN {join_table_name} ON ({from_table_foreign_key} = {join_table_primary_key})";
  const OUTER_JOIN_SQL = "LEFT OUTER JOIN {join_table_name} ON ({from_table_foreign_key} = {join_table_primary_key})";
  /**
   * Verifies association and finder exsist then executes them
   * @param string $method
   * @param array $args
   * @uses NimbelAssociation::find_has_many($class, $association);
   */
  public static function __callStatic($method, $args) {
    $matches = array();
    $regex = '/^find_(' . implode('|', static ::$types) . ')$/';
    if (preg_match($regex, $method, $matches)) {
      return call_user_func_array(array('self', '_' . $matches[1]), array($args[0], $args[1]));
    } else {
      throw new NimbleRecordException('Association type does not exist');
    }
  }
  /**
   * Has many executing logic is in this function
   * Determines which type of has_many wither its polymorphic or standard and executes the correct one
   * @param NimbleRecord $class - instance of the called class
   * @param string $name - association name
   */
  private static function _has_many($class, $name) {
    $obj = static ::get_association_object($class, $name, NimbleAssociationBuilder::HAS_MANY);
    $options = (array)$obj;
    if (isset($obj->as) && !empty($obj->as)) {
      return static ::has_many_polymorphic_find($class, $name, $options);
    }
    if (isset($obj->through) && !empty($obj->through)) {
      return static ::has_many_through_find($class, $name, $options);
    }
    return static ::has_many_find($class, $name, $options);
  }
  /**
   * Has one executing logic is in this function
   * @param NimbleRecord $class - instance of the called class
   * @param string $name - association name
   */
  private static function _has_one($class, $name) {
    $options = (array)static ::get_association_object($class, $name, NimbleAssociationBuilder::HAS_ONE);
    return static ::has_one_find($class, $name, $options);
  }
  /**
   * Belongs to executing logic is in this function
   * Determines which type of belongs_to wither its polymorphic or standard and executes the correct one
   * @param NimbleRecord $class - instance of the called class
   * @param string $name - association name
   */
  private static function _belongs_to($class, $name) {
    $obj = static ::get_association_object($class, $name, NimbleAssociationBuilder::BELONGS_TO);
    $options = (array)$obj;
    if (isset($obj->polymorphic) && $obj->polymorphic === true) {
      return static ::belongs_to_polymorphic_find($class, $name, $options);
    }
    return static ::belongs_to_find($class, $name, $options);
  }
  /**
   * Has one executing logic is in this function
   * @param NimbleRecord $class - instance of the called class
   * @param string $name - association name
   */
  private static function _has_and_belongs_to_many($class, $name) {
    $obj = static ::get_association_object($class, $name, NimbleAssociationBuilder::HAS_AND_BELONGS_TO_MANY);
    $options = (array)$obj;
    return static ::has_and_belongs_to_many_find($class, $name, $options);
  }
  /**
   * Checks to make sure an association exists
   * @return boolean
   * @param string $class
   * @param string $type
   * @param string $association_name
   */
  public static function exists($class, $type, $association_name) {
    $class = static ::class_as_string($class);
    if (!isset(static ::$associations[$class])) {
      return false;
    }
    $associations = static ::$associations[$class];
    if (isset($associations[$type])) {
      return isset($associations[$type][$association_name]);
    } else {
      return false;
    }
  }
  /**
   * Finds the association type given the name and class
   * @param mixed $class
   * @param string $association
   * @return mixed
   */
  public static function find_type($class, $association) {
    $class = static ::class_as_string($class);
    $cache = Cache::get_cache();
    $cache_key = 'find_type-' . $class . '-' . $association;
    if ($cache->exists($cache_key)) {
      return $cache->get($cache_key);
    }
    if (!isset(static ::$associations[$class])) {
      return false;
    }
    foreach(static ::$associations[$class] as $assoc => $assocs) {
      if (isset(static ::$associations[$class][$assoc][$association])) {
        $cache->set($cache_key, (string)$assoc);
        return (string)$assoc;
      }
    }
    return false;
  }
  /**
   * Performs a find for a has_one relationship
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function has_one_find() {
    $foreign_key = is_null($options['foreign_key']) ? static ::foreign_key($class) : $options['foreign_key'];
    $class_name = is_null($options['class_name']) ? static ::model($name) : $options['class_name'];
    $id = $class->row[NimbleRecord::$primary_key_field];
    $conditions = "$foreign_key = '$id'";
    $options['conditions'] = is_null($options['conditions']) ? $conditions : implode(' AND ', array($conditions, $options['conditions']));
    return call_user_func_array(array($class_name, 'find'), array('first', $options));
  }
  /**
   * Performs a find for a has_many relationship
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function has_many_find($class, $name, $options = array()) {
    $foreign_key = is_null($options['foreign_key']) ? static ::foreign_key($class) : $options['foreign_key'];
    $class_name = is_null($options['class_name']) ? static ::model($name) : $options['class_name'];
    $id = $class->row[NimbleRecord::$primary_key_field];
    $conditions = NimbleQuery::condition($foreign_key, $id);
    if (strpos($id, 'IN') !== false) {
      $conditions = $polymorphic_column_id . $id;
    }
    $options['conditions'] = is_null($options['conditions']) ? $conditions : implode(' AND ', array($conditions, $options['conditions']));
    return call_user_func(array($class_name, 'find_all'), $options);
  }
  /**
   * Performs a find for has_and_belongs_to_many
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function has_and_belongs_to_many_find($class, $name, $options = array()) {
    $class_name = static ::class_as_string($class);
    $model = static ::class_as_string($name);
    $join_table = is_null($options['join_table']) ? static ::generate_join_table_name(array(strtolower($class_name), strtolower($name))) : $options['join_table'];
    $assoc = Inflector::underscore(Inflector::pluralize($class_name));
    $fk1 = is_null($options['foreign_key']) ? static ::foreign_key($class) : $options['foreign_key'];
    $fk2 = is_null($options['association_foreign_key']) ? static ::foreign_key($model) : $options['association_foreign_key'];
    $join_options['{join_table_name}'] = $join_table;
    $join_options['{join_table_primary_key}'] = $join_table . '.' . $fk2;
    $join_options['{from_table_foreign_key}'] = NimbleRecord::table_name($model) . '.' . $model::$primary_key_field;
    $options['joins'] = str_replace(array_keys($join_options), array_values($join_options), self::INNER_JOIN_SQL);
    $id = $class->row[NimbleRecord::$primary_key_field];
    $conditions = NimbleQuery::condition($join_table . '.' . $fk1, $id);
    $options['conditions'] = is_null($options['conditions']) ? $conditions : implode(' AND ', array($conditions, $options['conditions']));
    $options['select'] = "$name.*";
    return call_user_func_array(array($model, 'find_all'), array($options));
  }
  /**
   * Performs a find for has_many->through
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function has_many_through_find($class, $name, $options = array()) {
    $source_class = static ::class_as_string($options['through']);
    $type = static ::find_type($source_class, $name);
    $fk = static ::foreign_key($class);
    $finder_class = static ::class_as_string($name);
    $_class = static ::class_as_string($class);
    $assoc_class = call_user_func(array($source_class, 'find_all'), array('select' => $source_class::$primary_key_field, 'conditions' => array($fk => $class->row[$_class::$primary_key_field])));
    $id_col = $source_class::$primary_key_field;
    $ids = collect(function ($a) use($id_col) {
      return $a->row[$id_col];
    }, $assoc_class);
    $_source_class = new $source_class;
    $_source_class->row[$source_class::$primary_key_field] = ' IN(' . implode(',', $ids) . ')';
    return call_user_func_array(array('self', '_' . $type), array($_source_class, $name, $options));
  }
  /**
   * Performs a find for has_many->as (polymorphic)
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function has_many_polymorphic_find($class, $name, $options = array()) {
    $id = $class->row[NimbleRecord::$primary_key_field];
    $model = static ::model($name);
    $polymorphic_column_type = $options['as'] . '_type';
    $polymorphic_column_id = $options['as'] . '_id';
    $class = strtolower(static ::class_as_string($class));
    $conditions = implode(' AND ', array(NimbleQuery::condition($polymorphic_column_type, $class), NimbleQuery::condition($polymorphic_column_id, $id)));
    if (strpos($id, 'IN') !== false) {
      $conditions = implode(' AND ', array(NimbleQuery::condition($polymorphic_column_type, $class), $polymorphic_column_id . $id));
    }
    $merg_conditions = array($conditions);
    if (!is_null($options['conditions'])) {
      $merg_conditions[] = $options['conditions'];
    }
    $options['conditions'] = implode(' AND ', $merg_conditions);
    return call_user_func("$model::find_all", $options);
  }
  /**
   * Performs a find for belongs_to->polymorphic
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function belongs_to_polymorphic_find($class, $name, $options = array()) {
    $singular = Inflector::singularize(get_class($class));
    $polymorphic_column_type = $name . '_type';
    $model = static ::model($class->row[$polymorphic_column_type]);
    $polymorphic_column_id = $name . '_id';
    $id = $class->row[$polymorphic_column_id];
    return call_user_func_array(array($model, 'find'), array($id));
  }
  /**
   * Performs a find for belongs_to
   * @param NimbleRecord $class
   * @param string $name - association name
   * @param array $options
   */
  protected static function belongs_to_find($class, $name, $options = array()) {
    $class_name = is_null($options['class_name']) ? static ::model($name) : $options['class_name'];
    $foreign_key = is_null($options['foreign_key']) ? static ::foreign_key($name) : $options['foreign_key'];
    return call_user_func_array(array($class_name, 'find'), array($class->row[$foreign_key], $options));
  }
  /**
   * builds a join based given class and associations
   * @param NimbleRecord $class
   * @param mixed $assoc
   * @return string
   */
  public static function process_join($class, $assoc) {
    $class = static ::class_as_string($class);
    $out = array();
    if (is_string($assoc) && static ::find_type($class, $assoc) === false) {
      return $assoc;
    } else {
      $assoc = is_array($assoc) ? $assoc : array($assoc);
    }
    if (is_array($assoc) && !is_assoc($assoc)) {
      foreach($assoc as $association) {
        $out[] = static ::build_join($class, $association);
      }
    } else {
      /** @todo add nested builder for joins */
    }
    return implode(" ", $out);
  }
  private static function build_join($model, $association, $sql = self::INNER_JOIN_SQL) {
    $type = static ::find_type($model, $association);
    if ($type === false) {
      throw new NimbleRecordException('Invalid association: ' . $association);
    }
    $association_model = Inflector::classify(Inflector::singularize($association));
    $options = array();
    $options['{join_table_name}'] = NimbleRecord::table_name($association_model);
    switch ($type) {
      case 'belongs_to':
        $options['{join_table_primary_key}'] = NimbleRecord::table_name($association_model) . '.' . $model::$foreign_key_suffix;
        $options['{from_table_foreign_key}'] = NimbleRecord::table_name($model) . '.' . static ::foreign_key($association);
      break;
      case 'has_many':
        $options['{join_table_primary_key}'] = NimbleRecord::table_name($association_model) . '.' . static ::foreign_key($model);
        $options['{from_table_foreign_key}'] = NimbleRecord::table_name($model) . '.' . $model::$foreign_key_suffix;
      break;
    }
    return str_replace(array_keys($options), array_values($options), $sql);
  }
  //helper methods
  
  /**
   * Generates a table name for the has_and_belongs_to_many relationships
   * @param array $array - array of model names
   */
  public static function generate_join_table_name($array) {
    sort($array);
    return NimbleRecord::$table_name_prefix . Inflector::singularize(reset($array)) . '_' . Inflector::pluralize(end($array));
  }
  /**
   * Returns the class name of a given class
   * @param string|class $class
   */
  public static function class_as_string($class) {
    return Inflector::classify(Inflector::singularize(is_string($class) ? $class : get_class($class)));
  }
  public static function get_association_object($class, $name, $type) {
    return NimbleAssociation::$associations[static ::class_as_string($class) ][$type][$name];
  }
  public static function foreign_key($class) {
    $class = static ::class_as_string($class);
    $model = Inflector::classify(Inflector::singularize($class));
    return Inflector::foreignKey($model, $model::$foreign_key_suffix);
  }
  /**
   * Returns the table name for a model
   * @param string $name - Model name
   * @return string
   */
  public static function table_name($name) {
    return NimbleRecord::table_name(static ::model($name));
  }
  /**
   * Return model name
   * @param string $name
   * @return string
   */
  public static function model($name) {
    return Inflector::classify($name);
  }
}
/**
 * Builds association object
 * @package NimbleRecord
 */
class NimbleAssociationBuilder {
  const HAS_MANY = 'has_many';
  const BELONGS_TO = 'belongs_to';
  const HAS_AND_BELONGS_TO_MANY = 'has_and_belongs_to_many';
  const HAS_ONE = 'has_one';
  static $options = array(
                          self::HAS_MANY => array('through', 'foreign_key', 'class_name', 'conditions', 'order', 'include', 'as', 'dependent'), 
                          self::BELONGS_TO => array('class_name', 'conditions', 'foreign_key', 'include', 'polymorphic'), 
                          self::HAS_AND_BELONGS_TO_MANY => array('class_name', 'join_table', 'foreign_key', 
                                                                 'association_foreign_key', 'conditions', 'order'), 
                          self::HAS_ONE => array('class_name', 'conditions', 'foreign_key', 'include', 'as', 'through'));
  public function __construct($class, $type, $arg) {
    if (!array_include($type, NimbleAssociation::$types)) {
      throw new NimbleRecordException('Invalid Association Type: ' . $type);
    }
    $this->type = $type;
    $this->class = NimbleAssociation::class_as_string($class);
    $this->name = $arg;
    foreach(static ::$options[$this->type] as $var) {
      $this->{$var} = NULL;
    }
    return $this;
  }
  public function __call($method, $args) {
    if (array_include($method, static ::$options[$this->type])) {
      $this->{$method} = reset($args);
      return $this;
    } else {
      throw new NimbleRecordException('Property does not exist on this association type only: ' . implode(', ', static ::$options[$this->type]));
    }
  }
}
