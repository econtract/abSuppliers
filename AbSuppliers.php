<?php

namespace abSuppliers;

use AnbApiClient\Aanbieders;
use AnbSearch\AnbCompare;
use AnbTopDeals\AnbProduct;
use Locale;


if(!function_exists('getLanguage')) {
    function getLanguage()
    {
        //get locale
        $locale = function_exists('pll_current_language') ? pll_current_language() : Locale::getPrimaryLanguage(get_locale());

        return $locale;
    }
}

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

	/** @var Aanbieders mixed */
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

	public $productTypesEnergy = [
		'dualfuel_pack',
		'electricity',
		'gas'
	];

    /**
     * @var int
     */
    public $totalFoundLogos = 0;

    /**
     * @var string
     */
    public $currencyUnit = 'EUR';

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

        add_action( 'wp_enqueue_scripts', array($this, 'enqueueScripts') );
    }

    /**
     * AJAX scripts
    **/

    function enqueueScripts()
    {
        wp_enqueue_script('suppliers-ajax', plugins_url('/js/suppliers-ajax.js', __FILE__), array('jquery'), '1.0.2', true);
        wp_localize_script('suppliers-ajax', 'suppliers_ajax_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'site_url' => pll_home_url(),
                'template_uri' => get_template_directory_uri(),
                'lang' => getLanguage(),
                'trans_loading_dots'    => pll__('Loading...')
            )
        );
    }

    /**
     * @param $atts
     * @return null
     */
    function getSuppliers($atts, $enableCache = true, $cacheDurationSeconds = 14400)
    {
        if(defined('SUPPLIER_API_CACHE_DURATION')) {
            $cacheDurationSeconds = SUPPLIER_API_CACHE_DURATION;
        }

        $suppliers = null;

        //generate key from params to store in cache
        $displayText = "API Call (getSuppliers) inside getSuppliers";
        $start = getStartTime();
	    $params = [
		    'cat'           => $atts['products'],
		    'lang'          => $atts['lang'],
		    'detaillevel'   => $atts['detaillevel'],
		    'partners_only' => $atts['partners_only'],
		    'pref_cs'       => $atts['pref_cs']
	    ];

	    //remove empty values
	    $params = array_filter($params);

	    if($_GET['debug']) {
		    echo "<pre>supplier logo params>>>";
		    print_r($params);
		    echo "</pre>";
	    }

        if ($enableCache && !isset($_GET['no_cache'])) {
	    	$keyParams = $atts;
	    	if(isset($params['cat'])) {
			    $keyParams = $atts + $params['cat'];
		    }
            $cacheKey = md5(serialize($keyParams)) . ":getSuppliers";
            $suppliers = mycache_get($cacheKey);

            if($suppliers === false || empty($suppliers)) {
                $suppliers = json_encode($this->anbApi->getSuppliers( $params ));
	            mycache_set($cacheKey, $suppliers, $cacheDurationSeconds);
            } else {
                $displayText = "API Call Cached (getSuppliers) inside getSuppliers";
            }
        } else {
            $suppliers = json_encode($this->anbApi->getSuppliers($params));
        }

        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);

        return ($suppliers) ? json_decode($suppliers, true) : $suppliers;
    }

    /**
     * @param $atts
     * @return mixed
     */
    public function getSupplierLogos($atts)
    {
        $suppliers = $this->getSuppliers($atts);

        $this->totalFoundLogos = 0;

	    $supplierList = [];

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
    protected function preparedSuppliersLogoData($atts)
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

	    $atts = $this->processMultipleProductCats( $atts );

	    list($atts, $supplierLogos) = $this->preparedSuppliersLogoData($atts);

        $totalLogos = count($supplierLogos);


        if($totalLogos > 12){
            $mod6 = $totalLogos % 6;
            $mod5 = $totalLogos % 5;

            /*
             * $breakPint defines the number of rows the logos will appear
             * if records are more than 12 then the logos will be have 5 or 6 column
             * if modulus of 6 and 5 are equal than number of columns in each row is 6
             * if modulus of 6 and 5 not equal to zero then $breakPoint will be the one that return high modulus value*/
            $breakPoint = (($mod6 === $mod5) || ($mod6 ===0) || ($mod6 > $mod5 && $mod5 != 0))?6:5;
        }
        else{

            /*if records are less than or equal to 10 than break point will be the ceiled result we get dividing by 2*/
            $breakPoint = ceil($totalLogos / 2);
        }

        $counter = 0;

        $response = '<div class="row">';

        foreach ($supplierLogos as $supplier) {
	        $atts['link'] = $this->generateProviderLink( $atts, $supplier );

	        // If $counter is divisible by $mod...
            if($counter % $breakPoint == 0 && $counter != 0)
            {
                // New div row
                $response.= '</div><div class="row">';
            }
            $greyClass = '';
            if(empty($atts['partners_only']) || $atts['partners_only'] === false){ $greyClass = 'partnergrey'; }
            $response .= '<' . $atts['mark-up'] .
                ' class="' . $atts['mark-up-class'] . '">' .
                '<a href="' . $atts['link'] . '"' .
                ' title="' . $supplier['name'] . '">' .
                '<img class="'. $greyClass .'" src="' . $supplier['logo'] . '"' .
                ' alt="' . $supplier['name'] . '">' .
                '</a>' .
                '</' . $atts['mark-up'] . '>';
            $counter++;
        }

        $response .= '</div>';

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
    public function prepareSuppliersForOverview( $atts )
    {
        list($atts, $supplierLogos) = $this->preparedSuppliersLogoData($atts);

        $counter = 0;
        $response = '<div class="row">';

        foreach ($supplierLogos as $supplier) {

	        $atts['link'] = $this->generateProviderLink( $atts, $supplier );

            // If $counter is divisible by $mod...
            if($counter % $atts['mod'] == 0 && $counter != 0)
            {
                // New div row
                $response.= '</div><div class="row">';
            }
            /*$logoStr = '<div class="suppliertext">'.$supplier['name'].'</div>';
            if($supplier['is_partner'] == 1) {
                $logoStr = '<img src="' . $supplier['logo'] . '" alt="' . $supplier['name'] . '">';
            }*/
            $greyClass = 'partnergrey';
            if($supplier['is_partner'] == 1){ $greyClass = ''; }
            $logoStr = '<img class="'.$greyClass.'" src="' . $supplier['logo'] . '" alt="' . $supplier['name'] . '">';
            $response .= '<' . $atts['mark-up'] . ' class="col-sm-3" >' .
                    '<div class="provider" >' .
                    /*'<div class="bestReviewBadge"><span>BEST</span><span class="bold">Review</span></div>'.*/
                    '<div class="providerWrapper" >' . $logoStr .
                    '<div class="moreInfo">' .
                    '<h4>' . $supplier['name'] . '</h4>' .
                    '<div class="services">' .
                    $this->acquireService($supplier) .
                    '</div>' .
                    '<div class="btnWrapper">
                       <a href=' . $atts['link'] . ' class="btn btn-primary">' . pll__('More Info') . '</a>
                     </div>' .
                    '</div>' .
                    '</div>
                    </div>' .
                    '</' . $atts['mark-up'] . '>';
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
    	$atts = $this->processMultipleProductCats($atts);

        $this->preparedSuppliersLogoData($atts);

        return $this->totalFoundLogos;
    }

    /**
     * @param $atts
     * @return array
     */
    public function prepareShortCodeAttributes($atts)
    {
	    $atts = $this->catsToArray( $atts );

        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

        // override default attributes with user attributes
        $atts = shortcode_atts([
            'lang' => getLanguage(),
            'segments' => $atts['sg'] ?: $this->segments,
            'products' => $atts['cat'] ?: $this->productTypes,
            'sort-by' => 'name',
            'image-size' => '100x70',
            'image-color-type' => 'transparent',
            'mark-up' => 'div',
            'mark-up-class' => '',
            'link' => '#',
            'detaillevel' => 'logo',
            'mod'        => '6',
            'partners_only' => false,
	        'pref_cs' => []

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

    /**
     * call back will fetch data from API
     * will load Html file and embed it with theme
     * will replace content with specifically relates to supplier
     *
     * @param string $supplier
     * @param bool $returnResult
     * @return array
     */
    public function suppliersCallback( $supplier="", $returnResult = false, $forceCallToCompare = false )
    {
    	if(empty($supplier)) {
		    $supplier = $this->getUriSegment(2);
	    }

        $lang = $this->getLanguage();

        /*$getSupplier = $this->anbApi->getSuppliers(
            [
                'pref_cs' => $supplier,
                'lang'    => $lang
            ]
        );*/
        $getSupplier = $this->getSuppliers([
	        'pref_cs' => $supplier,
	        'lang'    => $lang
        ]);

        $params = [
            'pref_cs'     => [$getSupplier[0]['supplier_id']],
            'lang'        => $lang,
            'cat'         => $this->productTypes,
            'detaillevel' => ['reviews']
        ];

        /*if (count($_GET['cat']) >= 2) {
            sort($_GET['cat']);
            $params['cp'] = getPacktypeOnCats($_GET['cat']);
            //$params['cat'] = 'packs';
        } else {
            if (is_array($_GET['cat'])) {
                $params['cat'] = (is_array($_GET['cat'])) ? $_GET['cat'][0] : $_GET['cat'];
            }
        }*/

        $directProductCall = false;
        $prefCs = $params['pref_cs'];
        if(isset($_GET['searchSubmit'])) {
            $nou = (isset($_GET['exc_night_meter']) && $_GET['exc_night_meter'] == 1)? $_GET['nou'] : '';
            $du = $_GET['du'];
            if(isset($_GET['meter']) && $_GET['meter'] == 'double') {
                $nu = $_GET['nu'];
            }
            $has_solar = (isset($_GET['has_solar']) && $_GET['has_solar'] == 1)? $_GET['has_solar'] : '';
            $params['du'] = $du;
            $params['nu'] = $nu;
            $params['nou'] = $nou;
            $params['sg'] = $_GET['sg'];
            $params['s'] = 1;
        } else {
            //make direct getProducts call to grab all combinations
            $directProductCall = true;
            unset($params['pref_cs']);

            $params['sid'] = $getSupplier[0]['supplier_id'];
            if($forceCallToCompare) {
            	unset($params['sid']);
	            $params['pref_cs'] = $prefCs;//compare doesn't use sid, that is used by product api
            }
        }

        /** @var AnbCompare $anbComp */
        $anbComp = wpal_create_instance(AnbCompare::class);

        /** @var AnbProduct $anbProduct */
        $anbProduct = wpal_create_instance(AnbProduct::class);

        $getProducts = (!$directProductCall || $forceCallToCompare) ? json_decode($anbComp->getCompareResults($params), true)['results'] : json_decode($anbProduct->getProducts($params), true);

        if(session_id() == '') {
            session_start();
        }

        if($returnResult === false) {
	        $_SESSION['supplierData'] = $getSupplier;
	        $_SESSION['supplierProducts'] = $getProducts;
            unset($getSupplier);
            unset($getProducts);
        } else {
        	return [$getSupplier, $getProducts];
        }
    }

	public function supplierDetailsWithProducts( $supplier="", $supplierDetailParams = [] )
	{
		if(empty($supplier)) {
			$supplier = $this->getUriSegment(2);
		}

		$lang = getLanguage();
		$supplierDetailParams += ['lang' => $lang];

		return $this->getSupplierDetail($supplier, $supplierDetailParams);
	}

	/**
	 * @param $atts
	 *
	 * @return mixed
	 */
	protected function processMultipleProductCats( $atts ) {
		$atts = $this->catsToArray($atts);

		return $atts;
	}

	/**
	 * @param $atts
	 * @param $supplier
	 *
	 * @return mixed
	 */
	protected function generateProviderLink( $atts, $supplier ) {
		// poly lang exists
		$link = '';
		$sector = '';
		if ( $supplier['services'] ) {
			$sector = getSectorOnCats( $supplier['services'] );
		}
		$link = ((function_exists( 'pll_home_url' )) ? pll_home_url() : get_home_url()) . $sector . '/' . pll__( 'brands' ) . '/' . $supplier['slug'];

		return $link;
	}

	/**
	 * @param $atts
	 *
	 * @return mixed
	 */
	public function catsToArray( $atts ) {
		if ( $atts['cat'] && ! empty( trim( $atts['cat'] ) ) && is_string($atts['cat']) ) {
			//handling both space coma and without space comma
			$atts['cat'] = explode( ',', $atts['cat'] );
			$atts['cat'] = array_map( 'trim', $atts['cat'] );//triming each value
		}

		return $atts;
	}

	/**
	 * @param $product
	 *
	 * @return array
	 */
	protected function extractFeeFromProduct( $product ) {
		$sector = getSectorOnProducttype( $product['producttype'] );

		if($sector == pll__('energy')) {
			$value  = $product['pricing']['monthly']['promo_price'];
			$unit   = getCurrencySymbol($this->getLanguage());
		} else {
			$value  = $product['monthly_fee']['value'];
			$unit   = $product['monthly_fee']['unit'];
		}

		return array( $value, $unit );
	}

	/**
     * @param $product
     * @return string
     */
    protected function determineProductPackType ($product)
    {
        $countProducts = count($product['packtypes']);
        $productNames = array_keys($product['packtypes']);

        if ($countProducts) {
            return implode(" + ", $productNames);
        }
    }

    /**
     * @param null $productData
     * @return array
     */
    public function prepareSupplierProducts($productData = null) {
        $supplierProducts = $productData;

    	if(empty($productData)) {
		    $supplierProducts = $_SESSION['supplierProducts'];
	    }

        $listProducts = [];
        $temp = $packTemp = 0;

        foreach ($supplierProducts as $product) {
        	$pricing = [];
        	if(isset($product['pricing'])) {
        		$pricing = $product['pricing'];
	        }
            $product = (isset($product['product'])) ? $product['product'] : $product;
        	$product['pricing'] = $pricing;//associating pricing information to the product

            if ($product['producttype'] == 'packs' && array_key_exists('packtype', $product) ) {
                // Overwrite product type for accuracy to data set on the behalf of provided group of services
                $product['packtype'] =  $this->determineProductPackType($product);

            }

            if (!isset($listProducts[$product['producttype']])) {
                $listProducts[$product['producttype']] = [];
                $listProducts[$product['producttype']]['count'] = 0;
                $i = 0;
            }

            if ($product['producttype'] == 'packs' &&
                !isset($listProducts[$product['producttype']][$product['segment']][$product['packtype']])) {
                $listProducts[$product['producttype']][$product['segment']][$product['packtype']] = [];
                $listProducts[$product['producttype']][$product['segment']][$product['packtype']]['count'] = 0;
                $subPack = 0;
            }

            if (array_key_exists('packtype', $product)) {
                //check if this is first loop then assign first value as $min
                if ($subPack == 0) {
                    $listProducts[$product['producttype']][$product['segment']][$product['packtype']]['fee'] = $packTemp = $product['monthly_fee']['value'];
                    $listProducts[$product['producttype']][$product['segment']][$product['packtype']]['unit'] = $product['monthly_fee']['unit'];
                }
                //if this is not first loop then go in this if
                if ((int)$subPack > 0) {
                    //check if this value of array is lesser than $packTemp
                    if ((int)$product['monthly_fee']['value'] < (int)$packTemp) {
                        $listProducts[$product['producttype']][$product['segment']][$product['packtype']]['fee'] = $product['monthly_fee']['value'];
                        $listProducts[$product['producttype']][$product['segment']][$product['packtype']]['unit'] = $product['monthly_fee']['unit'];
                    }
                }

                if (isset($listProducts[$product['producttype']][$product['segment']][$product['packtype']]['count'])) {
                    $listProducts[$product['producttype']][$product['segment']][$product['packtype']]['count']++;
                    $subPack++;
                }
            }

            // Don't check fee for the top level packs
            if ($product['producttype'] != 'packs' ) {

                if (!isset($listProducts[$product['producttype']][$product['segment']])) {
                    $listProducts[$product['producttype']][$product['segment']] = [];
                    $listProducts[$product['producttype']][$product['segment']]['count'] = 0;
                    $otherOffers = 0;
                }

                //check if this is first loop then assign first value as $min
                if ($otherOffers == 0) {
	                list( $value, $unit ) = $this->extractFeeFromProduct( $product );
                    $listProducts[$product['producttype']][$product['segment']]['fee'] = $temp = $value;
                    $listProducts[$product['producttype']][$product['segment']]['unit'] = $unit;
                }

                //if this is not first loop then go in this if
                if ($otherOffers > 0) {

                    //check if this value of array is lesser than $temp
                    if ((int)$product['monthly_fee']['value'] < (int)$temp) {
	                    list( $newValue, $newUnit ) = $this->extractFeeFromProduct( $product );
                        $listProducts[$product['producttype']][$product['segment']]['fee'] = $newValue;
                        $listProducts[$product['producttype']][$product['segment']]['unit'] = $newUnit;
                    }
                }

                if (isset($listProducts[$product['producttype']][$product['segment']]['count'])) {
                    $listProducts[$product['producttype']][$product['segment']]['count']++;
                    $otherOffers++;
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

	        'electricity' => 'electricity',
	        'dualfuel_pack' => 'dualfuel_pack',
	        'gas' => 'gas'
        ];

        $html = '<ul class="list-unstyled list-inline">';

        // Alphabetically sort array to services consistent
        sort($provider['services']);

        foreach ($provider['services'] as $key => $service) {
            if (in_array($service, $checkFor)) {
	            if($service == 'dualfuel_pack') {//only display dualfuel pack no need to display gas and electricity here
		            $html .= '<li service="' . $service . '"> <i class="service-icons white ' . array_search($service, $checkFor) . '"></i> </li>';
		            break;
	            }
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
	    $sector = ($this->getUriSegment(3)) ? $this->getUriSegment(1) : pll__('telecom');
        // get supplier slug from url
	    if(!isset($atts['pref_cs'])) {
		    $supplier = $this->getUriSegment(3);
	    } else {
	    	$supplier = $atts['pref_cs'];
	    }

        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);

	    $defaultProductTypes = ($sector == pll__('energy')) ? $this->productTypesEnergy : $this->productTypes;

	    $params = [
		    'lang' => $this->getLanguage(),
		    'cat' => $defaultProductTypes,
		    'pref_cs' => $supplier,
		    'limit' => '1',
		    'html'  => true,
		    'mark-up' => 'div'
	    ];

	    $atts = $this->catsToArray($atts);

        // override default attributes with user attributes
	    return shortcode_atts($params, $atts, 'anb_supplier_reviews');
    }

    /**
     * @param $atts
     * @return null
     */
    public function getReviews($atts = [], $enableCache = true, $cacheDurationSeconds = 14400)
    {
        if(defined('REVIEW_API_CACHE_DURATION')) {
            $cacheDurationSeconds = REVIEW_API_CACHE_DURATION;
        }

        $atts = $this->prepareReviewShortCodeParams($atts);

        $reviews = null;

        //generate key from params to store in cache
        displayParams($atts);
        $start = getStartTime();
        $displayText = "Time API (Reviews) inside getReviews";
        if ($enableCache && !isset($_GET['no_cache'])) {
            $cacheKey = md5(serialize($atts)) . ":getReviews";
            $reviews = mycache_get($cacheKey);

            if($suppliers === false || empty($reviews)) {
                $reviews = json_encode($this->anbApi->getReviews(
                    $atts
                ));

	            mycache_set($cacheKey, $reviews, $cacheDurationSeconds);
            } else {
                $displayText = "Time API Cached (Reviews) inside getReviews";
            }
        } else {
            $reviews = json_encode($this->anbApi->getReviews(
                $atts
            ));
        }

        if (!$reviews) {
            $reviews = json_encode($this->anbApi->getReviews(
                $atts
            ));
        }

        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);

	    return ($reviews) ? json_decode($reviews, true) : $reviews;
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

            $truncate = false;

            foreach ($reviews as $review){

                $string = $review['texts']['contents'];
                if (strlen($string) > 300) {

                    // truncate string
                    $stringCut = substr($review['texts']['contents'], 0, 300);

                    // make sure it ends in a word so assassinate doesn't become ass...
                    $string = substr($stringCut, 0, strrpos($stringCut, ' ')).'...';
                }

                if (strlen($string) > 220) {
                    $truncate = true;
                }

                $html .= "<div class='col-md-5 infoPanel'>
                            <h6>".$review['texts']['title']."</h6>
                            <p". (($truncate)? " class='truncate'":" "   ).'>'.$string."</p>
                            ".(($truncate)? "<a href='#' class='readMore'><i class='fa fa-chevron-right'></i> Read more</a>":" ")."
                           
                            <p class='infoStamp'>".date("d/m/Y, H:i", strtotime($review['date'])).' - '.$review['author'].', '.$review['city']."</p>
                            <!--<a href='#'><i class='fa fa-thumbs-o-up'></i>Is this useful?</a>-->
                        </div>
                        <div class='col-md-3 ratingPanel'>
                            <div class='row header'>
                                <div class='col-xs-8 ratingTitle'>".pll__('Total Rating')."</div>
                                <div class='col-xs-4 countTitle'>".displayReviewRating($review['score'])."</div>
                            </div>
                            ".$this->fetchReviewRatings($review['ratings'])."
                      </div>";
            }
        }

        return $html;
    }

    /**
     * @param array $atts
     * @return array
     */
    public function getAllReviews($atts = [])
    {
        if (empty($atts)) {
            $atts = [
                'limit' => '',
                'mark-up' => 'li'
            ];
        }

	    $atts = $this->prepareReviewShortCodeParams($atts);
        $reviews = null;

        if (!$reviews) {
            $reviews = $this->getReviews(
                $atts
            );
        }

        return array($reviews, $atts);
    }

    /**
     * @param $atts
     * @return string
     */
    public function showAllReviews($atts)
    {
       list($reviews, $atts) = $this->getAllReviews($atts);

        $html = ' ';

        if ($reviews) {

            foreach ($reviews as $review){

                $string = $review['texts']['contents'];
                if (strlen($string) > 300) {

                    // truncate string
                    $stringCut = substr($review['texts']['contents'], 0, 300);

                    // make sure it ends in a word so assassinate doesn't become ass...
                    $string = substr($stringCut, 0, strrpos($stringCut, ' ')).'...';
                }

                $html .= '<' .$atts['mark-up'] . '>';
                $html .= '<div class="col-md-8 infoPanel">
                            <h6>'.$review['texts']['title'].'</h6>
                            <p>'.$string.'</p>
                            <p class="infoStamp">'.date("Y/m/d, H:i", strtotime($review['date'])).' - '.$review['author'].', '.$review['city'].'</p>
                            <!--<a href="#"><i class="fa fa-thumbs-o-up"></i>Is this useful?</a>-->
                        </div>
                        <div class="col-md-4 ratingPanel">
                            <div class="row header">
                                <div class="col-xs-8 ratingTitle">'.pll__('Total Rating').'</div>
                                <div class="col-xs-4 countTitle circled"><span>'.displayReviewRating($review['score']).'</span></div>
                            </div>
                            '.$this->fetchReviewRatings($review['ratings']).'
                      </div>';
                $html .= '</' .$atts['mark-up'] . '>';
            }
        }

        if ($atts['mark-up'] == 'li') {
            return '<ul class="list-unstyled clearfix">'.
            $html;
            '</ul>';
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
                <div class="col-xs-4 ratingCount">' . displayReviewRating($reviewRating['score']) . '</div>
            </div>';
            }
        }

        return $html;
    }

    /**
     * @return string
     */
    public function suppliersForResultFilters($atts = [])
    {
        $atts['sort-by'] = 'is_partner';

        $html = '';
        $nonPartner = false;

        $atts = $this->prepareShortCodeAttributes($atts);

	    $queryParams = $this->getUriQuery() + $atts;

	    $selectedProviders = (!empty($queryParams) && isset($queryParams['pref_cs'])? $queryParams['pref_cs']: [] ) ;

	    if(empty($selectedProviders)){
            $selectedProviders = $_GET['pref_cs'];
        }

	    //now its time to unset pref_cs because we want to fetch all suppliers
	    unset($atts['pref_cs']);

        $getLogos = $this->getSupplierLogos($atts);
        $supplierSorted = $this->sortSupplier(
            $getLogos,
            $atts,
            SORT_DESC
        );

        foreach ($supplierSorted as $supplier) {

            if ($supplier['is_partner'] == 0  && $nonPartner == 0) {
                $nonPartner = true;
                $html .= '<div class="moreFilterWrapper moreNonPartners"><a>'.pll__('+ more').'</a></div>';
                $html .= '<div class="NonPartnersResult">';
            }

            $checked = (in_array($supplier['id'], $selectedProviders ) ? 'checked' : '');

            $html .= '<div class="checkbox fancyCheck small">
                        <input type="checkbox"  name="pref_cs[]" '.$checked.'  value="' . $supplier['id'] . '" id="' . $supplier['name'] . '">
                        <label for="' . $supplier['name'] . '">
                            <i class="unchecked"></i>
                            <i class="checked"></i>
                            <span>' . $supplier['name'] . '</span>
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
	 * @param int $selectedId
	 * @param array $atts
	 *
	 * @return string
	 */
    public function supplierAsOptionsForDropDown($selectedId = null, $atts = [])
    {
        $html = '';

        list($atts, $supplierSorted) = $this->preparedSuppliersLogoData($atts);

        foreach ($supplierSorted as $supplier) {
        	$selected = '';
        	if(!empty($selectedId) && $selectedId == $supplier['id']) {
        		$selected = 'selected';
	        }

            $html .= '<option ' . $selected . ' value="' . $supplier['id'] . '">' . $supplier['name'] . '</option>';
        }

        return $html;
    }

    /**
     * @param null $minimumPrices
     * @param array $atts
     * @return string
     */
    public function suppliersForWizard($minimumPrices = null ,$atts = [])
    {
        $html = '';
        $segment = isset($_GET['sg']) ? $_GET['sg'] : 'consumer';

        $atts['partners_only'] = true;

        list($atts, $supplierSorted) = $this->preparedSuppliersLogoData($atts);

        foreach ($supplierSorted as $supplier) {

            $minimumPriceString =  "<span class='offer supplier-offer-{$supplier['id']}'>".pll__( 'No offers in your area' )."</span>";
            $noOffer = true;
            if ($minimumPrices && array_key_exists($supplier['id'], $minimumPrices)) {
                $noOffer = false;
                $minimumPriceString =  "<span class='offer supplier-offer-{$supplier['id']}'>".pll__( 'offers starting from' )." " .
                                       formatPrice($minimumPrices[$supplier['id']]['price'], 2, getCurrencySymbol($minimumPrices[$supplier['id']]['unit'])) .
                                       "</span>";
            }
            $noOfferClass = '';
            $checked = 'checked';
            $disabled = '';
            if($noOffer) {
                $checked = '';
                $disabled = 'disabled';
                $noOfferClass = 'no-offer';
            }

            $html .= "<li  id='li-supplier-box-{$supplier['id']}'>
                        <input type='checkbox' class='$noOfferClass' $checked $disabled name='pref_cs[]' id='{$supplier['name']}' value='{$supplier['id']}'>
                        <label for='{$supplier['name']}'>
                            <img src='{$supplier['logo']}' alt='{$supplier['name']}' class='logo'>
                            <span class='providerName'>{$supplier['name']}</span>
                            {$minimumPriceString}
                            <i class='fa fa-check'></i>
                        </label>
                    </li>";
        }

        if ($html) {
            $html = "<ul class='list-unstyled logoCheckBoxComp col-4 wizardSupplierOptions clearfix'>" . $html . "</ul>";
        }

        return $html;
    }

    /**
     * @param $isPartner
     * @return mixed
     */
    public function getSupplierIds($isPartner)
    {
        $ids = [];

        if ($isPartner) {
            $atts['partners_only'] = true;
        }


        list($atts, $supplierSorted) = $this->preparedSuppliersLogoData($atts);


        foreach ($supplierSorted as $supplier) {
          $ids[] = $supplier['id'];
        }

        return $ids;
    }

    /**
     * @param $id
     * @return string
     */
    public function getSupplierNameById($id, $atts = [])
    {
        $name = '';

        list($atts, $supplierSorted) = $this->preparedSuppliersLogoData($atts);

        foreach ($supplierSorted as $supplier) {

            if ($id == $supplier['id']) {
                $name = $supplier['name'];
                break;
            }
        }

        return $name;
    }

    public function registerStringsForLocalization ()
    {
        pll_register_string('abSuppliers', 'brands', 'Suppliers', true);
    }

    /**
     * @param $supplier
     * @param $segment
     * @param $resultMin
     * @return mixed
     */
    private function getMinimumPriceForSupplier($supplier, $segment, $resultMin)
    {
        $getProducts = $this->anbApi->getProducts(
            [
                'sid' => $supplier['id'], //'102',//
                'lang' => $this->getLanguage(),
                'cat' => $this->productTypes,
                'detaillevel' => ['reviews']
            ]
        );

        $supplierProducts = $this->prepareSupplierProducts($getProducts);

        if ($supplierProducts && array_key_exists('packs', $supplierProducts) && isset($supplierProducts['packs'][$segment])) {
            foreach ($supplierProducts['packs'][$segment] as $key => $value) {
                if (array_key_exists('fee', $value)) {
                    $resultMin[$key] = (float)$value['fee'];
                }
            }

            $resultMin = min($resultMin);
            return $resultMin;
        } elseif (isset($supplierProducts['internet'][$segment])) {

            $resultMin = $supplierProducts['internet'][$segment]['fee'];
            return $resultMin;
        }
        return $resultMin;
    }

    public function getSupplierDetail($supplierId, $params = [], $enableCache = true, $cacheDurationSeconds = 14400) {
	    if(defined('SUPPLIER_API_CACHE_DURATION')) {
		    $cacheDurationSeconds = SUPPLIER_API_CACHE_DURATION;
	    }

	    $supplier = null;

	    //generate key from params to store in cache
	    $displayText = "API Call (getSupplierDetail) inside getSupplierDetail";
	    $start = getStartTime();

	    //remove empty values
	    $params = array_filter($params);

	    if ($enableCache && !isset($_GET['no_cache'])) {
		    $cacheKey = md5($supplierId . serialize($params)) . ":getSupplierDetail";
		    $supplier = mycache_get($cacheKey);

		    if($supplier === false || empty($supplier)) {
			    $supplier = json_encode($this->anbApi->getSupplierDetail( $supplierId, $params ));
			    mycache_set($cacheKey, $supplier, $cacheDurationSeconds);
		    } else {
			    $displayText = "API Call Cached (getSupplierDetail) inside getSupplierDetail";
		    }
	    } else {
		    $supplier = json_encode($this->anbApi->getSupplierDetail($supplierId, $params));
	    }

	    $finish = getEndTime();
	    displayCallTime($start, $finish, $displayText);

	    return ($supplier) ? json_decode($supplier, true) : $supplier;
    }

    //echo do_shortcode('[anb_suppliers mark-up="div" mark-up-class="col-sm-2 serviceProvider" lang="nl" segments="sme" products="internet" mod="6"]'); />
}
