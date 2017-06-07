<?php

namespace abSuppliers;

use AnbApiClient\Aanbieders;
use WP_Router;


/**
 * Class abSuppliers
 * @package abSuppliers
 */
class abSuppliers {

    /**
     * @var string
     */
    public $apiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings

    /**
     * @var mixed
     */
    public $anbApi;

    /**
     * @var array
     */
    public $apiConf = [
        'staging' => ANB_API_STAGING,
        'key'     => ANB_API_KEY,
        'secret'  => ANB_API_SECRET
    ];

    /**
     * @var array
     */
    public $segments = [
        'consumer',
        'sme'
    ];

    /**
     * @var array
     */
    public $productTypes = [
        'internet',
        'mobile',
        'packs',
        'telephony',
        'electricity',
        'gas',
        'mobile_internet',
       // 'savings',
       //   'longterm',
       // 'creditcard',
        'idtv',
        'dualfuel_pack',
        //'mobile_device_offer',
        //'prepaid_creditcard'
    ];

    /**
     * @var int
     */
    public $totalFoundLogos = 0;

    /**
     * abSuppliers constructor.
     */
    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);
        $this->anbApi->setOutputType('array');

        // Create dynamic routes for suppliers
        add_action('wp_router_generate_routes', array(get_class(), 'generate_routes'), 10, 1);
    }

    /**
     * @param $atts
     * @return null
     */
    function getSuppliers($atts)
    {
        //delete_transient( 'abCompareSuppliers' );
       // $suppliers = get_transient('abCompareSuppliers');
        $suppliers = null;

        if (!$suppliers) {

            foreach ($atts['segments'] as $segment) {
                foreach ($atts['products'] as $product) {
                    $suppliers[$segment][$product] = $this->anbApi->getSuppliers(
                        [
                            'cat'         => trim($product),
                            'lang'        => $atts['lang'],
                            'sg'          => trim($segment),
                            'detaillevel' => $atts['detaillevel']
                        ]
                    );

                }

            }

           // set_transient('abCompareSuppliers', $suppliers, 60 * 60 * 2592000);
        }

        return $suppliers;
    }

    /**
     * @param $atts
     * @return mixed
     */
    public function getSupplierLogos($atts)
    {
        $suppliers = $this->getSuppliers($atts);

        foreach ($suppliers as $segment => $supplierSegment) {
            foreach ($supplierSegment as $type => $supplierType) {
                if( is_array( $supplierType) ){
                    foreach ($supplierType as $supplier) {
                        $supplierList[$supplier['supplier_id']]['name'] = $supplier['name'];

                        if($atts['image-color-type']=='transparent' && isset($supplier['logo'][$atts['image-size']][$atts['image-color-type']]) ){
                            $supplierList[$supplier['supplier_id']]['logo'] = $supplier['logo'][$atts['image-size']]['transparent']['color'];
                        }
                        else{
                            $supplierList[$supplier['supplier_id']]['logo'] = $supplier['logo'][$atts['image-size']][$atts['image-color-type']];
                        }
                    }
                }
            }
        }

        return $supplierList;
    }

    /**
     * @param $atts
     * @return string
     */
    public function prepareSuppliersForFrontEnd( $atts )
    {
        $atts = $this->prepareShortCodeAttributes($atts);

        $getLogos = $this->getSupplierLogos($atts);
        $supplierLogos =  $this->sortSupplier(
            $getLogos,
            $atts
        );

        $counter = 0;

        $response = '<div class="row">';

        foreach ($supplierLogos as $supplier) {

            // If $counter is divisible by $mod...
            if($counter % $atts['mod'] == 0 && $counter != 0)
            {
                // New div row
                $response.= '</div><div class="row">';
            }

            $response .= '<' .$atts['mark-up'] .
                            ' class="' . $atts['mark-up-class'] . '">'.
                            '<a href="' .$atts['link'] . '"'.
                            ' title="' . $supplier['name'] . '">'.
                            '<img src="' .$supplier['logo'] . '"'.
                            ' alt="' . $supplier['name'] . '">'.
                            '</a>'.
                          '</' .$atts['mark-up'] . '>';

            $counter++;
        }

        if ($atts['mark-up'] == 'li') {
            return '<ul>'.
                        $response;
                   '</ul>';
        }
        return $response;
    }


    /**
     * @param $atts
     * @return string
     */
    public function countSuppliersLogo( $atts )
    {
        $atts = $this->prepareShortCodeAttributes($atts);

        $getLogos = $this->getSupplierLogos($atts);
        $supplierLogos =  $this->sortSupplier(
            $getLogos,
            $atts
        );
        $counter = 0;
        foreach ($supplierLogos as $supplier) {
            if ($supplier) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * @param $atts
     * @return array
     */
    private function prepareShortCodeAttributes($atts)
    {
        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        // override default attributes with user attributes
        $atts = shortcode_atts([
            'lang' => 'nl',
            'segments' => $this->segments,
            'products' => $this->productTypes,
            'sortBy' => 'name',
            'image-size' => '100x70',
            'image-color-type' => 'transparent',
            'mark-up' => 'li',
            'mark-up-class' => '',
            'link' => '#',
            'detaillevel' => 'logo',
            'mod'        => '6'

        ], $atts, 'anb_suppliers');

        $atts['segments'] = (is_string($atts['segments']) && strpos($atts['segments'], ',') !== false) ? explode(",", $atts['segments']) : $atts['segments'];
        $atts['products'] = (is_string($atts['products']) && strpos($atts['products'], ',') !== false) ? explode(",", $atts['products']) : $atts['products'];

        // type casting
        $atts['segments'] = (array) $atts['segments'];
        $atts['products'] = (array) $atts['products'];

        // poly lang exists
        if (function_exists('pll_current_language')) {
            $atts['lang'] = pll_current_language();
        }

        return $atts;
    }

    /**
     * @param $supplierLogos
     * @param $atts
     * @return bool
     */
    private function sortSupplier($supplierLogos, $atts)
    {
        $sortArray = array();

        foreach($supplierLogos as $person){
            foreach($person as $key=>$value){
                if(!isset($sortArray[$key])){
                    $sortArray[$key] = array();
                }
                $sortArray[$key][] = strtolower($value);
            }
        }

       array_multisort($sortArray[$atts['sortBy']], SORT_ASC, $supplierLogos);

       return $supplierLogos;
    }

    public static function generate_routes( WP_Router $router )
    {
        $router->add_route('aanbieders-suppliers-router', array(
            'path' => '^brands/(.*?)$',
            'query_vars' => array(
                'argument' => 2,
            ),
            'page_callback' => array(get_class(), 'suppliers_callback'),
            'page_arguments' => array('chummi', '123'),
            'access_callback' => TRUE,
            'title' => __( '' ),
            /*'template' => array(
                'views/display.php',
                dirname( __FILE__ ) . '/views/display.php'
            )
            */
            /*'template' => array(
                'mypage.php',
                get_template_directory() . 'mypage.php'
            )*/
        ));
    }

    public static function suppliers_callback( $argument, $test )
    {
        echo '<p>Welcome to the WP Router sample page. You can find the code that generates this page in '.__FILE__.'</p>';
        echo '<p>This page helpfully tells you the value of the <code>sample_argument</code> query variable: '.esc_html($argument).'</p>';

        echo 'arslan file';
        var_dump($argument, $test);
    }



    //echo do_shortcode('[anb_suppliers mark-up="div" mark-up-class="col-sm-2 serviceProvider" lang="nl" segments="sme" products="internet" mod="6"]'); />
}
