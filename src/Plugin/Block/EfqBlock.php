<?php

namespace Drupal\efq\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\efq\EfqQueryEntities;



/**
 * Provides a Efq block.
 *
 * @Block(
 *   id = "efq_block",
 *   admin_label = @Translation("Efq List Block"),
 * )
 */

class EfqBlock extends BlockBase implements ContainerFactoryPluginInterface {
  
    
    
    /**
     * @var \Drupal\efq\EfqQueryEntities
     */
    protected $efqQueryEntities;
    
    
    
    /**
     * @param array $configuration
     * @param string $plugin_id
     * @param mixed $plugin_definition
     * @param \Drupal\efq\EfqQueryEntities $EfqQueryEntities
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EfqQueryEntities $EfqQueryEntities) {
        
        // Call parent construct method.
        parent::__construct($configuration, $plugin_id, $plugin_definition);

        // Store our dependency.
        $this->efqQueryEntities = $EfqQueryEntities;
        
    }

    
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param array $configuration
     * @param string $plugin_id
     * @param mixed $plugin_definition
     * @return static
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('efq.query_entities')
        );
        
    }

    
    

    
    /**
     * {@inheritdoc}
     */
    public function build() {
        
        // Get Configuration of the block
        $config = $this->getConfiguration();
        
        // Get View mode out of block configuration // default to Teaser
        $view_mode = ( isset($config['view_mode']) ) ? $config['view_mode'] : 'teaser';

        // Get entities List if content type is set
        if( isset($config['content_type']) ){

            // Get only pusblished node
            $conditions = array( "status" => 1 );

            // We use the injected service to get the message.
            $entitiesList = $this->efqQueryEntities->getEntities($config['content_type'], $view_mode, $conditions);

            // We return a render of all nodes
            return $entitiesList;

            
        }else{
            
            return [
                '#type' => 'markup',
                '#markup' => '<p>' . $this->t("This Block is empty") . '</p>',
            ]; 
                        
        }
        

    } 

  
  
}
