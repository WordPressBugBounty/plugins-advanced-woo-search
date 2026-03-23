<?php
/**
 * Divi 5 module bootstrap.
 */

namespace AWS\Divi5\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

require_once __DIR__ . '/AwsSearch/AwsSearch.php';

use AWS\Divi5\Modules\AwsSearch\AwsSearch;

/**
 * Register AWS Divi 5 module dependency.
 *
 * @param object $dependency_tree Dependency tree object.
 * @return void
 */
function aws_pro_divi5_register_modules( $dependency_tree ) {
	$dependency_tree->add_dependency( new AwsSearch() );
}

add_action( 'divi_module_library_modules_dependency_tree', __NAMESPACE__ . '\\aws_pro_divi5_register_modules' );
