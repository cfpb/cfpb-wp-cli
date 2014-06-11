<?php
namespace CFPB;
class CLI_Common extends \WP_CLI_COMMAND {

	protected function get_specified_posts($assoc_args) {
        extract($assoc_args);
        $args = array('posts_per_page' => -1);
        $include = isset($include) ? $include : 'all';
        $message = '';
        if ( $include != 'all' ) {
            $args['include'] = $include;
            $message .= "for post(s) {$include}";
        } else {
        	$message .= "for all posts";
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

    protected function set_author_terms($object_id, $terms) {
        foreach ( $terms as $k => $a ) {
            if ( has_term($a, 'author', $object_id ) ) {
                unset($terms, $k);
            }
            if ( !empty( $terms ) ) {
                wp_set_object_terms( $object_id, $terms, 'author', $append = false );
            }
        }
    }

    protected function split_by_comma_or_and($string) {
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