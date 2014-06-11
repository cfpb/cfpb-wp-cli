<?php
namespace CFPB;
/**
* Migrates meta data
*
* @subcommand taxonomy
*
**/
class Randomize_Command extends CLI_Common {
/**
    *
    * Assigns random terms to all posts in a taxonomy.
    *
    * By default all objects of the 'post' post type will be randomized, use the
    * --post_type flag to target pages or a custom post type. Use the --include 
    * and --exclude flags to filter or ignore specific object IDs and the --before
    * and --after flags to specify a date range. Also, optionally pass --terms as
    * a list of terms you want to use for the randomization. If terms exist in the
    * target taxonomy, those terms will be used. If not, a string of 6 words 
    * generated randomly will be used for the randomization.
    * 
    * ## Options
    *
    * <taxonomy>
    * : The taxonomy that should get randomized
    *
    * ## Exmples
    *
    *     wp randomize category
    *     
    * @synopsis <taxonomy> [--include=<bar>] [--exclude=<foo>] [--post_type=<foo>] 
    * [--before=<bar>] [--after=<date>] [--terms=<terms>]
    * 
    **/
	public function taxonomy($args, $assoc_args) {
		$taxonomy = $args[0];
		$get_posts = $this->get_specified_posts($assoc_args);
		$message = $get_posts['message'];
		$posts = $get_posts['posts'];
		$args = $get_posts['args'];
		$preamble = "Will assign random {$taxonomy} terms";
		print_r("{$preamble} {$message}.\n");
		if ( isset( $assoc_args['terms'] ) ) {
			$terms = explode(',', $assoc_args['terms']);
			\WP_CLI::log('Using terms ' . $assoc_args['terms']);
		} else {
			\WP_CLI::log('Gathering and processing random terms.');
			$terms = $this->get_random_terms();
			\WP_CLI::log('No term list given, using random terms.');
		}
		foreach ( $posts as $p ) {
			$index = array_rand($terms);
			$term = $terms[$index];
			\WP_CLI::log("Assigning {$term} to taxonomy {$taxonomy} for {$p->post_type} {$p->ID}");
			if ( ! term_exists( $term, $taxonomy ) ) {
				wp_insert_term( $term, $taxonomy );
			}
			wp_set_object_terms( $p->ID, $term, $taxonomy, $append = false );
		}
	}
}
\WP_CLI::add_command( 'randomize', '\CFPB\Randomize_Command' );