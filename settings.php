<?php
/**
 *	Display Posts Multisite Settings
 */

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');


/**
 * Add the top level menu page.
 */
function dpsmulti_options_page() {
	add_submenu_page(
		'options-general.php',
		'Display Posts Multisite Settings', // title as it appears on the settings page
		'Display Posts Multisite Settings', // title as it appears in the Settings menu
		'manage_options',
		'display-posts-multisite-settings',
		'dpsmulti_options_page_html'
	);
}
add_action( 'admin_menu', 'dpsmulti_options_page' );


/**
 * custom option and settings
 */
function dpsmulti_settings_init() {

	register_setting( 'dpsmulti_settings', 'dpsmulti_taxonomies', [] );

	// Register a new settings section.
	add_settings_section(
		'dpsmulti_settings', // id
		__( 'Taxonomy Settings', 'dpsmulti' ), // fieldset title
		'dpsmulti_taxonomies_callback', // callback
		'display-posts-multisite-settings', // page slug
		array(
			'label_for' => 'dpsmulti_taxonomies', // make sure this is the same as the id
			'class' => 'dpsmulti_row',
			'desc' => 'Enter the slugs of the taxonomies from other source sites on the network that you want to query.  Separate slugs by commas or line breaks.',
		)
	);
}
add_action( 'admin_init', 'dpsmulti_settings_init' );

/**
 * Field callback function.
 *
 * @param array $args
 */
function dpsmulti_taxonomies_callback( $args ) {

	// Get the value of the setting we've registered with register_setting()
	$option = get_option( $args['label_for'] );	
	?>
	<p>If you want to query posts on other sites by taxonomy, the taxonomy has to exist on this site. In a multisite environment, it's possible that the taxonomy you want to query exists on the source site, but not this one. In that case, enter the taxonomy slugs here so you can query against them.</p>
	<textarea
		type="text"
		cols="80"
		rows="12"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>" 
	><?php echo $option ?></textarea>
	<p class="description">
		<?php esc_html_e( $args['desc'], 'dpsmulti' ); ?>
	</p>
	<?php
}


/**
 * Top level menu callback function
 */
function dpsmulti_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
//  	echo '<pre>opts ', var_dump( wp_load_alloptions() ), '</pre>';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered to this page
			settings_fields( 'dpsmulti_settings' );

			// output setting sections and their fields
			do_settings_sections( 'display-posts-multisite-settings' );

			// output save settings button
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}
