<?php

namespace abSuppliers;

use WP_Router;

/**
 * Class Helper
 * @package abSuppliers
 */
trait Helper {

    /**
     * @return array
     */
    public function getUriSegments()
    {
        return explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    }

    /**
     * @param $n
     * @return mixed|string
     */
    public function getUriSegment($n)
    {
        $segments = $this->getUriSegments();
        return isset($segments[$n]) ? $segments[$n] : '';
    }

    /**
     * @return array
     */
    public function getUriQuery()
    {
        parse_str(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY),
            $output
        );

        return $output;
    }

    /**
     * @return bool|string
     */
    public function getLanguage()
    {
        return function_exists('pll_current_language') ? pll_current_language() : Locale::getPrimaryLanguage(get_locale());
    }

	public function generateRoutes( WP_Router $router )
	{
		$startTime = 0;
		$supplier = $this->getUriSegment(2);
		if($this->getUriSegment(3)) {
			$supplier = $this->getUriSegment(3);
		}
		if(isset($_GET['pref_cs'][0]) && $_GET['pref_cs'][0] > 0) {
            $supplier = $_GET['pref_cs'][0];
        }

		if(function_exists('getStartTime')) {
			$startTime = getStartTime();
		}

		$router->add_route('anb_route_product_new', array(
			'path' => '^'.pll__('telecom').'/'. pll__('brands').'/(.+?)/((?!'. pll__('results').').)+/?$',
			'query_vars' => ['sid' => $this->getUriSegment(3), 'productid' => $this->getUriSegment(4), 'startScriptTime' => $startTime, 'via_route' => true],
			'page_callback' => [$this, 'emptyCallback'],
			'page_arguments' =>  [],
			'title_callback' => [$this, 'emptyCallback'],
			'title_arguments' => [$this->getUriSegment(3), $this->getUriSegment(4)],
			'access_callback' => true,
			'title' => __( '' ),
			'template' => array(
				'anb-product.php',
				get_template_directory() . 'anb-product.php'
			)
		));

		$router->add_route('anb_route_product_energy', array(
			'path' => '^'.pll__('energy').'/'.pll__('brands').'/(.+?)/((?!'. pll__('results').').)+/?$',
			'query_vars' => ['sid' => $this->getUriSegment(3), 'productid' => $this->getUriSegment(4), 'startScriptTime' => $startTime, 'via_route' => true],
			'page_callback' => [$this, 'emptyCallback'],
			'page_arguments' =>  [],
			'title_callback' => [$this, 'emptyCallback'],
			'title_arguments' => [$this->getUriSegment(3), $this->getUriSegment(4)],
			'access_callback' => true,
			'title' => __( '' ),
			'template' => array(
				'anb-product-energy.php',
				get_template_directory() . 'anb-product-energy.php'
			)
		));

		$router->add_route('anb_route_brand_details', array(
			'path' => '^' . pll__('telecom') . '/' . pll__('brands').'/(.*?)$',
			'query_vars' => ['startScriptTime' => $startTime, 'supplier' => $supplier, 'via_route' => true],
			'page_callback' => array($this, 'suppliersCallback'),
			'page_arguments' =>  [],
			'access_callback' => TRUE,
			'title' => __( '' ),
			'template' => array(
				'page-provider-details.php',
				get_template_directory() . 'page-provider-details.php'
			)
		));

		$router->add_route('anb_route_brand_details_energy', array(
			'path' => '^' . pll__('energy') . '/' . pll__('brands').'/(.*?)$',
			'query_vars' => ['startScriptTime' => $startTime, 'supplier' => $supplier, 'via_route' => true],
			'page_callback' => array($this, 'suppliersCallback'),
			'page_arguments' =>  [],
			'access_callback' => TRUE,
			'title' => __( '' ),
			'template' => array(
				'page-provider-details-energy.php',
				get_template_directory() . 'page-provider-details-energy.php'
			)
		));

        //Same as the previous route, just allowing /brands/<provider>/results as valid link as well, currently its invalid
        $router->add_route('anb_route_brand_results', array(
            'path' => '^'.pll__('telecom').'/'.pll__('brands').'/(.+?)/'. pll__('results').'/?$',
            'query_vars' => ['startScriptTime' => $startTime, 'supplier' => $supplier, 'via_route' => true],
            'page_callback' => array($this, 'suppliersCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'page-provider-details.php',
                get_template_directory() . 'page-provider-details.php'
            )
        ));

		$router->add_route('anb_route_brand_results_energy', array(
			'path' => '^'.pll__('energy').'/'. pll__('brands').'/(.+?)/'. pll__('results').'/?$',
			'query_vars' => ['startScriptTime' => $startTime, 'supplier' => $supplier, 'via_route' => true],
			'page_callback' => array($this, 'suppliersCallback'),
			'page_arguments' =>  [],
			'access_callback' => TRUE,
			'title' => __( '' ),
			'template' => array(
				'page-provider-details.php',
				get_template_directory() . 'page-provider-details-energy.php'
			)
		));

        $router->add_route('anb_route_faq_answer_telecom', array(
            'path' => '^'.pll__('telecom').'/'. pll__('faqs').'/(.*?)/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faq-terms-answer.php',
                get_template_directory() . 'faq-terms-answer.php'
            )
        ));

        $router->add_route('anb_route_faq_answer_energie', array(
            'path' => '^'.pll__('energy').'/'. pll__('faqs').'/(.*?)/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faq-terms-answer.php',
                get_template_directory() . 'faq-terms-answer.php'
            )
        ));

        $router->add_route('anb_route_faq_answer_mobile_belle_data', array(
            'path' => '^mobiel-bellen-data/'. pll__('faqs').'/(.*?)/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faq-terms-answer.php',
                get_template_directory() . 'faq-terms-answer.php'
            )
        ));

        $router->add_route('anb_route_faq_terms_telecom', array(
            'path' => '^'.pll__('telecom').'/'. pll__('faqs').'/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faqs-terms.php',
                get_template_directory() . 'faqs-terms.php'
            )
        ));

        $router->add_route('anb_route_faq_terms_energie', array(
            'path' => '^'.pll__('energy').'/'. pll__('faqs').'/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faqs-terms.php',
                get_template_directory() . 'faqs-terms.php'
            )
        ));

        $router->add_route('anb_route_faq_terms_mobile_belle_data', array(
            'path' => '^mobiel-bellen-data/'. pll__('faqs').'/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faqs-terms.php',
                get_template_directory() . 'faqs-terms.php'
            )
        ));

        $router->add_route('anb_route_files_answer_telecom', array(
            'path' => '^'.pll__('telecom').'/'. pll__('files').'/(.*?)/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faq-terms-answer.php',
                get_template_directory() . 'faq-terms-answer.php'
            )
        ));

        $router->add_route('anb_route_files_answer_energie', array(
            'path' => '^'.pll__('energy').'/'. pll__('files').'/(.*?)/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faq-terms-answer.php',
                get_template_directory() . 'faq-terms-answer.php'
            )
        ));

        $router->add_route('anb_route_files_answer_mobile_belle_data', array(
            'path' => '^mobiel-bellen-data/'. pll__('files').'/(.*?)/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faq-terms-answer.php',
                get_template_directory() . 'faq-terms-answer.php'
            )
        ));

        $router->add_route('anb_route_files_terms_telecom', array(
            'path' => '^'.pll__('telecom').'/'. pll__('files').'/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faqs-terms.php',
                get_template_directory() . 'faqs-terms.php'
            )
        ));

        $router->add_route('anb_route_files_terms_energie', array(
            'path' => '^'.pll__('energy').'/'. pll__('files').'/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faqs-terms.php',
                get_template_directory() . 'faqs-terms.php'
            )
        ));

        $router->add_route('anb_route_files_terms_mobile_belle_data', array(
            'path' => '^mobiel-bellen-data/'. pll__('files').'/(.*?)$',
            'query_vars' => ['startScriptTime' => $startTime, 'via_route' => true],
            'page_callback' => array($this, 'emptyCallback'),
            'page_arguments' =>  [],
            'access_callback' => TRUE,
            'title' => __( '' ),
            'template' => array(
                'faqs-terms.php',
                get_template_directory() . 'faqs-terms.php'
            )
        ));

        $router->add_route('anb_sitemap_products', array(
            'path' => '^sitemap_products.xml',
            'query_vars' => ['startScriptTime' => $startTime,'via_route' => true],
            'page_callback' => [],
            'page_arguments' =>  [],
            'title_callback' => [],
            'title_arguments' => [],
            'access_callback' => true,
            'title' => __( '' ),
            'template' => array(
                'sitemaps/products.php',
                get_template_directory() . 'sitemaps/products.php'
            )
        ));


        //echo '<pre>'.print_r($router, true).'</pre>';
	}

    /**
     * an empty call back
     * @return string
     */
    public function emptyCallback()
    {
        return '';
	}

	/**
	 * @return mixed
	 */
	public function productTitleCallback($sid, $productid)
    {
		/** @var \AnbTopDeals\AnbProduct $anbPrd */
		$anbPrd = wpal_create_instance( \AnbTopDeals\AnbProduct::class );

		/** @var \AnbSearch\AnbCompare $anbComp */
		$anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );

		$result      = $anbPrd->getProducts(
			[
				'sid'         => $sid,
				'detaillevel' => [
					'just_get_core_info'
				],
				'lang'        => $anbComp->getCurrentLang()
			],
            $productid );
		$product     = json_decode( $result )[0];
		$productName = $product->product_name;
		unset($product);
        unset($result);
		return $productName;
	}

    /**
     * @param $price
     * @return mixed
     */
    public function priceDotToCommaConversion ($price)
    {
        return str_replace( '.', ',', $price);
    }

}
