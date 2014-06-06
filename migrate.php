<?php
/**
* Migrates meta data
*
* @subcommand taxonomy
*
**/
class Migrate_Command extends WP_CLI_Command {

    /**
    *
    * Migrates terms in one taxonomy to another. Useful for when you decide you want tags instead of categories.
    * 
    * ## Options
    *
    * <from>
    * : The taxonomy to migrate from
    * 
    * <to>
    * : The taxonomy to migrate to
    *
    * ## Exmples
    *
    *     wp migrate taxonomy tag group
    * @synopsis <from> <to> [--include=<bar>] [--exclude=<foo>] [--post_type=<foo>] [--before=<bar>] [--after=<date>]
    * 
    * @todo  [--terms=<terms>]
    * 
    **/
    protected function get_specified_posts($assoc_args) {
        extract($assoc_args);
        $args = array('posts_per_page' => -1);
        $include = isset($include) ? $include : 'all';
        if ( $include != 'all' ) {
            $args['include'] = $include;
            $message .= " for post(s) {$include}";
        }

        if ( isset( $exclude ) ) {
            $args['exclude'] = $exclude;
            $message .= " and excluding {$exclude}";
        }
        if ( isset( $post_type ) ) {
            $args['post_type'] = $post_type;
            $message .= " of the {$post_type} post type";
        } else {
            $args['post_type'] = 'post';
        }
        if ( isset($before) ) {
            $args['date_query'] = array(
                'before' => $before,
            );
            $message .= " published before {$before}";
        }
        if ( isset($after) ) {
            $args['date_query'] = array(
                'after' => $after,
            );
            if ( array_key_exists('before', $args['date_query']) ) {
                $message .= " and after {$after}";
            } else {
                $message .= " published after {$after}";
            }
        }
        // unimplemented stuff, keep this before get_posts, for now
        if ( isset($terms) ) {
            $message .= " against only these terms: $terms";
            exit('Unimplemented' );
        }

        // start the action!
        print_r("{$message}.\n");
        $posts = get_posts($args);
        return array( 
            'message' => $message, 
            'posts' => $posts 
        );
    }
    public function taxonomy( $args, $assoc_args ) {
        $from = $args[0];
        $to = $args[1];
        extract( $assoc_args );
        $preamble = "Will migrate all $from to $to";
        $get_posts = $this->get_specified_posts( $assoc_args );
        extract($get_posts);
        $message = "{$preamble} {$message}";

        $count = count($posts);
        $set = array();
        foreach ( $posts as $p ) {
            $terms = wp_get_post_terms( $p->ID, $from );
            foreach ( $terms as $t ) {
                $new_term = wp_insert_term( $t->name, $to, array( 'slug' => $t->slug ) );
                if ( $new_term instanceof WP_Error ) {
                    $new_term = get_term_by('slug', $t->slug, $to);
                    array_push($set, $new_term->slug);
                } else {
                    $added_term = get_term( $new_term['term_id'], $to, $output = OBJECT, $filter = 'raw' );
                    array_push($set, $added_term->slug);
                }
            }
            $message = "Setting terms for {$to} on {$args['post_type']} #{$p->ID}.\n";
            print_r($message);
            $new = wp_set_object_terms( $p->ID, $set, $to );
            // clear all those posts out of $set to tee up the next 
            $set = array();
        }
        $message .= "All {$from} successfully migrated to {$to} for {$count} {$args['post_type']}s. You did it!";
        WP_CLI::success( $message );
    }

    /**
    * @subcommand author
    *
    * Migrates author names to a taxonomy.
    *
    * ## Options
    * <type>
    * : Acceptable: taxonomy or custom_field (expects a taxonomy called Author or will use custom_field key "Author")
    * 
    * @synopsis <type> 
    * @todo  [--posts=<foo>] [post_types=<foo>] [--authors=<foo>] [--before=<foo>] [--after=<foo>]
    * 
    **/
    public function author( $args, $assoc_args ) {
        $type = $args[0];
        extract($assoc_args);
    }
}

WP_CLI::add_command( 'migrate', 'Migrate_Command' );
