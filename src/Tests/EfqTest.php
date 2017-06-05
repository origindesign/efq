<?php

namespace Drupal\efq\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal 8 efq module functionality
 *
 * @group efq
 */
class EfqTest extends WebTestBase {

    /**
     * Modules to install.
     *
     * @var array
     */
    public static $modules = array('node', 'efq');
    
    
    
    
    
    /**
     * Tests that the 'efqtest' path returns the right content
     */
    public function testCustomPageExists() {
        
        //$this->drupalLogin($this->user);

        $this->drupalGet('efqtest');
        $this->assertResponse(200);

        $this->assertText(t('Test'), 'Correct message is shown.');
        
    }
    
    
    
    
    /**
     * Tests that the 'efqtest' path returns the right content
     */
    public function testRenderNodes() {
        


        $efq_service = \Drupal::service('efq.query_entities');
        
        $nodeTestArray = array(2,3); 
        $result = $efq_service->renderNodes($nodeTestArray, 'teaser');
        
        

        $this->assertNotNull($result, 'A non-null result was returned when trying to load a valid object.');
        
        $this->assertText(sprintf('Fusce pharetra!', $result), 'Text Fusce is properly grabbed');

        
    }
    
  
    
}
