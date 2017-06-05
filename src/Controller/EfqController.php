<?php
 
/**
 * @file
 * Contains \Drupal\efq\Controller\EfqController.
 */
 
namespace Drupal\efq\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\efq\EfqQueryEntities;
 


class EfqController extends ControllerBase {
 
    /**
     * @var \Drupal\efq\EfqQueryEntities
     */
    protected $efqQueryEntities;
    
    
 
    /**
     * @param \Drupal\efq\EfqQueryEntities $EfqQueryEntities
     */
    public function __construct(EfqQueryEntities $EfqQueryEntities) {
        $this->efqQueryEntities = $EfqQueryEntities;
    }
 

    /**
     * When this controller is created, it will get the efq.queryEntities service and store it.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @return static
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('efq.query_entities')
        );
    }



    /**
     * Get Nodes based on content type and params
     *
     * @param string $content_type
     * @param string $view_mode
     * @param null $param
     * @param null $date
     * @return array
     */
    public function getNodes($content_type = 'article', $view_mode = 'teaser', $param = null, $date = null ) {


        // Only get Published Nodes
        $conditions = array(
            "status" => 1
        );

        // Default Sort
        $sort = NULL;

		// Default Range
		$range = array(
		  "start" => 0,
		  "length" => 1000
		);


        // Use the injected service to get the node list.
        $nodesList = $this->efqQueryEntities->getEntities( $content_type, $view_mode, $conditions, $range, $sort );


        // Return a render of all nodes
        if ($nodesList){
            return $nodesList;
        }


        // If nothing is returned, then return a no result message
        return [
            '#type' => 'markup',
            '#markup' => '<p class="no-results">Sorry, there are no results for your current selection.</p>',
        ];


    }



  
}
