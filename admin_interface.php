<?php

/**
 * Provide array $im_template_files for insert webim_template
 * Provide array $im_template_cache_dir for clear template cache
 *
 */

$im_template_cache_dir = dirname( dirname( __FILE__) ) . '/data/tpl_cache';
$im_template_files = array();
$template_dir = dirname( dirname( __FILE__) ) . '/template';
foreach( webim_scan_subdir( $template_dir ) as $k => $v ) {
	$d = $template_dir.DIRECTORY_SEPARATOR.$v;
	$f = $d.DIRECTORY_SEPARATOR.'footer.htm';
	if( file_exists( $f ) ){
		$im_template_files[] = $f;
	}
}

unset( $template_dir );

