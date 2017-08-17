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
		//$router->add_rewrite_rules(['^brands/(.+)/(.+)/?', 'index.php?pagename=brands/brand/product&sid=$matches[1]&productid=$matches[2]', 'top']);
		//add_rewrite_rule('^testing1/test1/?', 'index.php?pagename=merken', 'top');
		$router->add_route('anb_route_product', array(
			'path' => '^'. pll__('brands').'/(.+?)/(.+?)/?$',
			'query_vars' => ['sid' => $this->getUriSegment(2), 'productid' => $this->getUriSegment(3)],
			'page_callback' => [$this, 'emptyCallback'],
			'page_arguments' =>  [],
			'access_callback' => true,
			'title' => __( '' ),
			'template' => array(
				'anb-product.php',
				get_template_directory() . 'anb-product.php'
			)
		));

		$router->add_route('anb_route_brand', array(
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

		/*$router->add_rewrite_rules([
			'^'.pll__('brands').'/(.+)/?' => 'index.php?pagename='.pll__('brands').'/'.pll__('brand').'&sid=$matches[1]',
			'^'.pll__('brands').'/(.+)/(.+)/?' => 'index.php?pagename='.pll__('brands').'/'.pll__('brand').'/'.pll__('product').'&sid=$matches[1]&productid=$matches[2]'
		]);*/
		//add_rewrite_rule('^'.pll__('brands').'/(.+)/?', 'index.php?pagename='.pll__('brands').'/'.pll__('brand').'&sid=$matches[1]', 'top');
		//add_rewrite_rule('^'.pll__('brands').'/(.+)/(.+)/?', 'index.php?pagename='.pll__('brands').'/'.pll__('brand').'/'.pll__('product').'&sid=$matches[1]&productid=$matches[2]', 'top');

	}

	public function emptyCallback() {
    	return '';
	}

}
