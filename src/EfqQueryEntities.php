<?php
/**
 * @file Contains \Drupal\efq\EfqQueryEntities
 */

namespace Drupal\efq;

use Drupal\efq\QueryBuilder\QueryBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * A Service for querying entities.
 *
 */
class EfqQueryEntities {


  protected $entityTypeManager;
  protected $queryBuilder;



  /**
   * EfqQueryEntities constructor.
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param QueryBuilder $queryBuilder
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, QueryBuilder $queryBuilder) {
    $this->entityTypeManager  = $entityTypeManager;
    $this->queryBuilder = $queryBuilder;

  }



  /**
   * Get the entities requested by EntityQuery
   * @param string $bundle
   * @param string $view_mode
   * @param array $conditions
   * @param array $range
   * @param array $sortBy
   * @param bool $count
   * @param bool $random
   * @param string $entity_type
   * @return array
   */
  public function getEntities ( $bundle = 'article', $view_mode = 'teaser', $conditions = NULL, $range = NULL, $sortBy = NULL, $count = false, $random = false, $entity_type = 'node' ) {

    // Build the query
    $this->queryBuilder->build( $bundle, $conditions, $range, $sortBy, $random, $entity_type );

    if ($count){
      $render = $this->queryBuilder->count();
    }else{
      // Run the query and grab entities IDs out of it.
      $nids = $this->queryBuilder->apply();
      // Get proper rendered Entities based on IDs and view mode
      $render = $this->renderNodes( $nids, $view_mode, $entity_type );
    }

    return $render;

  }




  /**
   * Get the entities nids requested by EntityQuery
   * @param string $bundle
   * @param array $conditions
   * @param array $range
   * @param array $sortBy
   * @param bool $random
   * @param string $entity_type
   * @return array
   */
  public function getNidsOnly ( $bundle = 'article', $conditions = NULL, $range = NULL, $sortBy = NULL, $random = false, $entity_type = 'node' ) {

    // Build the query
    $this->queryBuilder->build( $bundle, $conditions, $range, $sortBy, $random, $entity_type );

    // Run the query and grab entities IDs out of it.
    $nids = $this->queryBuilder->apply();

    return $nids;
  }


  /**
   * @param $pageNo
   * @param $perPage
   * @param $bundle
   * @param string $view_mode
   * @param null $conditions
   * @param null $sortBy
   * @param null $category
   * @param string $entity_type
   * @return array
   */
  public function getEntitiesPaged($pageNo, $perPage, $bundle, $view_mode = 'teaser', $conditions = NULL, $sortBy = NULL, $category = NULL, $entity_type = 'node') {

    // Range based on pager value
    $range = array(
      "start" => ($pageNo * $perPage) - $perPage,
      "length" => $perPage
    );

    // Use the injected service to get the node list
    $nodesList = $this->getEntities( $bundle, $view_mode, $conditions, $range, $sortBy, false, $entity_type );

    // Get pager html
    $pager = $this->renderPager( $bundle, $conditions, $pageNo, $perPage, $category );

    // Return a render of all nodes suffixed with pager
    if ($nodesList){
      $nodesList['#suffix'] = $pager;
      return $nodesList;
    }

    // If nothing is returned, then return a no result message
    return [
      '#type' => 'markup',
      '#markup' => '<p class="no-results">'.t('Sorry, there are no results for your current selection.').'</p>',
    ];


  }



  /**
   * Return a list of nodes that are published.
   * @param $nids
   * @param $view_mode
   * @param $entity_type
   * @return string
   */
  protected function renderNodes ($nids, $view_mode, $entity_type = 'node') {

    if( !empty($nids) ){

      // Get the node storage object.
      $node_storage = $this->entityTypeManager->getStorage($entity_type);

      // Get the view builder object
      $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);

      // Use the loadMultiple function to load an array of node objects keyed by node ID.
      $nodes = $node_storage->loadMultiple($nids);

      // Prepare output for rendering
      $output = $view_builder->viewMultiple($nodes, $view_mode);

      return $output;

    }

    return false;

  }


  /** Build pager
   * @param $content_type
   * @param $conditions
   * @param $page
   * @param $perPage
   * @param $params
   * @param $type
   * @param $entity_type
   * @return string
   */
  public function renderPager($content_type, $conditions, $page, $perPage, $params, $type = 'default', $entity_type = 'node'){

    // Get count of nodes in query
    $nodes = $this->getEntities($content_type, 'teaser', $conditions, NULL, NULL, true, false, $entity_type);

    // Get total pages
    $total = ceil(intval($nodes)/$perPage);

    // Setup output of pager html
    $output = '';

    if($total > 1){

      // Setup new data strings
      $prevParams = $params;
      $nextParams = $params;
      $prevParams['paged'] = ($page-1).'-'.$perPage.'--'.$type;
      $nextParams['paged'] = ($page+1).'-'.$perPage.'--'.$type;

      // If restricted value passed
      if(strpos($type,'restricted-') !== false){
        $array = explode('-',$type);
        $restrictBy = $array[1];
        $restricted = true;
      }else{
        $restricted = false;
      }

      $output .= '<div class="pager ajax"><ul>';

      // Previous
      if($page != 1){
        $output .= "<li class='prev'><a href='#' data-params='".json_encode($prevParams)."' title='Previous'></a></li>";
      }

      if($type == 'simple'){

        $output .= '<li class="text">Page '.$page.' of '.$total.'</li>';

      }else{
        // Add ellipsis to restricted
        if($restricted){
          $offset = ($restrictBy-1)/2;
          if($page > $offset + 1){
            $firstParams = $params;
            $firstParams['paged'] = '1-'.$perPage.'--'.$type;
            $output .= "<li class='ellipsis first'><a href='#' class='first' data-params='".json_encode($firstParams)."' title='First'>...</a></li>";
          }
        }
        // Loop through and create page numbers
        for($i = 1; $i <= $total ; $i++){

          // Set up params string
          $iParams = $params;
          $iParams['paged'] = $i.'-'.$perPage.'--'.$type;
          // Active class
          $class = '';
          if($page == $i){
            $class = 'active';
          }
          // Output
          $html =  "<li class='".$class."'><a class='".$class."' href='#' data-params='".json_encode($iParams)."'>".$i."</a></li>";

          // Page Numbers
          if($restricted){
            if(( $i <= $total - $restrictBy  && $i < $page - $offset) || ($i > $restrictBy && $i > $page + $offset)){
              $output .= '';
            }else{
              $output .= $html;
            }
          }else{
            $output .= $html;
          }
        }
        // Add ellipsis to restricted
        if($restricted){
          if($page < $total - $offset){
            $lastParams = $params;
            $lastParams['paged'] = $total.'-'.$perPage.'--'.$type;
            $output .= "<li class='ellipsis last'><a href='#' class='last' data-params='".json_encode($lastParams)."' title='Last'>...</a></li>";
          }
        }
      }

      // Next
      if($page != $total){
        $output .= "<li class='next'><a href='#' data-params='".json_encode($nextParams)."' title='Next'></a></li>";
      }

      $output .= '</ul></div>';

    }

    return $output;

  }


}
