<?php
namespace CFPB;
/**
* Migrates meta data
*
* @subcommand taxonomy
*
**/
class Migrate_Command extends CLI_Common {

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
    * [--ignore_term]
    * : Comma separate list of terms in <from> to ignore in the migration to <to>
    * ## Exmples
    *
    *     wp migrate taxonomy tag group
    * @synopsis <from> <to> [--include=<bar>] [--exclude=<foo>] [--post_type=<foo>] [--before=<bar>] [--after=<date>] [--ignore_term=<foo>]
    * 
    * @todo  [--terms=<terms>]
    * 
    **/
    public function taxonomy( $args, $assoc_args ) {
        if ( empty($args) ) {
            exit('Invalid entry.');
        }
        $from = $args[0];
        $to = $args[1];
        extract( $assoc_args );
        $preamble = "Will migrate all $from to $to";
        $get_posts = $this->get_specified_posts( $assoc_args, $args );
        $message = $get_posts['message'];
        $posts = $get_posts['posts'];
        $args = $get_posts['args'];
        $message = "{$preamble} {$message}.\n";
        print_r($message);
        $count = count($posts);
        $set = array();
        foreach ( $posts as $p ) {
            $terms = wp_get_post_terms( $p->ID, $from );
            foreach ( $terms as $t ) {
                $new_term = wp_insert_term( $t->name, $to, array( 'slug' => $t->slug ) );
                if ( $new_term instanceof \WP_Error ) {
                    $new_term = get_term_by('slug', $t->slug, $from);
                    array_push($set, $new_term->slug);
                } else {
                    $added_term = get_term( $new_term['term_id'], $to, $output = OBJECT, $filter = 'raw' );
                    array_push($set, $added_term->slug);
                }
            }
            $n = count($set);
            $message = "Setting {$n} terms for {$to} on {$args['post_type']} #{$p->ID}.\n";
            print_r($message);
            $new = wp_set_object_terms( $p->ID, $set, $to );
            // clear all those posts out of $set to tee up the next 
            $set = array();
        }
        $message = "All {$from} successfully migrated to {$to} for {$count} {$args['post_type']}s. You did it!";
        \WP_CLI::success( $message );
    }

    /**
    * @subcommand author
    *
    * Migrates author names to a taxonomy.
    *
    * ## Options
    * <type>
    * : Acceptable: taxonomy or custom_field (expects a taxonomy called author or will use custom_field key "custom_author")
    * 
    * @synopsis [<type>] [--include=<foo>] [--exclude=<foo>] [--post_type=<foo>] [--authors=<foo>] [--before=<foo>] [--after=<foo>] [--ignore_term] [--dry-run]
    * @todo
    * 
    **/
    public function author( $args, $assoc_args ) {
        if ( empty($args) && taxonomy_exists( 'author' ) ) {
            $to = 'the author taxonomy';
            $type = true;
            $taxonomy = 'author';
        } elseif ( is_array($args) && taxonomy_exists($args[0]) ) {
            $to = "taxonomy '{$args[0]}'";
            $type = true;
            $taxonomy = $args[0];
        } elseif ( isset($args) ) {
            $to = "custom field '{$args[0]}'";
            $type = $args[0];
        } else {
            $to = 'a custom field';
            $type = 'custom_author';
        }
        $preamble = "Will migrate native authors to {$to}";
        $get_posts = $this->get_specified_posts($assoc_args);
        $message = $get_posts['message'];
        $posts = $get_posts['posts'];
        $args = $get_posts['args'];
        $count = count($posts);
        print_r("$preamble $message.\n");
        if ( isset($assoc_args['dry-run']) ) {
            \WP_CLI::success("If this were not a dry run, all authors would migrate for {$count} on {$args['post_type']}s.");
        } else {
            foreach ( $posts as $p ) {
                $authorID = $p->post_author;
                $author_name = get_the_author_meta('display_name', $authorID );
                $terms = $this->split_by_comma_or_and($author_name);
                if ( $type === true ) {
                    $this->set_author_terms($p->ID, $terms, $taxonomy);
                } else {
                    $this->set_author_as_meta($p->ID, $terms, $type);
                }
            }
            \WP_CLI::success("All authors migrated for {$count} on {$args['post_type']}s. You did it!");
        }
    }
}

\WP_CLI::add_command( 'migrate', '\CFPB\Migrate_Command' );