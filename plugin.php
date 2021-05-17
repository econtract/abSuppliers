<?php
/*
Plugin Name: Aanbieders Suppliers
Depends: Wp Autoload with Namespaces, Aanbieders Api Client
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A plugin to load files from Aanbieders econtract API.
Version: 1.0.0
Author: Arslan Hamee <arslan.hameed@zeropoint.it>
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

namespace abSuppliers;


include_once(WP_PLUGIN_DIR . "/wpal-autoload/wpal-autoload.php" );
// If this file is accessed directory, then abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

$supplier = wpal_create_instance(AbSuppliers::class);
add_shortcode( 'anb_suppliers_count', array( $supplier, 'countSuppliersLogo' ) );
add_shortcode( 'anb_suppliers_overview', array( $supplier, 'prepareSuppliersForOverview' ) );

add_shortcode('anb_supplier_partners', array($supplier, 'displayPartners'));
add_shortcode( 'anb_supplier_partners_footer', array( $supplier, 'displaySupplierPartnersForFooter' ) );

add_shortcode( 'anb_supplier_reviews', array( $supplier, 'showReviews' ) );
add_shortcode( 'anb_supplier_all_reviews', array( $supplier, 'showAllReviews' ) );
