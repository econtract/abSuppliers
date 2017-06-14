<?php

namespace abSuppliers;

use AnbApiClient\Aanbieders;
use WP_Router;


/**
 * Class abSuppliers
 * @package abSuppliers
 */
class AbSuppliers {

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
        'idtv'
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
        add_action('wp_router_generate_routes', array($this, 'generateRoutes'), 10, 1);

        // add string for the plugin to polylang
        add_action('init', array($this, 'registerStringsForLocalization'));
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
                        $supplierList[$supplier['supplier_id']]['id'] = $supplier['supplier_id'];
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
    public function prepareSuppliersForLandingPage($atts )
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

            // poly lang exists
            if (function_exists('pll_home_url')) {
                $atts['link'] = pll_home_url().pll__('brands').'/'.$supplier['id'];
            }

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
    public function prepareSuppliersForOverview($atts )
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

            // poly lang exists
            if (function_exists('pll_home_url')) {
                $atts['link'] = pll_home_url().pll__('brands').'/'.$supplier['id'];
            }

            // If $counter is divisible by $mod...
            if($counter % $atts['mod'] == 0 && $counter != 0)
            {
                // New div row
                $response.= '</div><div class="row">';
            }
            $response .= '<' .$atts['mark-up'] . ' class="col-sm-3" >'.
                '<div class="provider" >'.
                '<div class="bestReviewBadge" >'.
                        '<span>BEST</span>'.
                        '<span class="bold">Review</span>'.
                '</div>'.
                '<div class="providerWrapper" >'.
                    '<img src="' .$supplier['logo'] . '"'.
                          ' alt="' . $supplier['name'] . '">'.
                '<div class="moreInfo">'
                .'<h4>'. $supplier['name'] .'</h4>'.
                '<div class="services">'.
                '<ul class="list-unstyled list-inline">
                                                    <li>
                                                        <i class="fa fa-wifi"></i>
                                                    </li>
                                                    <li>
                                                        <i class="fa fa-mobile"></i>
                                                    </li>
                                                    <li>
                                                        <i class="fa fa-phone"></i>
                                                    </li>
                                                    <li>
                                                        <i class="fa fa-tv"></i>
                                                    </li>
                                                </ul>'.
                '</div>'.
                '<div class="btnWrapper">
                   <a href='.$atts['link'].' class="btn btn-primary">More Info</a>
                 </div>'.
                '</div>'.
                '</div>
                    </div>'.
                '</' .$atts['mark-up'] . '>';


            $counter++;
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
            'mark-up' => 'div',
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
        foreach($supplierLogos as $logo){
            foreach($logo as $key=>$value){
                if(!isset($sortArray[$key])){
                    $sortArray[$key] = array();
                }
                $sortArray[$key][] = strtolower($value);
            }
        }
       array_multisort($sortArray[$atts['sortBy']], SORT_ASC, $supplierLogos);

       return $supplierLogos;
    }

    public function generateRoutes( WP_Router $router )
    {
        $router->add_route('aanbieders-suppliers-router', array(
            'path' => '^'. pll__('brands').'/(.*?)$',
            'query_vars' => [],
            'page_callback' => array($this, 'suppliersCallback'),
            'page_arguments' =>  [],
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

    public function suppliersCallback(  )
    {
        $supplier = $this->getUriSegment(2);
       // $product  = $this->getUriSegment(3);


        $getSupplier = $this->anbApi->getSuppliers(
            [
                'pref_cs' => $supplier,
                'lang'    => function_exists('pll_current_language') ? pll_current_language() : 'nl'
            ]
        );

       // var_dump($getSupplier); die;

        echo '<p>Welcome to the WP Router sample page. You can find the code that generates this page in '.__FILE__.'</p>';
        echo '<p>This page helpfully tells you the value of the <code>sample_argument</code> query variable: '.esc_html('').'</p>';

        echo 'arslan file';
    }

    /**
     * @return array
     */
    private function getUriSegments()
    {
        return explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    }

    /**
     * @param $n
     * @return mixed|string
     */
    private function getUriSegment($n)
    {
        $segment = $this->getUriSegments();
        return count($segment)>0&&count($segment)>=($n-1) ? $segment[$n] : '';
    }


    public function registerStringsForLocalization ()
    {
        pll_register_string('abSuppliers', 'brands', 'Suppliers', true);
    }

    //echo do_shortcode('[anb_suppliers mark-up="div" mark-up-class="col-sm-2 serviceProvider" lang="nl" segments="sme" products="internet" mod="6"]'); />
}
