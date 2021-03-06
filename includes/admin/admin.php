<?php

/**
 * Main bbPress Admin Class
 *
 * @package bbPress
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Admin' ) ) :
/**
 * Loads bbPress plugin admin area
 *
 * @package bbPress
 * @subpackage Administration
 * @since 2.0.0 bbPress (r2464)
 */
class BBP_Admin {

	/** Directory *************************************************************/

	/**
	 * @var string Path to the bbPress admin directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the bbPress admin directory
	 */
	public $admin_url = '';

	/**
	 * @var string URL to the bbPress images directory
	 */
	public $images_url = '';

	/**
	 * @var string URL to the bbPress admin styles directory
	 */
	public $styles_url = '';

	/**
	 * @var string URL to the bbPress admin css directory
	 */
	public $css_url = '';

	/**
	 * @var string URL to the bbPress admin js directory
	 */
	public $js_url = '';

	/** Capability ************************************************************/

	/**
	 * @var bool Minimum capability to access Tools and Settings
	 */
	public $minimum_capability = 'keep_gate';

	/** Separator *************************************************************/

	/**
	 * @var bool Whether or not to add an extra top level menu separator
	 */
	public $show_separator = false;

	/** Tools *****************************************************************/

	/**
	 * @var array Array of available repair tools
	 */
	public $tools = array();

	/** Functions *************************************************************/

	/**
	 * The main bbPress admin loader
	 *
	 * @since 2.0.0 bbPress (r2515)
	 *
	 * @uses BBP_Admin::setup_globals() Setup the globals needed
	 * @uses BBP_Admin::includes() Include the required files
	 * @uses BBP_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since 2.0.0 bbPress (r2646)
	 *
	 * @access private
	 */
	private function setup_globals() {
		$bbp              = bbpress();
		$this->admin_dir  = trailingslashit( $bbp->includes_dir . 'admin'      ); // Admin path
		$this->admin_url  = trailingslashit( $bbp->includes_url . 'admin'      ); // Admin url
		$this->images_url = trailingslashit( $this->admin_url   . 'images'     ); // Admin images URL
		$this->styles_url = trailingslashit( $this->admin_url   . 'styles'     ); // Admin styles URL
		$this->css_url    = trailingslashit( $this->admin_url   . 'assets/css' ); // Admin css URL
		$this->js_url     = trailingslashit( $this->admin_url   . 'assets/js'  ); // Admin js URL
	}

	/**
	 * Include required files
	 *
	 * @since 2.0.0 bbPress (r2646)
	 *
	 * @access private
	 */
	private function includes() {

		// Tools
		require $this->admin_dir . 'tools.php';
		require $this->admin_dir . 'tools/common.php';
		require $this->admin_dir . 'tools/converter.php';
		require $this->admin_dir . 'tools/repair.php';
		require $this->admin_dir . 'tools/upgrade.php';
		require $this->admin_dir . 'tools/reset.php';
		require $this->admin_dir . 'tools/help.php';

		// Components
		require $this->admin_dir . 'settings.php';
		require $this->admin_dir . 'common.php';
		require $this->admin_dir . 'metaboxes.php';
		require $this->admin_dir . 'forums.php';
		require $this->admin_dir . 'topics.php';
		require $this->admin_dir . 'replies.php';
		require $this->admin_dir . 'users.php';

		// Converter
		require $this->admin_dir . 'classes/class-bbp-converter-base.php';
		require $this->admin_dir . 'classes/class-bbp-converter.php';
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since 2.0.0 bbPress (r2646)
	 *
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( bbp_is_deactivation() ) {
			return;
		}

		/** General Actions ***************************************************/

		add_action( 'bbp_admin_menu',              array( $this, 'admin_menus'             )     ); // Add menu item to settings menu
		add_action( 'bbp_admin_head',              array( $this, 'admin_head'              )     ); // Add some general styling to the admin area
		add_action( 'bbp_admin_notices',           array( $this, 'activation_notice'       )     ); // Add notice if not using a bbPress theme
		add_action( 'bbp_register_admin_style',    array( $this, 'register_admin_style'    )     ); // Add green admin style
		add_action( 'bbp_register_admin_settings', array( $this, 'register_admin_settings' )     ); // Add settings
		add_action( 'bbp_activation',              array( $this, 'new_install'             )     ); // Add menu item to settings menu
		add_action( 'admin_enqueue_scripts',       array( $this, 'enqueue_styles'          )     ); // Add enqueued CSS
		add_action( 'admin_enqueue_scripts',       array( $this, 'enqueue_scripts'         )     ); // Add enqueued JS
		add_action( 'admin_bar_menu',              array( $this, 'admin_bar_about_link'    ), 15 ); // Add a link to bbPress about page to the admin bar

		/** Ajax **************************************************************/

		// No _nopriv_ equivalent - users must be logged in
		add_action( 'wp_ajax_bbp_suggest_topic', array( $this, 'suggest_topic' ) );
		add_action( 'wp_ajax_bbp_suggest_user',  array( $this, 'suggest_user'  ) );

		/** Filters ***********************************************************/

		// Modify admin links
		add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'bbp_map_meta_caps',   array( $this, 'map_settings_meta_caps' ), 10, 4 );

