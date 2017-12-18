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
        $segment = $this->getUriSegments();
        return count($segment)>0&&count($segment)>=($n-1) ? $segment[$n] : '';
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
		$router->add_route('anb_route_product', array(
			'path' => '^'. pll__('brands').'/(.+?)/(.+?)/?$',
			'query_vars' => ['sid' => $this->getUriSegment(2), 'productid' => $this->getUriSegment(3), 'startScriptTime' => getStartTime()],
			'page_callback' => [$this, 'emptyCallback'],
			'page_arguments' =>  [],
			'title_callback' => [$this, 'productTitleCallback'],
			'title_arguments' => [$this->getUriSegment(2), $this->getUriSegment(3)],
			'access_callback' => true,
			'title' => __( '' ),
			'template' => array(
				'anb-product.php',
				get_template_directory() . 'anb-product.php'
			)
		));

		$router->add_route('anb_route_brand', array(
			'path' => '^'. pll__('brands').'/(.*?)$',
			'query_vars' => ['startScriptTime' => getStartTime()],
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
