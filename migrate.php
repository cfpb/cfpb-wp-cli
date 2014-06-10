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
        $message = '';
        if ( $include != 'all' ) {
            $args['include'] = $include;
            $message .= "for post(s) {$include}";
        }

        if ( isset( $exclude ) ) {
            $args['exclude'] = $exclude;
            $message .= "excluding {$exclude}";
        }
        if ( isset( $post_type ) ) {
            $args['post_type'] = $post_type;
            $message .= "of the {$post_type} post type";
        } else {
            $args['post_type'] = 'post';
        }
        if ( isset($before) ) {
            $args['date_query'] = array(
                'before' => $before,
            );
            $message .= "published before {$before}";
        }
        if ( isset($after) ) {
            $args['date_query'] = array(
                'after' => $after,
            );
            if ( array_key_exists('before', $args['date_query']) ) {
                $message .= "and after {$after}";
            } else {
                $message .= "published after {$after}";
            }
        }
        // unimplemented stuff, keep this before get_posts, for now
        if ( isset($terms) ) {
            $message .= "against only these terms: $terms";
            exit('Unimplemented' );
        }
        if ( ! isset( $message ) ) {
            $message = "all posts";
        }

        // start the action!
        $posts = get_posts($args);
        return array( 
            'message' => $message, 
            'posts' => $posts, 
            'args' => $args,
        );
    }
    public function taxonomy( $args, $assoc_args ) {
        if ( empty($args) ) {
            exit('Invalid entry.');
        }
        $from = $args[0];
        $to = $args[1];
        extract( $assoc_args );
        $preamble = "Will migrate all $from to $to";
        $get_posts = $this->get_specified_posts( $assoc_args );
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
                if ( $new_term instanceof WP_Error ) {
                    $new_term = get_term_by('slug', $t->slug, $from);
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
        $message = "All {$from} successfully migrated to {$to} for {$count} {$args['post_type']}s. You did it!";
        WP_CLI::success( $message );
    }

    /**
    * @subcommand author
    *
    * Migrates author names to a taxonomy.
    *
    * ## Options
    * <type>
    * : Acceptable: taxonomy or custom_field (expects a taxonomy called Author or will use custom_field key "custom_author")
    * 
    * @synopsis [<type>] [--include=<foo>] [--exclude=<foo>] [--post_types=<foo>] [--authors=<foo>] [--before=<foo>] [--after=<foo>]
    * @todo
    * 
    **/
    public function author( $args, $assoc_args ) {
        if ( empty($args) && taxonomy_exists( 'author' ) ) {
            $to = 'author taxonomy';
            $type = get_taxonomy( 'author' );
        } elseif ( is_array($args) && taxonomy_exists($args[0]) ) {
            $to = "taxonomy '{$args[0]}'";
            $type = get_taxonomy( $args[0] );
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
        print_r("$preamble $message.\n");
        foreach ( $posts as $p ) {
            $authorID = $p->post_author;
            $author_name = get_the_author_meta('display_name', $authorID );
            $terms = $this->split_by_comma_or_and($author_name);
            $this->set_author_terms($p->ID, $terms);
            wp_set_object_terms( $p->ID, $terms, 'author', $append = false );
        }
    }

    private function set_author_terms($object_id, $terms) {
        foreach ( $terms as $k => $a ) {
            if ( has_term($a, 'author', $object_id ) ) {
                unset($terms, $k);
            }
            if ( !empty( $terms ) ) {
                wp_set_object_terms( $object_id, $terms, 'author', $append = false );
            }
        }
    }

    private function split_by_comma_or_and($string) {
        $authors = array();
        $explosion = explode(', ', $string);
        $count = count($explosion);
        if ( $count == 1 && strstr($string, ' and ') ) {
            $index = strpos($string, ' and ');
            array_push($authors, substr($string, 0, $index-1));
            array_push($authors, substr($string, $index+4));
        } elseif ( $count > 1 ) {
            foreach ( $explosion as $e ) {
                if ( strstr($e, ' and ') ) {
                    $index = strpos($e, ' and ');
                    array_push($authors, 0, substr($e, 0, $index));
                    array_push($authors, substr($e, $index+5));
                } else {
                    array_push($authors, $e);
                }
            }
        } else {
            array_push($authors, $string);
        }
        return $authors;
    }
}

WP_CLI::add_command( 'migrate', 'Migrate_Command' );
