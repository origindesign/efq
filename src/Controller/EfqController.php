<?php

/**
 * @file
 * Contains \Drupal\efq\Controller\EfqController.
 */

namespace Drupal\efq\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\efq\EfqQueryEntities;



class EfqController extends ControllerBase {

    /**
     * @var \Drupal\efq\EfqQueryEntities
     */
    protected $efqQueryEntities;


    protected $dateFormat = 'd-m-Y';
    protected $dateDelimiter = '--';



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
     * @param Request $request
     * @return array
     */
    public function getNodesPost(Request $request){

        // Default Parameters
        $entity_type = 'node';
        $content_type = 'page';
        $conditions = array(
            "status" => 1
        );
        $view_mode = 'teaser';
        $sort = NULL;
        $range = array(
            "start" => 0,
            "length" => 1000
        );
        $paged = false;

        // Post Params
        $params = $request->request->all();

        // var_dump($request->request->all());

        // If params are set
        if($params != null){

            // Switch based on key of variable types
            foreach($params as $key => $value){

                switch($key){

                    // Format entity_type:node
                    case 'entity_type':
                        $entity_type = $value;
                        break;

                    // Format content_type:page
                    case 'content_type':
                        $content_type = $value;
                        break;

                    // Format view_mode:teaser
                    case 'view_mode':
                        $view_mode = $value;
                        break;

                    // Format sticky:0
                    case 'sticky':
                        $conditions['sticky'] = $value;
                        break;

                    // Format sort:field_name-DESC,field_name-ASC
                    case 'sort':
                        $sort = $this->parseSort($value);
                        break;

                    // Format range:start-end
                    case 'range':
                        $values = $this->parseBasicValues($value);
                        $range = array(
                            "start" => $values[0],
                            "length" => $values[1]
                        );
                        break;

                    // Format paged:pageNo-perPage
                    case 'paged':
                        $paged = true;
                        $values = $this->parseBasicValues($value);
                        $pageNo = $values[0];
                        $perPage = $values[1];
                        break;

                    // Format category:field_name--10-11
                    case 'category':
                        // Only parse if value does not contain all
                        if(strpos($value,'all') === false){
                            $conditions['group'] = $this->parseCategory($value);
                        }
                        break;

                    // Format date:field_name--d-m-Y,d-m-Y
                    case 'date':
                        $conditions['group2'] = $this->parseDate($value);
                        break;

                    // Format byMonth:field_name--d-m-Y
                    case 'byMonth':
                        $conditions['group2'] = $this->parseByMonth($value);
                        break;

                }

            }

        }



        // Use the injected service to get the node list.
        if($paged == false){

            $nodesList = $this->efqQueryEntities->getEntities( $content_type, $view_mode, $conditions, $range, $sort );

            // Return a render of all nodes
            if ($nodesList){
                return $nodesList;
            }

        }else{

            // Range based on pager value
            $range = array(
                "start" => ($pageNo * $perPage) - $perPage,
                "length" => $perPage
            );

            // Use the injected service to get the node list
            $nodesList = $this->efqQueryEntities->getEntities( $content_type, $view_mode, $conditions, $range, $sort, false, $entity_type );

            // Get pager html
            $pager = $this->efqQueryEntities->renderPager( $content_type, $conditions, $pageNo, $perPage, $params );

            // Return a render of all nodes suffixed with pager
            if ($nodesList){
                $nodesList['#suffix'] = $pager;
                return $nodesList;
            }

        }


        // If nothing is returned, then return a no result message
        return [
            '#type' => 'markup',
            '#markup' => '<p class="no-results">Sorry, there are no results for your current selection.</p>',
        ];

    }



