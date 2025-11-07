<?php
/*
Plugin Name: Display Posts Multisite
Requires Plugins: display-posts-shortcode
Plugin URI: 
Description: Adds a blog_id parameter to display posts from other sites on the network.
Version: 0.3
Author: John Pennypacker <john@pennypacker.net>
Author URI: 
*/

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

// to make taxonomy queries work, the taxonomy on the remote site must also be registered
// on this site.  This setting screen lets admins enter taxonomies to query.
if( is_admin() ) {
	include_once( plugin_dir_path( __FILE__ ) . 'settings.php' );
}

/**
 * The wrapper class. Handy for storing values like attributes and swiched.
 */
class DPSMultisite {

	/**
	 * Declare properties.
	 * As of php 8.2, you get warnings if you don't.
	 */
	public $path;
	public $url;
	public $switched = FALSE;
	public $atts = array();

	/**
	 * Constructor method.
	 * Sets up instance variables, adds new shortcode, filters permalinks for formatting.
	 */
	function __construct() {
		$this->path = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ );

		add_shortcode( 'display-posts-multisite', array( $this, 'dpsmulti_shortcode' ) );
		add_filter( 'the_permalink', array( $this, 'autofix_permalink' ), 99 );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Shortcode callback.
	 * Switches blogs if id exists, then wraps be_display_posts_shortcode().
	 * @param arr $attributes are the shortcode attributes.
	 * @param str $content is the stuff inside the shortcode (unused).
	 * @param str $shortcode the raw shortcode (unused).
	 * @return str
	 */
	function dpsmulti_shortcode( $attributes, $content, $shortcode ) {
		$this->atts = $attributes;
		if( isset( $attributes['blog_id'] ) ) {
			$site = get_site( $attributes['blog_id'] );
			if( $site->blog_id ) {
				$this->switched = TRUE;
				switch_to_blog( $site->blog_id );
			}	
		}
		$return = be_display_posts_shortcode( $attributes );
		if( TRUE === $this->switched ) {
			restore_current_blog();
			$this->switched = FALSE;
		}
		return $return;
	}

	/**
	 * Links are likely to come back looking ugly becase we don't bootstrap the source site.
	 * The workaround here is to address that. Especially with custom post types.
	 * @param str $url The URL of the link.
	 * @return str
	 */
	function autofix_permalink( $url ) {
		if( $this->switched && FALSE !== strpos( $url, '?p=' ) ) {
			global $post;
			$pretty_url = $this->get_post_permalink( $post );
			if( FALSE !== $pretty_url ) {
				return $pretty_url;
			}
		}
		return $url;
	}
	
	/**
	 * Suss out the permalink based on the rewrite_rules option.
	 * @param obj $post A post.
	 * @return str
	 */
	function get_post_permalink( $post ) {
		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
			return FALSE;
		}
		$rw = get_option( 'rewrite_rules' );
		
		if( FALSE === $rw ) {
			return FALSE;
		}
		
		$output = FALSE;

		$k = array_search( 'index.php?post_type=' . $post->post_type, $rw );
		if( $k ) {
			$path = str_replace( '?$', '', $k ) . $post->post_name . '/';
			$output = get_site_url() . '/' . $path;
		}
		$output = apply_filters( 'dpsmulti_shortcode_permalink', $output, $post, $rw );	
		return $output;
	}
	
	function register_taxonomies() {
		$option = get_option( 'dpsmulti_taxonomies' );	
		if( FALSE === $option ) {
			return;
		}
		$option = str_replace( ',', "\n", $option );
		$slugs = explode( "\n", $option );
		foreach( $slugs as $t ) {
			$t = trim( $t );
			if( ! taxonomy_exists( $t ) ) {
				register_taxonomy( $t, null, array(
					'public'                => false,
					'show_ui'               => true,  // equal to the argument public
					'show_in_rest'          => false, // add to REST API
					'hierarchical'          => false,
					'update_count_callback' => '__return_null',
				));
			}
		}
	}
	
}

new DPSMultisite();