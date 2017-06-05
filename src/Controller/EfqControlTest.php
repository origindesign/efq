<?php
 
/**
 * @file
 * Contains \Drupal\efq\Controller\EfqController.
 */
 
namespace Drupal\efq\Controller;
 
use Drupal\Core\Controller\ControllerBase;
 

class EfqControlTest extends ControllerBase {
 
  
    

    /**
     * Generates an example page.
     */
    public function demo() {
        return array(
            '#markup' => t('Hello Test!'),
        );
    }  
    
  
  
}
