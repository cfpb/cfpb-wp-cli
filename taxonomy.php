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
    * @synopsis <from> <to> [--post_type=<foo>] [--exclude=<bar>] [--after=<date>] [--before=<date>] [--terms=<terms>]
    *
    **/
    function migrate( $args, $assoc_args ) {
        $from = $args[0];
        $to = $args[1];
        extract( $assoc_args );
        $message = "Will migrate all $from to $to";
        if ( isset( $post_type ) ) {
            $message .= " in the $post_type post type";
        }
        if ( isset( $exclude ) ) {
            $message .= " except for posts $exclude";
        }
        if ( isset( $after ) && isset( $before ) ) {
            $message .= " between $before and $after";
        } elseif ( isset( $after ) ) {
            $message .= " after $after";
        } elseif ( isset( $before ) ) {
            $message .= " before $before";
        }
        if ( isset($terms) ) {
            $message .= " against only these terms: $terms";
        }

        $message .= ".";
        WP_CLI::success( $message );
        WP_CLI::success( "FYI: Nothing actually happened, this is just a prototype." );
    }
}

WP_CLI::add_command( 'taxonomy', 'Taxonomy_Command' );
