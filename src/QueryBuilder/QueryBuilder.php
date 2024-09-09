<?php

/**
 * @file
 * Contains \Drupal\efq\QueryBuilder\QueryBuilder
 */

namespace Drupal\efq\QueryBuilder;

use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * A Class to Build a Query that can be used by Efq Main Class
 */
class QueryBuilder {


  protected $entityTypeManager;
  protected $defaultCondition;
  protected $defaultRange;
  protected $defaultSort;
  protected $query;



  public function __construct( EntityTypeManagerInterface $entityTypeManager ) {

    // Store Service Query Factory
    $this->entityTypeManager  = $entityTypeManager;

    // Set default conditions
    $this->defaultCondition = array(
      "status" => 1
    );

    // Set default range
    $this->defaultRange = array(
      "start" => 0,
      "length" => 300
    );

    // Set default sort
    $this->defaultSort = array(
      "field" => 'title',
      "direction" => 'ASC'
    );

  }


  /**
   * Get the entities requested by EntityQuery
   * @param string $bundle (content type)
   * @param array $conditions (optional)
   * @param null $range
   * @param null $sortBy
   * @param bool $random
   * @param string $entity_type (optional, type of entity, ie: node, user, ... default is node)
   * @return array
   */
  public function build( $bundle, $conditions = NULL, $range = NULL, $sortBy = NULL, $random = false, $entity_type = 'node' ){

    // Use the factory to create a query object for node entities.
    $this->query = $this->entityTypeManager->getStorage($entity_type)->getQuery();

    // Add filter content_type or bundle
    if (isset($bundle) && $bundle != '') {
      // If taxonomy term
      if ($entity_type == 'taxonomy_term') {
        $this->applyCondition('vid', $bundle);
        // Else if Media
      } else if ($entity_type == 'media') {
        $this->applyCondition('bundle', $bundle);
        // Else node
      } else {
        if (is_array($bundle)) {
          $this->applyCondition('type', $bundle, 'IN');
        } else {
          $this->applyCondition('type', $bundle);
        }
      }
    }


    // If conditions is not empty, update default conditions, otherwise use default one set in constructor
    if ( is_null ( $conditions ) ){
      // Use default Conditions Provided in constructor
      $this->parseConditions( $this->defaultCondition );
    }else{
      // Use Conditions argument
      $this->parseConditions( $conditions );
    }


    // If random is true, apply random tag
    if($random){
      $this->query->addTag('random_order');
    }


    // If range is not empty, update default range, otherwise use default one set in constructor
    if ( is_null ( $range ) ){
      // Use default range Provided in constructor
      $this->applyRange( $this->defaultRange );
    }else{
      // Use range argument
      $this->applyRange( $range );
    }


    // If sortBy is not empty, update default sort, otherwise use default one set in constructor
    if (is_null($sortBy)) {
      // Use default sort Provided in constructor
      $this->applySort($this->defaultSort);
    } else {
      // Use Sort argument
      $this->applySort($sortBy);
    }

    return $this->query;

  }



  /**
   * Return a list of entities ID based on the query passed in argument
   * @return array
   */
  public function apply( ){

    //dpm( $this->query );
    $this->query->accessCheck(TRUE);
    return $this->query->execute();

  }



  /**
   * Return the number of entities based on the query passed in argument
   * @return array
   */
  public function count( ){

    $this->query->accessCheck(TRUE);
    return $this->query->count()->execute();

  }





  /**
   * Parse the array with all conditions and add to current query
   * @param $conditions
   */
  protected function parseConditions ( $conditions ){

    foreach ( $conditions as $key => $value ) {

      // If there is grouping elements then apply conditions with group (AND or OR)
      if ($key == "group" || $key == "group2"){

        $this->applyConditionsGroup ( $value );

      }else{
        // If no grouping, it means we pass a single field, so we apply condition directly without grouping

        if (is_array($value)) {

          // If value is an array, then, let's get the data and the extra (sometimes array of taxo or operator) out of it
          $data = $value[0];
          $operator = $value[1];

        } else {

          // If value is not an array, then there is no operator
          $data = $value;
          $operator = NULL;

        }

        $this->applyCondition ( $key, $data, $operator );

      }



    }

  }


  /**
   * Add conditions to the current query
   * @param string $key
   * @param string $value
   * @param string $operator
   */
  protected function applyCondition ( $key, $value, $operator = NULL ){

    if( !is_null ( $operator ) ){

      $this->query->condition($key, $value, $operator);

    }else{

      $this->query->condition($key, $value);

    }

  }




  /**
   * Add range to the current query
   * @param array $array
   */
  protected function applyRange ( $array ){

    if( array_key_exists('start', $array) && array_key_exists('length', $array) ){

      $this->query->range($array['start'], $array['length']);

    }

  }



  /**
   * Add sort to the current query
   * @param array $array
   */
  protected function applySort ( $array ){

    if(isset($array[0]) && is_array($array[0])){

      foreach($array as $sort){

        if( array_key_exists('field', $sort)  && array_key_exists('direction', $sort) ){

          $this->query->sort($sort['field'], $sort['direction']);

        }

      }

    }else{

      if( array_key_exists('field', $array)  && array_key_exists('direction', $array) ){

        $this->query->sort($array['field'], $array['direction']);

      }

    }

  }




  /**
   * Add conditions in groups to the current query
   * @param array $conditions
   */
  protected function applyConditionsGroup ( $conditions ){

    $group = ( $conditions['andor'] == "OR" ) ? $this->query->orConditionGroup() : $this->query->andConditionGroup();

    foreach ( $conditions["grouping"] as $mainKey => $condition ){

      if ( isset($condition['andor'] )) {

        $subgroup = ( $condition['andor'] == "OR" ) ? $this->query->orConditionGroup() : $this->query->andConditionGroup();

        foreach ( $condition["grouping"] as $key => $value ){

          if (is_array($value)) {

            $data = $value[0];
            $operator = $value[1];
            $subgroup->condition($key, $data, $operator);

          }else{

            $subgroup->condition($key, $value);

          }

        }

      }else{

        $subgroup = $this->query->andConditionGroup();

        if (is_array($condition)) {

          $data = $condition[0];
          $operator = $condition[1];
          $subgroup->condition($mainKey, $data, $operator);


        }else{

          $subgroup->condition($mainKey, $condition);

        }

      }

      $group->condition($subgroup);

    }

    $this->query->condition($group);

  }





}
