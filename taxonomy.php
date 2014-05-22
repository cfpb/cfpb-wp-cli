<?php
class Taxonomy_Command extends WP_CLI_Command {

    /**
    *
    * Manipulates taxonomies
    *
    * ## Options
    *
    * <from>
    * : The taxonomy to migrate from
    * <to>
    * : The taxonomy to migrate to
    *
    * ## Exmples
    *
    *     wp taxonomy migrate tag group
    *
    * @synopsis <from> <to> --include=<bar> [--post_type=<foo>]
    * @todo [--post_type=<foo>] [--exclude=<bar>] [--after=<date>] [--before=<date>] [--terms=<terms>]
    *
    **/
    /**
    * @subcommand migrate
    * @alias m
    *
    **/
    function migrate( $args, $assoc_args ) {
        $from = $args[0];
        $to = $args[1];
        extract( $assoc_args );
        $message = "Will migrate all $from to $to";
        $args = array('posts_per_page' => -1);
        if ( $include != 'all' ) {
            $args['include'] = $include;
        }
        $posts = get_posts($args);

        var_dump($posts);
        foreach ( $posts as $p ) {
            $terms = wp_get_post_terms( $p->ID, $from, array( 'fields' => 'slugs' ) );
            $new = wp_set_object_terms( $p->ID, $terms, $to, true);
        }
        var_dump($terms);
        var_dump($new);
        if ( isset( $post_type ) ) {
            $message .= " in the $post_type post type";
            exit('Unimplemented' );
        }
        if ( isset( $exclude ) ) {
            $message .= " except for posts $exclude";
            exit('Unimplemented' );
        }
        if ( isset( $after ) && isset( $before ) ) {
            $message .= " between $before and $after";
            exit('Unimplemented' );
        } elseif ( isset( $after ) ) {
            $message .= " after $after";
            exit('Unimplemented' );
        } elseif ( isset( $before ) ) {
            $message .= " before $before";
            exit('Unimplemented' );
        }
        if ( isset($terms) ) {
            $message .= " against only these terms: $terms";
            exit('Unimplemented' );
        }

        $message .= ".";
        WP_CLI::success( $message );
    }
}

WP_CLI::add_command( 'taxonomy', 'Taxonomy_Command' );
