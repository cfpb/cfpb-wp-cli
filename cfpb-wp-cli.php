<?php
/*
 * Plugin Name: CFPB WP CLI Commands
 * Plugin URI: http://github.com/CFPB/cfpb-wp-cli
 * Description: A collection of wp-cli plugins we use at CFPB
 * Version: 1.0
 * Author: Greg Boone
 * Author URI: greg.harmsboone.org
 * License: Public Domain Work of the Federal Government
 */

if ( defined('WP_CLI') && WP_CLI ) {
    include __DIR__ . '/migrate.php';
}
?>
