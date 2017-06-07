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


include_once(WP_PLUGIN_DIR . "/wp-autoload/wpal-autoload.php" );
// If this file is accessed directory, then abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

$supplier = wpal_create_instance(abSuppliers::class);
add_shortcode( 'anb_suppliers', array( $supplier, 'prepareSuppliersForFrontEnd' ) );
add_shortcode( 'anb_suppliers_count', array( $supplier, 'countSuppliersLogo' ) );