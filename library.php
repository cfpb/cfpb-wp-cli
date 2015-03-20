<?php
namespace CFPB;
class CLI_Common extends \WP_CLI_COMMAND {

	protected function get_specified_posts($assoc_args) {
        $args = array('posts_per_page' => -1);
        $include = isset($assoc_args['include']) ? $assoc_args['include'] : 'all';
        $exclude = isset($assoc_args['exclude']) ? $assoc_args['exclude'] : null;
        $message = '';
        if ( $include != 'all' ) {
            $args['include'] = $include;
            $message .= "for post(s) {$include}";
        } else {
        	$message .= "for all posts";
        }

        if ( isset( $assoc_args['exclude'] ) ) {
            $args['exclude'] = $assoc_args['exclude'];
            $message .= "excluding {$assoc_args['exclude']}";
        }

        if ( isset( $assoc_args['post_type'] ) ) {
            $args['post_type'] = $assoc_args['post_type'];
            $message .= " of the {$assoc_args['post_type']} post type";
        } else {
            $args['post_type'] = 'post';
        }
        
        if ( isset( $assoc_args['ignore_term'] ) ) {
            $posts = $this->get_ignored_terms($assoc_args['ignore_term'], $args['post_type']);

            $ignore_taxonomy = array();
            foreach ( $posts as $p ) {
                array_push($ignore_taxonomy, $p->ID);
            }
            $args['post__not_in'] = $ignore_taxonomy;
            $taxonomy_m = explode(',', $assoc_args['ignore_term']);
            $message .= " excluding the following terms from the {$taxonomy_m[0]} taxonomy: ";
            unset($taxonomy_m[0]);
            $count = count($taxonomy_m);
            foreach ( $taxonomy_m as $index => $term ) {
                if ( $index != $count ) {
                $message .= "$term, ";
                } else {
                    $message .= "{$term}";
                }
            }
        }

        if ( isset($assoc_args['before']) ) {
            $args['date_query'] = array(
                'before' => $assoc_args['before'],
            );
            $message .= "published before {$assoc_args['before']}";
        }
        if ( isset($assoc_args['after']) ) {
            $args['date_query'] = array(
                'after' => $assoc_args['after'],
            );
            if ( array_key_exists('before', $args['date_query']) ) {
                $message .= "and after {$assoc_args['after']}";
            } else {
                $message .= "published after {$assoc_args['after']}";
            }
        }
        // unimplemented stuff, keep this before get_posts, for now
        if ( isset($assoc_args['terms']) ) {
            $message .= "against only these terms: {$assoc_args['terms']}";
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

    protected function get_ignored_terms($ignore_term, $post_type) {
        // Explode by comma the entire ignore_term parameter. We require the
        // first string in the new array to be the taxonomy containing the terms
        // to ignore.
        $terms = explode( ',', $ignore_term );
        // The 0 index of $terms above will always be either the 'to' value or 
        // the first value in the comma-separated list passed to $ignore_term.
        // Regardless, it will always be an array.
        $taxonomy = $terms[0];
        // if $terms is an array with more than one key we need to grab the terms
        if ( count($terms) > 1 ) {
            $ignore_term = array();
            // cycle through the rest of the array (skipping index 0) and push
            // them into the $ignore_term array.
            foreach ($terms as $index => $term) {
                if ( $index > 0 ) {
                    array_push($ignore_term, $term);
                }
            }
        }
        $args = array(
            'posts_per_page' => -1,
            'post_type' => $post_type,
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $ignore_term,
                ),
            ),
        );
        $posts = get_posts( $args );
        return $posts;
    }

    protected function set_author_terms($object_id, $terms, $taxonomy = 'author') {
        foreach ( $terms as $k => $a ) {
            if ( has_term($a, $taxonomy, $object_id ) ) {
                unset($terms, $k);
            }
            if ( !empty( $terms ) ) {
                wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false );
            }
        }
    }

    protected function set_author_as_meta($post_id, $terms, $meta_key) {
        foreach ( $terms as $meta_value ) {
            add_post_meta( $post_id, $meta_key, $meta_value, $unique = false );
        }
    }

    protected function split_by_comma_or_and($string) {
        $authors = array();
        $explosion = explode(', ', $string);
        $count = count($explosion);
        if ( $count == 1 && strstr($string, ' and ') ) {
            $index = strpos($string, ' and ');
            array_push($authors, substr($string, 0, $index));
            array_push($authors, substr($string, $index+5));
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

    protected function get_random_terms() {
    	$post_to = 'http://hipsterjesus.com/api/?paras=1&type=hipster-centric&html=false';
    	$init = curl_init( );
    	curl_setopt($init, CURLOPT_HEADER, 0);
    	curl_setopt($init, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($init, CURLOPT_URL, $post_to);
    	$result = curl_exec($init);
    	curl_close($init);
   		$result = json_decode($result, true);
    	$text = $result['text'];
    	$terms = explode(' ', $text);
    	$terms = array_unique($terms);
    	foreach ( $terms as $k => $t ) {
    		if ( empty($t)) {
    			unset($terms[$k]);
    		} elseif ( strstr($t, '.') ) {
    			$t = substr($t, 0, strpos($t, '.')); // lop off the period at the 
    			$terms[$k] = $t; 					 // end of a term and reset
    		} elseif ( strstr( $t, ',' ) ) {
    			$t = substr($t, 0, strpos($t,','));
    			$terms[$k] = $t;
    		} elseif ( is_numeric($t) || strlen( $t < 4 ) ) {
    			unset($terms[$k]);
    		}
    	} 
    	return $terms;
    }
}