    /**
     * Get Nodes based on content type and params
     *
     * @param string $content_type
     * @param string $view_mode
     * @param null $category format field_name--tid-tid
     * @param null $date
     * @return array
     */
    public function getNodes($content_type = 'article', $view_mode = 'teaser', $category = null, $date = null ) {


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


        // If there is a date in argument, then add conditions to get if a date is included in a given month (used for calendar)
        if ( $date ){

            $dateArray = explode($this->dateDelimiter,$date);

            if ( $this->verifyDate($dateArray[0]) && $this->verifyDate($dateArray[1]) ){

                $startDate = $this->formatDateFilter($dateArray[0], 'start');
                $endDate = $this->formatDateFilter($dateArray[1], 'end');

                $conditions['group'] =array(
                    "andor" => "OR",
                    'grouping' => array(
                        array(
                            "andor" => "AND",
                            "grouping" => array(
                                "field_date.0.value" => array( $startDate, '<='),
                                "field_date.0.end_value" => array( $startDate, '>')
                            )
                        ),
                        array(
                            "andor" => "AND",
                            "grouping" => array(
                                "field_date.0.value" => array( $endDate, '<'),
                                "field_date.0.end_value" => array( $endDate, '>=')
                            )
                        ),
                        "field_date.0.value" => array( array($startDate, $endDate), 'BETWEEN'),
                        "field_date.0.end_value" => array( array($startDate, $endDate), 'BETWEEN')
                    )

                );

                $sort = array(
                    array(
                        "field" => 'field_featured',
                        "direction" => 'DESC'
                    ),
                    array(
                        "field" => 'field_date',
                        "direction" => 'ASC'
                    )
                );

            }else{

                return [
                    '#type' => 'markup',
                    '#markup' => '<p class="no-results">Sorry, the date format is not valid.</p>',
                ];

            }

        }

        // Adding Category to group if required
        // If category is set to (field_name--tid-tid)
        if($category != '' && strpos($category,'all') === false){

            $categoryArray = explode('--',$category);
            $tids = explode('-',$categoryArray[1]);

            $conditions['group2'] =array(
                "andor" => "AND",
                'grouping' => array(
                    $categoryArray[0] => array( $tids, 'IN'),
                )
            );

        }



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



    /**
     * Get Nodes with a pager and filter by category if necessary
     *
     * @param string $content_type
     * @param string $view_mode
     * @param int $page
     * @param int $perPage
     * @param null $category
     * @return array
     *
     */
    public function getPagedNodes($content_type = 'article', $view_mode = 'teaser', $page = 1, $perPage = 10, $category = null) {


        $conditions = array(
            "status" => 1
        );


        // If category is set to term id number
        if($category && $category !== "all"){
            $conditions["field_category.entity.tid"] = $category;
        }


        $sort = array(
            array(
                "field" => 'created',
                "direction" => 'DESC'
            )
        );


        return $this->efqQueryEntities->getEntitiesPaged($page, $perPage, $content_type, $view_mode, $conditions, $sort, $category);


    }



    /**
     * @param $value
     * @return array
     */
    protected function parseDate($value){

        $values = explode('--',$value);
        $field = $values[0];
        $date = $values[1];

        $dateArray = explode(',',$date);

        if ( $this->verifyDate($dateArray[0]) && $this->verifyDate($dateArray[1]) ){

            $startDate = $this->formatDateFilter($dateArray[0], 'start');
            $endDate = $this->formatDateFilter($dateArray[1], 'end');

            return array(
                "andor" => "OR",
                'grouping' => array(
                    array(
                        "andor" => "AND",
                        "grouping" => array(
                            $field.".0.value" => array( $startDate, '<='),
                            $field.".0.end_value" => array( $startDate, '>')
                        )
                    ),
                    array(
                        "andor" => "AND",
                        "grouping" => array(
                            $field.".0.value" => array( $endDate, '<'),
                            $field.".0.end_value" => array( $endDate, '>=')
                        )
                    ),
                    $field.".0.value" => array( array($startDate, $endDate), 'BETWEEN'),
                    $field.".0.end_value" => array( array($startDate, $endDate), 'BETWEEN')
                )

            );

        }else{

            return [
                '#type' => 'markup',
                '#markup' => '<p class="no-results">Sorry, the date format is not valid.</p>',
            ];

        }

    }


    /**
     * @param $value
     * @return array
     */
    protected function parseByMonth($value){

        // Explode value
        $values = explode('--',$value);
        $field = $values[0];
        $date = $values[1];

        if ( $this->verifyDate($date) ){

            // Get date as array
            $dateArr = date_parse_from_format ("d-m-Y" ,  $date);
            // Get number of days in month
            $numDays = cal_days_in_month(CAL_GREGORIAN, $dateArr['month'], $dateArr['year']);

            $startDate = $this->formatDateFilter($date, 'start');
            $endDate = $this->formatDateFilter($numDays.'-'.$dateArr['month'].'-'.$dateArr['year'], 'end');

            return array(
                "andor" => "OR",
                'grouping' => array(
                    $field.".0.value" => array( array($startDate, $endDate), 'BETWEEN'),
                    $field.".0.end_value" => array( array($startDate, $endDate), 'BETWEEN')
                )

            );

        }else{

            return [
                '#type' => 'markup',
                '#markup' => '<p class="no-results">Sorry, the date format is not valid.</p>',
            ];

        }

    }



    /**
     * @param $value
     * @return array
     */
    protected function parseCategory($value){

        $categoryArray = explode('--',$value);
        $tids = explode('-',$categoryArray[1]);

        return array(
            "andor" => "AND",
            'grouping' => array(
                $categoryArray[0] => array( $tids, 'IN'),
            )
        );

    }



    /**
     * @param $value
     * @return array
     */
    protected function parseSort($value){

        $values = explode(',',$value);
        $sorts = array();

        foreach($values as $pair){

            $sort = explode('-',$pair);
            $sorts[] = array(
                "field" => $sort[0],
                "direction" => $sort[1]
            );
        }

        return $sorts;

    }



    /**
     * @param $value
     * @return array
     */
    protected function parseBasicValues($value){

        return explode('-',$value);

    }



    /**
     * Get start or end of day
     *
     * @param $date
     * @param string $delta (either start or end)
     * @return DrupalDateTime
     */
    protected function formatDateFilter ($date, $delta = 'start'){

        // Transform $date string into a date object, first hour first minute of day
        $formatDate = new DrupalDateTime($date);

        // Set the time zone as per stored in DB (GMT usually)
        $formatDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));

        if ( $delta == 'end' ){
            // Transform $date string into a date object, last hour last minute of day
            $formatDate->setTime(23,59);
        }else{
            // Transform $date string into a date object, first hour first minute of day
            $formatDate->setTime(00,01);
        }

        // Return the DrupalDatetime object
        return $formatDate->format(DATETIME_DATETIME_STORAGE_FORMAT);

    }



    /** Verify a date is valid
     * @param $date
     * @return bool
     */
    protected function verifyDate($date) {
        $dateFormat = \DateTime::createFromFormat($this->dateFormat, $date);
        if (!$dateFormat instanceof \DateTime) {
            return false;
        }else{
            return true;
        }
    }



}
