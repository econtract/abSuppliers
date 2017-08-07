<?php

namespace abSuppliers;

use AnbApiClient\Aanbieders;
use Locale;
use WP_Router;


/**
 * Class abSuppliers
 * @package abSuppliers
 */
class AbSuppliers {

    use Helper;

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
        'packs',
        // 'mobile',
        //'telephony',
        //'idtv'
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
        delete_transient('abCompareSuppliers');
        // $suppliers = get_transient('abCompareSuppliers');
        $suppliers = null;

        if (!$suppliers) {
            $suppliers = $this->anbApi->getSuppliers(
                [
                    'cat'           => $atts['products'],
                    'lang'          => $atts['lang'],
                    'detaillevel'   => $atts['detaillevel'],
                    'partners_only' => $atts['partners_only'],
                    //'sg'          => trim($segment)
                ]
            );

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

        $this->totalFoundLogos = 0;

        if (is_array($suppliers)) {
            foreach ($suppliers as $supplier) {
                $supplierList[$supplier['supplier_id']]['slug'] = $supplier['slug'];
                $supplierList[$supplier['supplier_id']]['name'] = $supplier['name'];
                $supplierList[$supplier['supplier_id']]['services'] = $supplier['services'];
                $supplierList[$supplier['supplier_id']]['id'] = $supplier['supplier_id'];
                $supplierList[$supplier['supplier_id']]['is_partner'] = $supplier['is_partner'];

                if ($atts['image-color-type'] == 'transparent' && isset($supplier['logo'][$atts['image-size']][$atts['image-color-type']])) {
                    $supplierList[$supplier['supplier_id']]['logo'] = $supplier['logo'][$atts['image-size']]['transparent']['color'];
                } else {
                    $supplierList[$supplier['supplier_id']]['logo'] = $supplier['logo'][$atts['image-size']][$atts['image-color-type']];
                }
            }
        }

        $this->totalFoundLogos = count($supplierList);

        return $supplierList;
    }

    /**
     * @param $atts
     * @return array
     */
    private function preparedSuppliersLogoData($atts)
    {
        $atts = $this->prepareShortCodeAttributes($atts);

        $getLogos = $this->getSupplierLogos($atts);
        $supplierLogos = $this->sortSupplier(
            $getLogos,
            $atts
        );
        return array($atts, $supplierLogos);
    }

