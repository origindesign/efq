<?php

namespace Drupal\efq\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;



/**
 * Tests the Drupal 8 efq service
 *
 * @group efq
 */
class TestEfqService extends UnitTestCase  {

    /**
     * @var \Drupal\test_example\TestExampleConversions
     */
    public $efqService;
    
    
     public function setUp() {
 
        
        parent::setUp(); 
        
        
        // Mock ConfigEntityStorage object, but methods will be mocked in the test class.
        $this->configStorage = $this->getMockBuilder('\Drupal\Core\Config\Entity\ConfigEntityStorage')->disableOriginalConstructor()->getMock();
 
        // Mock EntityManager service.
        $this->entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')->disableOriginalConstructor()->getMock();
        $this->entityManager->expects($this->any())->method('getStorage')->with('key')->willReturn($this->configStorage); 
        
        // Mock QueryFactory service
        $this->queryFactory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')->disableOriginalConstructor()->getMock();
        $this->queryFactory->expects($this->any())->method('execute')->willReturn([1 => 1]);
        
        // Mock efq service
        //$this->efqService = $this->getMockBuilder('\Drupal\efq\EfqQueryEntities')->getMock();
        
        // Create a dummy container.
        $this->container = new ContainerBuilder();
        $this->container->set('entity_type.manager', $this->entityManager);
        $this->container->set('entity.query', $this->queryFactory); 
        //$this->container->set('efq.query_entities', $this->efqService); 
        
        
        \Drupal::setContainer($this->container);
        
        
        //$this->efqService = \Drupal::service('efq.query_entities');
         
        $this->efqService = new \Drupal\efq\EfqQueryEntities( $this->container->get('entity_type.manager'),  $this->container->get('entity.query') ); 
         

        
    }

    
    /**
     * A simple test that tests our celsiusToFahrenheit() function.
     */
    public function testGetEntities() {
        
        $expectedResult = array(2,3); 
        $result = $this->efqService->getEntities('article', '', 'teaser');

        $this->assertEquals($expectedResult, $result);
        
        
        // $this->assertEquals(2,2);
        
        
    }
    
    
    
    
    
  
    
}
