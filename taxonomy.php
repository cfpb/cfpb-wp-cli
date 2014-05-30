<?php
class Migrate_Command extends WP_CLI_Command {

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
    *     wp migrate taxonomy tag group
    *
    * @synopsis <from> <to> --include=<bar> [--post_type=<foo>]
    * @todo [--post_type=<foo>] [--exclude=<bar>] [--after=<date>] [--before=<date>] [--terms=<terms>]
    *
    **/
    /**
    * @subcommand taxonomy
    * @alias m
    *
    **/
    public function taxonomy( $args, $assoc_args ) {
        if ( ! isset( $args[0] ) || ! isset( $args[1] ) ) {
            $message = "\nYou must specify taxonomies to migrate from and to. \n\n Example: wp migrate taxonomy category tag. \n\n";
            exit($message);
        }
        $from = $args[0];
        $to = $args[1];
        extract( $assoc_args );
        $message = "Will migrate all $from to $to";
        $args = array('posts_per_page' => -1);
        if ( $include != 'all' ) {
            $args['include'] = $include;
            $message .= " for post(s) {$include}.\n\n";
            print_r($message);
        }
        if ( isset( $post_type ) ) {
            $args['post_type'] = $post_type;
        } else {
            $args['post_type'] = 'post';
        }
        $posts = get_posts($args);

        $set = array();
        foreach ( $posts as $p ) {
            $terms = wp_get_post_terms( $p->ID, $from );
            foreach ( $terms as $t ) {
                $new_term = wp_insert_term( $t->name, $to, array( 'slug' => $t->slug ) );
                if ( $new_term instanceof WP_Error ) {
                    $new_term = get_term_by('slug', $t->slug, $to);
                    array_push($set, $new_term->slug);
                } else {
                    array_push($set, $new_term->term_id);
                }
            }
            print_r("Setting terms for {$to} on {$args['post_type']} #{$p->ID}\n");
            $new = wp_set_object_terms( $p->ID, $set, $to );
        }
        // var_dump($terms);
        // var_dump($new);
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

WP_CLI::add_command( 'migrate', 'Migrate_Command' );