		// Allow keymasters to save forums settings
		add_filter( 'option_page_capability_bbpress',  array( $this, 'option_page_capability_bbpress' ) );

		/** Network Admin *****************************************************/

		// Add menu item to settings menu
		add_action( 'network_admin_menu',  array( $this, 'network_admin_menus' ) );

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'bbp_admin_loaded', array( &$this ) );
	}

	/**
	 * Add the admin menus
	 *
	 * @since 2.0.0 bbPress (r2646)
	 *
	 * @uses add_management_page() To add the Recount page in Tools section
	 * @uses add_options_page() To add the Forums settings page in Settings
	 *                           section
	 */
	public function admin_menus() {

		$hooks = array();

		// Get the tools pages
		$tools = bbp_get_tools_admin_pages();

		// Loop through tools and check
		foreach ( $tools as $tool ) {

			// Try to add the admin page
			$page = add_management_page(
				$tool['name'],
				$tool['name'],
				$tool['cap'],
				$tool['page'],
				$tool['func']
			);

			// Add page to hook if user can view it
			if ( false !== $page ) {
				$hooks[] = $page;
			}
		}

		// Fudge the highlighted subnav item when on a bbPress admin page
		foreach ( $hooks as $hook ) {
			add_action( "admin_head-{$hook}", 'bbp_tools_modify_menu_highlight' );
		}

		// Forums Tools Root
		add_management_page(
			esc_html__( 'Forums', 'bbpress' ),
			esc_html__( 'Forums', 'bbpress' ),
			'bbp_tools_page',
			'bbp-repair',
			'bbp_admin_repair_page'
		);

		// Are settings enabled?
		if ( 'basic' === bbp_settings_integration() ) {
			add_options_page(
				esc_html__( 'Forums',  'bbpress' ),
				esc_html__( 'Forums',  'bbpress' ),
				'bbp_settings_page',
				'bbpress',
				'bbp_admin_settings'
			);
		}

		// These are later removed in admin_head
		if ( current_user_can( 'bbp_about_page' ) ) {

			// About
			add_dashboard_page(
				esc_html__( 'Welcome to bbPress',  'bbpress' ),
				esc_html__( 'Welcome to bbPress',  'bbpress' ),
				'bbp_about_page',
				'bbp-about',
				array( $this, 'about_screen' )
			);

			// Credits
			add_dashboard_page(
				esc_html__( 'Welcome to bbPress',  'bbpress' ),
				esc_html__( 'Welcome to bbPress',  'bbpress' ),
				'bbp_about_page',
				'bbp-credits',
				array( $this, 'credits_screen' )
			);
		}

		// Bail if plugin is not network activated
		if ( ! is_plugin_active_for_network( bbpress()->basename ) ) {
			return;
		}

		add_submenu_page(
			'index.php',
			esc_html__( 'Update Forums', 'bbpress' ),
			esc_html__( 'Update Forums', 'bbpress' ),
			'manage_network',
			'bbp-update',
			array( $this, 'update_screen' )
		);
	}

	/**
	 * Add the network admin menus
	 *
	 * @since 2.1.0 bbPress (r3689)
	 *
	 * @uses add_submenu_page() To add the Update Forums page in Updates
	 */
	public function network_admin_menus() {

		// Bail if plugin is not network activated
		if ( ! is_plugin_active_for_network( bbpress()->basename ) ) {
			return;
		}

		add_submenu_page(
			'upgrade.php',
			esc_html__( 'Update Forums', 'bbpress' ),
			esc_html__( 'Update Forums', 'bbpress' ),
			'manage_network',
			'bbpress-update',
			array( $this, 'network_update_screen' )
		);
	}

	/**
	 * If this is a new installation, create some initial forum content.
	 *
	 * @since 2.1.0 bbPress (r3767)
	 *
	 * @return type
	 */
	public static function new_install() {
		if ( ! bbp_is_install() ) {
			return;
		}

		bbp_create_initial_content();
	}

	/**
	 * Register the settings
	 *
	 * @since 2.0.0 bbPress (r2737)
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 * @todo Put fields into multidimensional array
	 */
	public static function register_admin_settings() {

		// Bail if no sections available
		$sections = bbp_admin_get_settings_sections();
		if ( empty( $sections ) ) {
			return false;
		}

		// Are we using settings integration?
		$settings_integration = bbp_settings_integration();

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) ) {
				continue;
			}

			// Only add section and fields if section has fields
			$fields = bbp_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) ) {
				continue;
			}

			// Overload the converter page
			if ( ! empty( $section['page'] ) && ( ( 'converter' === $section['page'] ) || ( 'deep' === $settings_integration ) ) ) {
				$page = $section['page'];
			} else {
				$page = 'bbpress';
			}

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $page );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					add_settings_field( $field_id, $field['title'], $field['callback'], $page, $section_id, $field['args'] );
				}

				// Register the setting
				register_setting( $page, $field_id, $field['sanitize_callback'] );
			}
		}
	}

	/**
	 * Maps settings capabilities
	 *
	 * @since 2.2.0 bbPress (r4242)
	 *
	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param array $args Arguments
	 * @uses get_post() To get the post
	 * @uses apply_filters() Calls 'bbp_map_meta_caps' with caps, cap, user id and
	 *                        args
	 * @return array Actual capabilities for meta capability
	 */
	public static function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// What capability is being checked?
		switch ( $cap ) {

			// Pages
			case 'bbp_about_page'            : // About and Credits
			case 'bbp_tools_page'            : // Tools Page
			case 'bbp_tools_repair_page'     : // Tools - Repair Page
			case 'bbp_tools_upgrade_page'    : // Tools - Upgrade Page
			case 'bbp_tools_import_page'     : // Tools - Import Page
			case 'bbp_tools_reset_page'      : // Tools - Reset Page
			case 'bbp_settings_page'         : // Settings Page

			// Converter Sections
			case 'bbp_converter_connection'  : // Converter - Connection
			case 'bbp_converter_options'     : // Converter - Options

			// Settings Sections
			case 'bbp_settings_users'        : // Settings - Users
			case 'bbp_settings_features'     : // Settings - Features
			case 'bbp_settings_theme_compat' : // Settings - Theme compat
			case 'bbp_settings_root_slugs'   : // Settings - Root slugs
			case 'bbp_settings_single_slugs' : // Settings - Single slugs
			case 'bbp_settings_user_slugs'   : // Settings - User slugs
			case 'bbp_settings_per_page'     : // Settings - Per page
			case 'bbp_settings_per_rss_page' : // Settings - Per RSS page
				$caps = array( bbpress()->admin->minimum_capability );
				break;

			// Extend - BuddyPress
			case 'bbp_settings_buddypress' :
				if ( ( is_plugin_active( 'buddypress/bp-loader.php' ) && defined( 'BP_VERSION' ) && bp_is_root_blog() ) && is_super_admin() ) {
					$caps = array( bbpress()->admin->minimum_capability );
				} else {
					$caps = array( 'do_not_allow' );
				}

				break;

			// Extend - Akismet
			case 'bbp_settings_akismet' :
				if ( ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) && is_super_admin() ) {
					$caps = array( bbpress()->admin->minimum_capability );
				} else {
					$caps = array( 'do_not_allow' );
				}

				break;
		}

		// Filter & return
		return (array) apply_filters( 'bbp_map_settings_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Register the importers
	 *
	 * @since 2.0.0 bbPress (r2737)
	 *
	 * @uses apply_filters() Calls 'bbp_importer_path' filter to allow plugins
	 *                        to customize the importer script locations.
	 */
	public function register_importers() {

		// Leave if we're not in the import section
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			return;
		}

		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		// Load our importers
		$importers = apply_filters( 'bbp_importers', array( 'bbpress' ) );

		// Loop through included importers
		foreach ( $importers as $importer ) {

			// Allow custom importer directory
			$import_dir  = apply_filters( 'bbp_importer_path', $this->admin_dir . 'importers', $importer );

			// Compile the importer path
			$import_file = trailingslashit( $import_dir ) . $importer . '.php';

			// If the file exists, include it
			if ( file_exists( $import_file ) ) {
				require $import_file;
			}
		}
	}

	/**
	 * Admin area activation notice
	 *
	 * Shows a nag message in admin area about the theme not supporting bbPress
	 *
	 * @since 2.0.0 bbPress (r2743)
	 *
	 * @uses current_user_can() To check notice should be displayed.
	 */
	public function activation_notice() {
		// @todo - something fun
	}

	/**
	 * Add Settings link to plugins area
	 *
	 * @since 2.0.0 bbPress (r2737)
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public static function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not bbPress
		if ( plugin_basename( bbpress()->basename ) !== $file ) {
			return $links;
		}

		// New links to merge into existing links
		$new_links = array();

		// Settings page link
		if ( current_user_can( 'bbp_settings_page' ) ) {
			$new_links['settings'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'bbpress'   ), admin_url( 'options-general.php' ) ) ) . '">' . esc_html__( 'Settings', 'bbpress' ) . '</a>';
		}

		// About page link
		if ( current_user_can( 'bbp_about_page' ) ) {
			$new_links['about']    = '<a href="' . esc_url( add_query_arg( array( 'page' => 'bbp-about' ), admin_url( 'index.php'           ) ) ) . '">' . esc_html__( 'About',    'bbpress' ) . '</a>';
		}

		// Add a few links to the existing links array
		return array_merge( $links, $new_links );
	}

	/**
	 * Add a link to bbPress about page to the admin bar
	 *
	 * @since 2.5.0 bbPress (r5136)
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar_about_link( $wp_admin_bar ) {
		if ( is_user_logged_in() && current_user_can( 'bbp_about_page' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'wp-logo',
				'id'     => 'bbp-about',
				'title'  => esc_html__( 'About bbPress', 'bbpress' ),
				'href'   => add_query_arg( array( 'page' => 'bbp-about' ), admin_url( 'index.php' ) )
			) );
		}
	}

	/**
	 * Enqueue any admin scripts we might need
	 *
	 * @since 2.2.0 bbPress (r4260)
	 */
	public function enqueue_scripts() {

		// Enqueue suggest for forum/topic/reply autocompletes
		wp_enqueue_script( 'suggest' );

		// Minified
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Get the version to use for JS
		$version = bbp_get_version();

		// Post type checker (only topics and replies)
		if ( 'post' === get_current_screen()->base ) {
			switch( get_current_screen()->post_type ) {
				case bbp_get_reply_post_type() :
				case bbp_get_topic_post_type() :

					// Enqueue the common JS
					wp_enqueue_script( 'bbp-admin-common-js', $this->js_url . 'common' . $suffix . '.js', array( 'jquery', 'suggest' ), $version );

					// Topics admin
					if ( bbp_get_topic_post_type() === get_current_screen()->post_type ) {
						wp_enqueue_script( 'bbp-admin-topics-js', $this->js_url . 'topics' . $suffix . '.js', array( 'jquery' ), $version );

					// Replies admin
					} elseif ( bbp_get_reply_post_type() === get_current_screen()->post_type ) {
						wp_enqueue_script( 'bbp-admin-replies-js', $this->js_url . 'replies' . $suffix . '.js', array( 'jquery', 'suggest' ), $version );
					}

					break;
			}
		}
	}

	/**
	 * Enqueue any admin scripts we might need
	 *
	 * @since 2.6.0 bbPress (r5224)
	 */
	public function enqueue_styles() {

		// RTL and/or minified
		$suffix  = is_rtl() ? '-rtl' : '';
		$suffix .= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue admin CSS with dashicons dependency
		wp_enqueue_style( 'bbp-admin-css', $this->css_url . 'admin' . $suffix . '.css', array( 'dashicons' ), bbp_get_version() );
	}

	/**
	 * Remove the individual recount and converter menus.
	 * They are grouped together by h2 tabs
	 *
	 * @since 2.0.0 bbPress (r2464)
	 *
	 * @uses remove_submenu_page() To remove menu items with alternate navigation
	 */
	public function admin_head() {

		// Tools
		foreach ( bbp_get_tools_admin_pages() as $tool ) {
			remove_submenu_page( 'tools.php', $tool['page'] );
		}

		// About
		remove_submenu_page( 'index.php', 'bbp-about'   );
		remove_submenu_page( 'index.php', 'bbp-credits' );
	}

	/**
	 * Registers the bbPress admin color scheme
	 *
	 * Because wp-content can exist outside of the WordPress root there is no
	 * way to be certain what the relative path of the admin images is.
	 * We are including the two most common configurations here, just in case.
	 *
	 * @since 2.0.0 bbPress (r2521)
	 *
	 * @uses wp_admin_css_color() To register the color scheme
	 */
	public function register_admin_style() {

		// Color schemes are not available when running out of src
		if ( false !== strpos( plugin_basename( bbpress()->file ), 'src' ) ) {
			return;
		}

		// RTL and/or minified
		$suffix  = is_rtl() ? '-rtl' : '';
		$suffix .= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Mint
		wp_admin_css_color(
			'bbp-mint',
			esc_html_x( 'Mint', 'admin color scheme', 'bbpress' ),
			$this->styles_url . 'mint/colors' . $suffix . '.css',
			array( '#4f6d59', '#33834e', '#5FB37C', '#81c498' ),
			array( 'base' => '#f1f3f2', 'focus' => '#fff', 'current' => '#fff' )
		);

		// Evergreen
		wp_admin_css_color(
			'bbp-evergreen',
			esc_html_x( 'Evergreen', 'admin color scheme', 'bbpress' ),
			$this->styles_url . 'evergreen/colors' . $suffix . '.css',
			array( '#324d3a', '#446950', '#56b274', '#324d3a' ),
			array( 'base' => '#f1f3f2', 'focus' => '#fff', 'current' => '#fff' )
		);
	}

	/**
	 * Allow keymaster role to save Forums settings
	 *
	 * @since 2.3.0 bbPress (r4678)
	 *
	 * @param string $capability
	 * @return string Return 'keep_gate' capability
	 */
	public function option_page_capability_bbpress( $capability = 'manage_options' ) {
		$capability = 'keep_gate';
		return $capability;
	}

	/** Ajax ******************************************************************/

	/**
	 * Ajax action for facilitating the forum auto-suggest
	 *
	 * @since 2.2.0 bbPress (r4261)
	 *
	 * @uses get_posts()
	 * @uses bbp_get_topic_post_type()
	 * @uses bbp_get_topic_id()
	 * @uses bbp_get_topic_title()
	 */
	public function suggest_topic() {

		// Bail early if no request
		if ( empty( $_REQUEST['q'] ) ) {
			wp_die( '0' );
		}

		// Bail if user cannot moderate - only moderators can change hierarchy
		if ( ! current_user_can( 'moderate' ) ) {
			wp_die( '0' );
		}

		// Check the ajax nonce
		check_ajax_referer( 'bbp_suggest_topic_nonce' );

		// Try to get some topics
		$topics = get_posts( array(
			's'         => bbp_db()->esc_like( $_REQUEST['q'] ),
			'post_type' => bbp_get_topic_post_type()
		) );

		// If we found some topics, loop through and display them
		if ( ! empty( $topics ) ) {
			foreach ( (array) $topics as $post ) {
				printf( esc_html__( '%s - %s', 'bbpress' ), bbp_get_topic_id( $post->ID ), bbp_get_topic_title( $post->ID ) . "\n" );
			}
		}
		die();
	}

	/**
	 * Ajax action for facilitating the topic and reply author auto-suggest
	 *
	 * @since 2.4.0 bbPress (r5014)
	 */
	public function suggest_user() {

		// Bail early if no request
		if ( empty( $_REQUEST['q'] ) ) {
			wp_die( '0' );
		}

		// Bail if user cannot moderate - only moderators can change authorship
		if ( ! current_user_can( 'moderate' ) ) {
			wp_die( '0' );
		}

		// Check the ajax nonce
		check_ajax_referer( 'bbp_suggest_user_nonce' );

		// Try to get some users
		$users_query = new WP_User_Query( array(
			'search'         => '*' . bbp_db()->esc_like( $_REQUEST['q'] ) . '*',
			'fields'         => array( 'ID', 'user_nicename' ),
			'search_columns' => array( 'ID', 'user_nicename', 'user_email' ),
			'orderby'        => 'ID'
		) );

		// If we found some users, loop through and display them
		if ( ! empty( $users_query->results ) ) {
			foreach ( (array) $users_query->results as $user ) {
				printf( esc_html__( '%s - %s', 'bbpress' ), bbp_get_user_id( $user->ID ), bbp_get_user_nicename( $user->ID, array( 'force' => $user->user_nicename ) ) . "\n" );
			}
		}
		die();
	}

	/** About *****************************************************************/

	/**
	 * Output the about screen
	 *
	 * @since 2.2.0 bbPress (r4159)
	 */
	public function about_screen() {

		list( $display_version ) = explode( '-', bbp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( esc_html__( 'Welcome to bbPress %s', 'bbpress' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( esc_html__( 'Thank you for updating! bbPress %s is sweet and savory, contains no artificial flavors or preservatives, is environmentally friendly, and is a great compliment to your site.', 'bbpress' ), $display_version ); ?></div>
			<div class="bbp-badge"></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-about' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'What&#8217;s New', 'bbpress' ); ?>
				</a><a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-credits' ), 'index.php' ) ) ); ?>">
					<?php esc_html_e( 'Credits', 'bbpress' ); ?>
				</a>
			</h2>

			<div class="changelog">
				<h3><?php esc_html_e( 'Forum Subscriptions', 'bbpress' ); ?></h3>

				<div class="feature-section col two-col">
					<div class="last-feature">
						<h4><?php esc_html_e( 'Subscribe to Forums', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Now your users can subscribe to new topics in specific forums.', 'bbpress' ); ?></p>
					</div>

					<div>
						<h4><?php esc_html_e( 'Manage Subscriptions', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Your users can manage all of their subscriptions in one convenient location.', 'bbpress' ); ?></p>
					</div>
				</div>
			</div>

			<div class="changelog">
				<h3><?php esc_html_e( 'Converters', 'bbpress' ); ?></h3>

				<div class="feature-section col one-col">
					<div class="last-feature">
						<p><?php esc_html_e( 'We&#8217;re all abuzz about the hive of new importers, AEF, Drupal, FluxBB, Kunena Forums for Joomla, MyBB, Phorum, PHPFox, PHPWind, PunBB, SMF, Xenforo and XMB. Existing importers are now sweeter than honey with improved importing stickies, topic tags, forum categories and the sting is now gone if you need to remove imported users.', 'bbpress' ); ?></p>
					</div>
				</div>

				<div class="feature-section col three-col">
					<div>
						<h4><?php esc_html_e( 'Theme Compatibility', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Better handling of styles and scripts in the template stack.', 'bbpress' ); ?></p>
					</div>

					<div>
						<h4><?php esc_html_e( 'Polyglot support', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'bbPress fully supports automatic translation updates.', 'bbpress' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php esc_html_e( 'User capabilities', 'bbpress' ); ?></h4>
						<p><?php esc_html_e( 'Roles and capabilities have been swept through, cleaned up, and simplified.', 'bbpress' ); ?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbpress' ), 'options-general.php' ) ) ); ?>"><?php esc_html_e( 'Go to Forum Settings', 'bbpress' ); ?></a>
			</div>

		</div>

		<?php
	}

	/**
	 * Output the credits screen
	 *
	 * Hardcoding this in here is pretty janky. It's fine for 2.2, but we'll
	 * want to leverage api.wordpress.org eventually.
	 *
	 * @since 2.2.0 bbPress (r4159)
	 */
	public function credits_screen() {

		list( $display_version ) = explode( '-', bbp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( esc_html__( 'Welcome to bbPress %s', 'bbpress' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( esc_html__( 'Thank you for updating! bbPress %s is sweet and savory, contains no artificial flavors or preservatives, is environmentally friendly, and is a great compliment to your site.', 'bbpress' ), $display_version ); ?></div>
			<div class="bbp-badge"></div>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-about' ), 'index.php' ) ) ); ?>" class="nav-tab">
					<?php esc_html_e( 'What&#8217;s New', 'bbpress' ); ?>
				</a><a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbp-credits' ), 'index.php' ) ) ); ?>" class="nav-tab nav-tab-active">
					<?php esc_html_e( 'Credits', 'bbpress' ); ?>
				</a>
			</h2>

			<p class="about-description"><?php esc_html_e( 'bbPress is created by a worldwide swarm of busy, busy bees.', 'bbpress' ); ?></p>

			<h3 class="wp-people-group"><?php esc_html_e( 'Project Leaders', 'bbpress' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-matt">
					<a href="https://profiles.wordpress.org/matt" class="web"><img src="http://0.gravatar.com/avatar/767fc9c115a1b989744c755db47feb60?s=120" class="gravatar" alt="" />Matt Mullenweg</a>
					<span class="title"><?php esc_html_e( 'Founding Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-johnjamesjacoby">
					<a href="https://profiles.wordpress.org/johnjamesjacoby" class="web"><img src="http://0.gravatar.com/avatar/7a2644fb53ae2f7bfd7143b504af396c?s=120" class="gravatar" alt="" />John James Jacoby</a>
					<span class="title"><?php esc_html_e( 'Lead Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-jmdodd">
					<a href="https://profiles.wordpress.org/jmdodd" class="web"><img src="http://0.gravatar.com/avatar/6a7c997edea340616bcc6d0fe03f65dd?s=120" class="gravatar" alt="" />Jennifer M. Dodd</a>
					<span class="title"><?php esc_html_e( 'Feature Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-netweb">
					<a href="https://profiles.wordpress.org/netweb" class="web"><img src="http://0.gravatar.com/avatar/97e1620b501da675315ba7cfb740e80f?s=120" class="gravatar" alt="" />Stephen Edgar</a>
					<span class="title"><?php esc_html_e( 'Converter Specialist', 'bbpress' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group"><?php esc_html_e( 'Contributing Developers', 'bbpress' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-contributing-developers">
				<li class="wp-person" id="wp-person-sergeybiryukov">
					<a href="https://profiles.wordpress.org/SergeyBiryukov" class="web"><img src="http://0.gravatar.com/avatar/750b7b0fcd855389264c2b1294d61bd6?s?s=120" class="gravatar" alt="" />Sergey Biryukov</a>
					<span class="title"><?php esc_html_e( 'Core Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-thebrandonallen">
					<a href="https://profiles.wordpress.org/thebrandonallen" class="web"><img src="http://0.gravatar.com/avatar/6d3f77bf3c9ca94c406dea401b566950?s?s=120" class="gravatar" alt="" />Brandon Allen</a>
					<span class="title"><?php esc_html_e( 'Core Developer', 'bbpress' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group"><?php esc_html_e( 'Project Emeriti', 'bbpress' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-project-emeriti">
				<li class="wp-person" id="wp-person-gautamgupta">
					<a href="https://profiles.wordpress.org/gautamgupta" class="web"><img src="http://0.gravatar.com/avatar/b0810422cbe6e4eead4def5ae7a90b34?s=120" class="gravatar" alt="" />Gautam Gupta</a>
					<span class="title"><?php esc_html_e( 'Feature Developer', 'bbpress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-jaredatch">
					<a href="https://profiles.wordpress.org/jaredatch" class="web"><img src="http://0.gravatar.com/avatar/e341eca9e1a85dcae7127044301b4363?s=120" class="gravatar" alt="" />Jared Atchison</a>
					<span class="title"><?php esc_html_e( 'Integration Testing', 'bbpress' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group"><?php esc_html_e( 'Core Contributors to bbPress 2.6', 'bbpress' ); ?></h3>
			<p class="wp-credits-list">
				<a href="https://profiles.wordpress.org/alex-ye">alex-ye</a>,
				<a href="https://profiles.wordpress.org/ankit-k-gupta">ankit-k-gupta</a>,
				<a href="https://profiles.wordpress.org/barryhughes-1">barryhughes-1</a>,
				<a href="https://profiles.wordpress.org/boonebgorges">boonebgorges</a>,
				<a href="https://profiles.wordpress.org/casiepa">casiepa</a>,
				<a href="https://profiles.wordpress.org/cfinke">cfinke</a>,
				<a href="https://profiles.wordpress.org/danielbachhuber">danielbachhuber</a>,
				<a href="https://profiles.wordpress.org/dimitrovadrian">dimitrov.adrian</a>,
				<a href="https://profiles.wordpress.org/DJPaul">DJPaul</a>,
				<a href="https://profiles.wordpress.org/DrPepper75">DrPepper75</a>,
				<a href="https://profiles.wordpress.org/eoigal">eoigal</a>,
				<a href="https://profiles.wordpress.org/ericlewis">ericlewis</a>,
				<a href="https://profiles.wordpress.org/extendwings">extendwings</a>,
				<a href="https://profiles.wordpress.org/Faison">Faison</a>,
				<a href="https://profiles.wordpress.org/gautamgupta">gautamgupta</a>,
				<a href="https://profiles.wordpress.org/glynwintle">glynwintle</a>,
				<a href="https://profiles.wordpress.org/gusrb84">gusrb84</a>,
				<a href="https://profiles.wordpress.org/hellofromTonya">hellofromTonya</a>,
				<a href="https://profiles.wordpress.org/icu0755">icu0755</a>,
				<a href="https://profiles.wordpress.org/imath">imath</a>,
				<a href="https://profiles.wordpress.org/jbrinley">jbrinley</a>,
				<a href="https://profiles.wordpress.org/jdgrimes">jdgrimes</a>,
				<a href="https://profiles.wordpress.org/jmdodd">jmdodd</a>,
				<a href="https://profiles.wordpress.org/joedolson">joedolson</a>,
				<a href="https://profiles.wordpress.org/johnbillion">johnbillion</a>,
				<a href="https://profiles.wordpress.org/johnjamesjacoby">johnjamesjacoby</a>,
				<a href="https://profiles.wordpress.org/jorbin">jorbin</a>,
				<a href="https://profiles.wordpress.org/jreeve">jreeve</a>,
				<a href="https://profiles.wordpress.org/kadamwhite ">kadamwhite</a>,
				<a href="https://profiles.wordpress.org/karlgroves">karlgroves</a>,
				<a href="https://profiles.wordpress.org/mat-lipe">mat-lipe</a>,
				<a href="https://profiles.wordpress.org/mazengamal">mazengamal</a>,
				<a href="https://profiles.wordpress.org/melchoyce">melchoyce</a>,
				<a href="https://profiles.wordpress.org/mercime">mercime</a>,
				<a href="https://profiles.wordpress.org/michaelbeil">michaelbeil</a>,
				<a href="https://profiles.wordpress.org/mikelopez">mikelopez</a>,
				<a href="https://profiles.wordpress.org/mordauk">mordauk</a>,
				<a href="https://profiles.wordpress.org/mspecht">mspecht</a>,
				<a href="https://profiles.wordpress.org/MZAWeb">MZAWeb</a>,
				<a href="https://profiles.wordpress.org/netweb">netweb</a>,
				<a href="https://profiles.wordpress.org/ocean90">ocean90</a>,
				<a href="https://profiles.wordpress.org/offereins">offereins</a>,
				<a href="https://profiles.wordpress.org/pareshradadiya">pareshradadiya</a>,
				<a href="https://profiles.wordpress.org/r-a-y">r-a-y</a>,
				<a href="https://profiles.wordpress.org/ramiy">ramiy</a>,
				<a href="https://profiles.wordpress.org/robin-w">robin-w</a>,
				<a href="https://profiles.wordpress.org/robkk">robkk</a>,
				<a href="https://profiles.wordpress.org/ryelle">ryelle</a>,
				<a href="https://profiles.wordpress.org/satollo">satollo</a>,
				<a href="https://profiles.wordpress.org/SergeyBiryukov">Sergey Biryukov</a>,
				<a href="https://profiles.wordpress.org/SGr33n">SGr33n</a>,
				<a href="https://profiles.wordpress.org/stephdau">stephdau</a>,
				<a href="https://profiles.wordpress.org/tharsheblows">tharsheblows</a>,
				<a href="https://profiles.wordpress.org/thebrandonallen">thebrandonallen</a>,
				<a href="https://profiles.wordpress.org/tobyhawkins">tobyhawkins</a>,
				<a href="https://profiles.wordpress.org/tonyrix">tonyrix</a>,
				<a href="https://profiles.wordpress.org/treyhunner">treyhunner</a>,
				<a href="https://profiles.wordpress.org/tw2113">tw2113</a>,
				<a href="https://profiles.wordpress.org/xknown">xknown</a>
			</p>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'bbpress' ), 'options-general.php' ) ) ); ?>"><?php esc_html_e( 'Go to Forum Settings', 'bbpress' ); ?></a>
			</div>

		</div>

		<?php
	}

	/** Updaters **************************************************************/

	/**
	 * Update all bbPress forums across all sites
	 *
	 * @since 2.1.0 bbPress (r3689)
	 *
	 * @uses get_blog_option()
	 * @uses wp_remote_get()
	 */
	public static function update_screen() {

		// Get action
		$action = isset( $_GET['action'] ) ? $_GET['action'] : ''; ?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Update Forum', 'bbpress' ); ?></h1>

		<?php

		// Taking action
		switch ( $action ) {
			case 'bbp-update' :

				// Run the full updater
				bbp_version_updater(); ?>

				<p><?php esc_html_e( 'All done!', 'bbpress' ); ?></p>
				<a class="button" href="index.php?page=bbp-update"><?php esc_html_e( 'Go Back', 'bbpress' ); ?></a>

				<?php

				break;

			case 'show' :
			default : ?>

				<p><?php esc_html_e( 'You can update your forum through this page. Hit the link below to update.', 'bbpress' ); ?></p>
				<p><a class="button" href="index.php?page=bbp-update&amp;action=bbp-update"><?php esc_html_e( 'Update Forum', 'bbpress' ); ?></a></p>

			<?php break;

		} ?>

		</div><?php
	}

	/**
	 * Update all bbPress forums across all sites
	 *
	 * @since 2.1.0 bbPress (r3689)
	 *
	 * @uses get_blog_option()
	 * @uses wp_remote_get()
	 */
	public static function network_update_screen() {
		$bbp_db = bbp_db();

		// Get action
		$action = isset( $_GET['action'] ) ? $_GET['action'] : ''; ?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Update Forums', 'bbpress' ); ?></h1>

		<?php

		// Taking action
		switch ( $action ) {
			case 'bbpress-update' :

				// Site counter
				$n = isset( $_GET['n'] ) ? intval( $_GET['n'] ) : 0;

				// Get blogs 5 at a time
				$blogs = $bbp_db->get_results( "SELECT * FROM {$bbp_db->blogs} WHERE site_id = '{$bbp_db->siteid}' AND spam = '0' AND deleted = '0' AND archived = '0' ORDER BY registered DESC LIMIT {$n}, 5", ARRAY_A );

				// No blogs so all done!
				if ( empty( $blogs ) ) : ?>

					<p><?php esc_html_e( 'All done!', 'bbpress' ); ?></p>
					<a class="button" href="update-core.php?page=bbpress-update"><?php esc_html_e( 'Go Back', 'bbpress' ); ?></a>

				<?php

				// Still have sites to loop through
				else : ?>

					<ul>

						<?php foreach ( (array) $blogs as $details ) :

							// Get site URLs
							$site_url   = get_site_url( $details['blog_id'] );
							$admin_url  = get_site_url( $details['blog_id'], 'wp-admin.php', 'admin' );
							$remote_url = add_query_arg( array(
								'page'   => 'bbp-update',
								'action' => 'bbp-update'
							), $admin_url ); ?>

							<li><?php echo esc_html( $site_url ); ?></li>

							<?php

							// Get the response of the bbPress update on this site
							$response = wp_remote_get(
								$remote_url,
								array(
									'timeout'     => 30,
									'httpversion' => '1.1'
								)
							);

							// Site errored out, no response?
							if ( is_wp_error( $response ) ) {
								wp_die( sprintf( esc_html__( 'Warning! Problem updating %1$s. Your server may not be able to connect to sites running on it. Error message: %2$s', 'bbpress' ), $site_url, '<em>' . $response->get_error_message() . '</em>' ) );
							}

							// Switch to the new blog
							switch_to_blog( $details[ 'blog_id' ] );

							$basename = bbpress()->basename;

							// Run the updater on this site
							if ( is_plugin_active_for_network( $basename ) || is_plugin_active( $basename ) ) {
								bbp_version_updater();
							}

							// restore original blog
							restore_current_blog();

							// Do some actions to allow plugins to do things too
							do_action( 'after_bbpress_upgrade', $response             );
							do_action( 'bbp_upgrade_site',      $details[ 'blog_id' ] );

						endforeach; ?>

					</ul>

					<p>
						<?php esc_html_e( 'If your browser doesn&#8217;t start loading the next page automatically, click this link:', 'bbpress' ); ?>
						<a class="button" href="update-core.php?page=bbpress-update&amp;action=bbpress-update&amp;n=<?php echo ( $n + 5 ); ?>"><?php esc_html_e( 'Next Forums', 'bbpress' ); ?></a>
					</p>
					<script type='text/javascript'>
						<!--
						function nextpage() {
							location.href = 'update-core.php?page=bbpress-update&action=bbpress-update&n=<?php echo ( $n + 5 ) ?>';
						}
						setTimeout( 'nextpage()', 250 );
						//-->
					</script><?php

				endif;

				break;

			case 'show' :
			default : ?>

				<p><?php esc_html_e( 'You can update all the forums on your network through this page. It works by calling the update script of each site automatically. Hit the link below to update.', 'bbpress' ); ?></p>
				<p><a class="button" href="update-core.php?page=bbpress-update&amp;action=bbpress-update"><?php esc_html_e( 'Update Forums', 'bbpress' ); ?></a></p>

			<?php break;

		} ?>

		</div><?php
	}
}
endif; // class_exists check

/**
 * Setup bbPress Admin
 *
 * @since 2.0.0 bbPress (r2596)
 *
 * @uses BBP_Admin
 */
function bbp_admin() {
	bbpress()->admin = new BBP_Admin();

	bbpress()->admin->converter = new BBP_Converter();
}