    /**
     * @param $atts
     * @return string
     */
    public function displaySupplierPartners($atts)
    {
        $atts['partners_only'] = true;

        list($atts, $supplierLogos) = $this->preparedSuppliersLogoData($atts);

        $counter = 0;

        $response = '<div class="row">';

        foreach ($supplierLogos as $supplier) {

            // poly lang exists
            if (function_exists('pll_home_url')) {
                $atts['link'] = pll_home_url().pll__('brands').'/'.$supplier['slug'];
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
        list($atts, $supplierLogos) = $this->preparedSuppliersLogoData($atts);

        $counter = 0;
        $response = '<div class="row">';

        foreach ($supplierLogos as $supplier) {

            // poly lang exists
            if (function_exists('pll_home_url')) {
                $atts['link'] = pll_home_url().pll__('brands').'/'.$supplier['slug'];
            }

            // If $counter is divisible by $mod...
            if($counter % $atts['mod'] == 0 && $counter != 0)
            {
                // New div row
                $response.= '</div><div class="row">';
            }
            $response .= '<' .$atts['mark-up'] . ' class="col-sm-3" >'.
                '<div class="provider" >'.
                /*'<div class="bestReviewBadge" >'.
                        '<span>BEST</span>'.
                        '<span class="bold">Review</span>'.
                '</div>'.*/
                '<div class="providerWrapper" >'.
                    '<img src="' .$supplier['logo'] . '"'.
                          ' alt="' . $supplier['name'] . '">'.
                '<div class="moreInfo">'
                .'<h4>'. $supplier['name'] .'</h4>'.
                '<div class="services">'.
                $this->acquireService($supplier).
                '</div>'.
                '<div class="btnWrapper">
                   <a href='.$atts['link'].' class="btn btn-primary">'.pll__('More Info').'</a>
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
        $this->preparedSuppliersLogoData($atts);

        return $this->totalFoundLogos;
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
            'lang' => Locale::getPrimaryLanguage(get_locale()),
            'segments' => $this->segments,
            'products' => $this->productTypes,
            'sort-by' => 'name',
            'image-size' => '100x70',
            'image-color-type' => 'transparent',
            'mark-up' => 'div',
            'mark-up-class' => '',
            'link' => '#',
            'detaillevel' => 'logo',
            'mod'        => '6',
            'partners_only' => false

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
     * @param int $orderBy
     * @return mixed
     */
    private function sortSupplier($supplierLogos, $atts, $orderBy = SORT_ASC)
    {
        $sortArray = array();
        foreach($supplierLogos as $logo){
            foreach($logo as $key=>$value){
                if(!isset($sortArray[$key])){
                    $sortArray[$key] = array();
                }
                $sortArray[$key][] = (is_string($value)) ?  strtolower($value) : $value;
            }
        }

       array_multisort($sortArray[$atts['sort-by']], $orderBy, $supplierLogos);

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
            'template' => array(
                'page-provider-details.php',
                get_template_directory() . 'page-provider-details.php'
            )
        ));
    }

    /**
     *  call back will fetch data from API
     *  will load Html file and embed it with theme
     *  will replace content with specifically relates to supplier
     */
    public function suppliersCallback(  )
    {
        $supplier = $this->getUriSegment(2);
       // $product  = $this->getUriSegment(3);

        $lang = $this->getLanguage();

        $getSupplier = $this->anbApi->getSuppliers(
            [
                'pref_cs' => $supplier,
                'lang'    => $lang
            ]
        );

        $getProducts = $this->anbApi->getProducts(
            [
                'sid'         => $getSupplier[0]['supplier_id'],
                'lang'        => $lang,
                'cat'         => $this->productTypes,
                'detaillevel' => ['reviews']
            ]
        );

       // var_dump($getSupplier[0]['supplier_id'], $getProducts); die;

        if(session_id() == '') {
            session_start();
        }

        $_SESSION['supplierData'] = $getSupplier;
        $_SESSION['supplierProducts'] = $getProducts;

    }

    /**
     * @return array
     */
    public function prepareSupplierProducts() {

        $supplierProducts = $_SESSION['supplierProducts'];

        $listProducts = [];
        $temp = $packTemp = 0;

        foreach ($supplierProducts as $product) {

            if (!isset($listProducts[$product['producttype']])) {
                $listProducts[$product['producttype']] = [];
                $listProducts[$product['producttype']]['count'] = 0;
                $i = 0;
            }

            if ($product['producttype'] == 'packs' &&
                !isset($listProducts[$product['producttype']][$product['packtype']])) {
                $listProducts[$product['producttype']][$product['packtype']] = [];
                $listProducts[$product['producttype']][$product['packtype']]['count'] = 0;
                $subPack = 0;
            }

            if (array_key_exists('packtype', $product)) {

                //check if this is first loop then assign first value as $min
                if ($subPack == 0) {
                    $listProducts[$product['producttype']][$product['packtype']]['fee'] = $packTemp = $product['monthly_fee']['value'];
                    $listProducts[$product['producttype']][$product['packtype']]['unit'] = $product['monthly_fee']['unit'];
                }
                //if this is not first loop then go in this if
                if ($subPack > 0) {
                    //check if this value of array is lesser than $packTemp
                    if ($product['monthly_fee']['value'] < $packTemp) {
                        $listProducts[$product['producttype']][$product['packtype']]['fee'] = $product['monthly_fee']['value'];
                        $listProducts[$product['producttype']][$product['packtype']]['unit'] = $product['monthly_fee']['unit'];

                    }
                }

                if (isset($listProducts[$product['producttype']][$product['packtype']]['count'])) {
                    $listProducts[$product['producttype']][$product['packtype']]['count']++;
                    $subPack++;
                }

            }

            // Don't check fee for the top level packs
            if ($product['producttype'] != 'packs' ) {

                //check if this is first loop then assign first value as $min
                if ($i == 0) {
                    $listProducts[$product['producttype']]['fee'] = $temp = $product['monthly_fee']['value'];
                    $listProducts[$product['producttype']]['unit'] = $product['monthly_fee']['unit'];
                }
                //if this is not first loop then go in this if
                if ($i > 0) {
                    //check if this value of array is lesser than $temp
                    if ($product['monthly_fee']['value'] < $temp) {
                        $listProducts[$product['producttype']]['fee'] = $product['monthly_fee']['value'];
                        $listProducts[$product['producttype']]['unit'] = $product['monthly_fee']['unit'];

                    }
                }
            }
            if (isset($listProducts[$product['producttype']]['count'])) {
                $listProducts[$product['producttype']]['count']++;
                $i++;
            }
        }

        return $listProducts;
    }

    /**
     * acquire supplier services
     * @param $provider
     * @return string
     */
    private function acquireService($provider) {

        $checkFor = [
            'mobile' => 'mobile',
            'phone'  => 'telephony',
            'tv'     => 'idtv',
            'wifi'   => 'internet',
        ];

        $html = '<ul class="list-unstyled list-inline">';

        // Alphabetically sort array to services consistent
        sort($provider['services']);

        foreach ($provider['services'] as $key => $service) {
            if (in_array($service, $checkFor)) {
                $html .= '<li> <i class="service-icons white '. array_search($service, $checkFor).'"></i> </li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * @param $atts
     * @return array
     */
    private function prepareReviewShortCodeParams($atts)
    {
        // get supplier slug from url
        $supplier = $this->getUriSegment(2);

        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        // override default attributes with user attributes
        return shortcode_atts([
            'lang' => $this->getLanguage(),
            'cat' => $this->productTypes,
            'pref_cs' => $supplier,
            'limit' => '1',
            'html'  => true
        ], $atts, 'anb_supplier_reviews');
    }

    /**
     * @param $atts
     * @return null
     */
    public function getReviews($atts = [])
    {
        $atts = $this->prepareReviewShortCodeParams($atts);
        $reviews = null;

        if (!$reviews) {
            $reviews = $this->anbApi->getReviews(
                $atts
            );
        }

        return $reviews;
    }

    /**
     * @param $atts
     * @return string
     */
    public function showReviews($atts)
    {
        $reviews = $this->getReviews($atts);

        $html = ' ';

        if ($reviews) {

            foreach ($reviews as $review){

                $string = $review['texts']['contents'];
                if (strlen($string) > 350) {

                    // truncate string
                    $stringCut = substr($review['texts']['contents'], 0, 350);

                    // make sure it ends in a word so assassinate doesn't become ass...
                    $string = substr($stringCut, 0, strrpos($stringCut, ' ')).'...';
                }

                $html .= '<div class="col-md-5 infoPanel">
                            <h6>'.$review['texts']['title'].'</h6>
                            <p>'.$string.'</p>
                            <p class="infoStamp">'.date("Y/m/d, H:i", strtotime($review['date'])).' - '.$review['author'].', '.$review['city'].'</p>
                            <!--<a href="#"><i class="fa fa-thumbs-o-up"></i>Is this useful?</a>-->
                        </div>
                        <div class="col-md-3 ratingPanel">
                            <div class="row header">
                                <div class="col-xs-8 ratingTitle">'.pll__('Total Rating').'</div>
                                <div class="col-xs-4 countTitle">'.number_format((float)$review['score'], 1, '.', '').'</div>
                            </div>
                            '.$this->fetchReviewRatings($review['ratings']).'
                      </div>';
            }
        }

        return $html;
    }

    /**
     * @param $reviewRatings
     * @return string
     */
    private function fetchReviewRatings($reviewRatings)
    {
        $html = '';

        if ($reviewRatings) {
            foreach ($reviewRatings as $key => $reviewRating) {
                $html .= '<div class="row">
                <div class="col-xs-8 ratingCaption">' . (!empty($reviewRating['label']) ? $reviewRating['label'] : $key) . '</div>
                <div class="col-xs-4 ratingCount">' . $reviewRating['score'] . '</div>
            </div>';
            }
        }

        return $html;
    }


    /**
     * @return string
     */
    public function suppliersForResultFilters()
    {
        $atts = [
          'sort-by' => 'is_partner'
        ];
        $html = '';
        $nonPartner = false;

        $atts = $this->prepareShortCodeAttributes($atts);

        $getLogos = $this->getSupplierLogos($atts);
        $supplierSorted = $this->sortSupplier(
            $getLogos,
            $atts,
            SORT_DESC
        );

        $queryParams = $this->getUriQuery();

        $selectedProviders = (!empty($queryParams) && isset($queryParams['pref_cs'])? $queryParams['pref_cs']: [] ) ;

        foreach ($supplierSorted as $supplier) {

            if ($supplier['is_partner'] == 0  && $nonPartner == 0) {
                $nonPartner = true;
                $html .= '<div class="moreFilterWrapper moreNonPartners"><a>'.pll__('+ more').'</a></div>';
                $html .= '<div class="NonPartnersResult">';
            }

            $checked = (in_array($supplier['id'], $selectedProviders ) ? 'checked' : '');

            $html .= '<div class="checkbox fancyCheck">
                        <input type="checkbox"  name="pref_cs[]" '.$checked.'  value="' . $supplier['id'] . '" id="' . $supplier['name'] . '">
                        <label for="' . $supplier['name'] . '">
                            <i class="unchecked"></i>
                            <i class="checked"></i>
                            <span>' . $supplier['name'] . ' </span>
                        </label>
                    </div>';

        }

        if ($nonPartner) {
            $html .= '</div>';
            $html .= '<div class="moreFilterWrapper lessNonPartners"><a>'.pll__('- less').'</a></div>';
        }

        return $html;
    }


    /**
     * @return string
     */
    public function supplierAsOptionsForDropDown()
    {
        $atts = [ ];
        $html = '';

        $atts = $this->prepareShortCodeAttributes($atts);

        $getLogos = $this->getSupplierLogos($atts);
        $supplierSorted = $this->sortSupplier(
            $getLogos,
            $atts
        );

        foreach ($supplierSorted as $supplier) {

            $html .= '<option value="' . $supplier['id'] . '">' . $supplier['name'] . '</option>';
        }

        return $html;
    }

    public function registerStringsForLocalization ()
    {
        pll_register_string('abSuppliers', 'brands', 'Suppliers', true);
    }

    //echo do_shortcode('[anb_suppliers mark-up="div" mark-up-class="col-sm-2 serviceProvider" lang="nl" segments="sme" products="internet" mod="6"]'); />
}
