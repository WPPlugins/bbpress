<?php

/**
 * bbPress Forum Admin Class
 *
 * @package bbPress
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Forums_Admin' ) ) :
/**
 * Loads bbPress forums admin area
 *
 * @package bbPress
 * @subpackage Administration
 * @since 2.0.0 bbPress (r2464)
 */
class BBP_Forums_Admin {

	/** Variables *************************************************************/

	/**
	 * @var string The post type of this admin component
	 */
	private $post_type = '';

	/** Functions *************************************************************/

	/**
	 * The main bbPress forums admin loader
	 *
	 * @since 2.0.0 bbPress (r2515)
	 *
	 * @uses BBP_Forums_Admin::setup_globals() Setup the globals needed
	 * @uses BBP_Forums_Admin::setup_actions() Setup the hooks and actions
	 * @uses BBP_Forums_Admin::setup_help() Setup the help text
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
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
	 * @uses bbp_get_forum_post_type() To get the forum post type
	 * @uses bbp_get_topic_post_type() To get the topic post type
	 * @uses bbp_get_reply_post_type() To get the reply post type
	 */
	private function setup_actions() {

		// Messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Forum Column headers.
		add_filter( 'manage_' . $this->post_type . '_posts_columns',        array( $this, 'column_headers' )        );

		// Forum Columns (in page row)
		add_action( 'manage_' . $this->post_type . '_posts_custom_column',  array( $this, 'column_data'    ), 10, 2 );
		add_filter( 'page_row_actions',                                     array( $this, 'row_actions'    ), 10, 2 );

		// Metabox actions
		add_action( 'add_meta_boxes', array( $this, 'attributes_metabox'    ) );
		add_action( 'add_meta_boxes', array( $this, 'moderators_metabox'    ) );
		add_action( 'add_meta_boxes', array( $this, 'subscriptions_metabox' ) );
		add_action( 'add_meta_boxes', array( $this, 'comments_metabox'      ) );
		add_action( 'save_post',      array( $this, 'save_meta_boxes'       ) );

		// Check if there are any bbp_toggle_forum_* requests on admin_init, also have a message displayed
		add_action( 'load-edit.php',  array( $this, 'toggle_forum'        ) );
		add_action( 'admin_notices',  array( $this, 'toggle_forum_notice' ) );

		// Contextual Help
		add_action( 'load-edit.php',     array( $this, 'edit_help' ) );
		add_action( 'load-post.php',     array( $this, 'new_help'  ) );
		add_action( 'load-post-new.php', array( $this, 'new_help'  ) );
	}

	/**
	 * Admin globals
	 *
	 * @since 2.0.0 bbPress (r2646)
	 *
	 * @access private
	 */
	private function setup_globals() {
		$this->post_type = bbp_get_forum_post_type();
	}

	/** Contextual Help *******************************************************/

	/**
	 * Contextual help for bbPress forum edit page
	 *
	 * @since 2.0.0 bbPress (r3119)
	 *
	 * @uses get_current_screen()
	 */
	public function edit_help() {

		// Overview
		get_current_screen()->add_help_tab( array(
			'id'		=> 'overview',
			'title'		=> __( 'Overview', 'bbpress' ),
			'content'	=>
				'<p>' . __( 'This screen displays the individual forums on your site. You can customize the display of this screen to suit your workflow.', 'bbpress' ) . '</p>'
		) );

		// Screen Content
		get_current_screen()->add_help_tab( array(
			'id'		=> 'screen-content',
			'title'		=> __( 'Screen Content', 'bbpress' ),
			'content'	=>
				'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:', 'bbpress' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'You can hide/display columns based on your needs and decide how many forums to list per screen using the Screen Options tab.',                                                                                                                                'bbpress' ) . '</li>' .
					'<li>' . __( 'You can filter the list of forums by forum status using the text links in the upper left to show All, Published, or Trashed forums. The default view is to show all forums.',                                                                                 'bbpress' ) . '</li>' .
					'<li>' . __( 'You can refine the list to show only forums from a specific month by using the dropdown menus above the forums list. Click the Filter button after making your selection. You also can refine the list by clicking on the forum creator in the forums list.', 'bbpress' ) . '</li>' .
				'</ul>'
		) );

		// Available Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'action-links',
			'title'		=> __( 'Available Actions', 'bbpress' ),
			'content'	=>
				'<p>' . __( 'Hovering over a row in the forums list will display action links that allow you to manage your forum. You can perform the following actions:', 'bbpress' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that forum. You can also reach that screen by clicking on the forum title.',                                                                              'bbpress' ) . '</li>' .
					'<li>' . __( '<strong>Trash</strong> removes your forum from this list and places it in the trash, from which you can permanently delete it.',                                                                                    'bbpress' ) . '</li>' .
					'<li>' . __( '<strong>View</strong> will show you what your draft forum will look like if you publish it. View will take you to your live site to view the forum. Which link is available depends on your forum&#8217;s status.', 'bbpress' ) . '</li>' .
				'</ul>'
		) );

		// Bulk Actions
		get_current_screen()->add_help_tab( array(
			'id'		=> 'bulk-actions',
			'title'		=> __( 'Bulk Actions', 'bbpress' ),
			'content'	=>
				'<p>' . __( 'You can also edit or move multiple forums to the trash at once. Select the forums you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.',           'bbpress' ) . '</p>' .
				'<p>' . __( 'When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected forums at once. To remove a forum from the grouping, just click the x next to its name in the Bulk Edit area that appears.', 'bbpress' ) . '</p>'
		) );

		// Help Sidebar
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'bbpress' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.bbpress.org" target="_blank">bbPress Documentation</a>',    'bbpress' ) . '</p>' .
			'<p>' . __( '<a href="https://bbpress.org/forums/" target="_blank">bbPress Support Forums</a>', 'bbpress' ) . '</p>'
		);
	}

	/**
	 * Contextual help for bbPress forum edit page
	 *
	 * @since 2.0.0 bbPress (r3119)
	 *
	 * @uses get_current_screen()
	 */
	public function new_help() {

		$customize_display = '<p>' . __( 'The title field and the big forum editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.', 'bbpress' ) . '</p>';

		get_current_screen()->add_help_tab( array(
			'id'      => 'customize-display',
			'title'   => __( 'Customizing This Display', 'bbpress' ),
			'content' => $customize_display,
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'title-forum-editor',
			'title'   => __( 'Title and Forum Editor', 'bbpress' ),
			'content' =>
				'<p>' . __( '<strong>Title</strong> - Enter a title for your forum. After you enter a title, you&#8217;ll see the permalink below, which you can edit.', 'bbpress' ) . '</p>' .
				'<p>' . __( '<strong>Forum Editor</strong> - Enter the text for your forum. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your forum text. You can insert media files by clicking the icons above the forum editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular forum editor.', 'bbpress' ) . '</p>'
		) );

		$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your forum in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a forum or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a forum to be published in the future or backdate a forum.', 'bbpress' ) . '</p>';

		if ( current_theme_supports( 'forum-thumbnails' ) && post_type_supports( 'forum', 'thumbnail' ) ) {
			$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to associate an image with your forum without inserting it. This is usually useful only if your theme makes use of the featured image as a forum thumbnail on the home page, a custom header, etc.', 'bbpress' ) . '</p>';
		}

		get_current_screen()->add_help_tab( array(
			'id'      => 'forum-attributes',
			'title'   => __( 'Forum Attributes', 'bbpress' ),
			'content' =>
				'<p>' . __( 'Select the attributes that your forum should have:', 'bbpress' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Type</strong> indicates if the forum is a category or forum. Categories generally contain other forums.',                                                                                'bbpress' ) . '</li>' .
					'<li>' . __( '<strong>Status</strong> allows you to close a forum to new topics and forums.',                                                                                                                  'bbpress' ) . '</li>' .
					'<li>' . __( '<strong>Visibility</strong> lets you pick the scope of each forum and what users are allowed to access it.',                                                                                     'bbpress' ) . '</li>' .
					'<li>' . __( '<strong>Parent</strong> dropdown determines the parent forum. Select the forum or category from the dropdown, or leave the default "No parent" to create the forum at the root of your forums.', 'bbpress' ) . '</li>' .
					'<li>' . __( '<strong>Order</strong> allows you to order your forums numerically.',                                                                                                                            'bbpress' ) . '</li>' .
				'</ul>'
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'publish-box',
			'title'   => __( 'Publish Box', 'bbpress' ),
			'content' => $publish_box,
		) );

		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'bbpress' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.bbpress.org" target="_blank">bbPress Documentation</a>',    'bbpress' ) . '</p>' .
			'<p>' . __( '<a href="https://bbpress.org/forums/" target="_blank">bbPress Support Forums</a>', 'bbpress' ) . '</p>'
		);
	}

	/**
	 * Add the forum attributes meta-box
	 *
	 * @since 2.0.0 bbPress (r2746)
	 *
	 * @uses bbp_get_forum_post_type() To get the forum post type
	 * @uses add_meta_box() To add the meta-box
	 * @uses do_action() Calls 'bbp_forum_attributes_metabox'
	 */
	public function attributes_metabox() {
		add_meta_box(
			'bbp_forum_attributes',
			__( 'Forum Attributes', 'bbpress' ),
			'bbp_forum_metabox',
			$this->post_type,
			'side',
			'high'
		);
	}

	/**
	 * Add the forum moderators meta-box
	 *
	 * @since 2.6.0 bbPress
	 *
	 * @uses bbp_get_forum_post_type() To get the forum post type
	 * @uses add_meta_box() To add the meta-box
	 * @uses do_action() Calls 'bbp_forum_attributes_metabox'
	 */
	public function moderators_metabox() {

		// Bail if feature not active or user cannot assign moderators
		if ( ! bbp_allow_forum_mods() || ! current_user_can( 'assign_moderators' ) ) {
			return;
		}

		// Moderators
		add_meta_box(
			'bbp_moderator_assignment_metabox',
			__( 'Forum Moderators', 'bbpress' ),
			'bbp_moderator_assignment_metabox',
			$this->post_type,
			'side',
			'high'
		);
	}

	/**
	 * Add the subscriptions meta-box
	 *
	 * Allows viewing of users who have subscribed to a forum.
	 *
	 * @since 2.6.0 bbPress (r6197)
	 *
	 * @uses add_meta_box() To add the meta-box
	 */
	public function subscriptions_metabox() {

		// Bail if post_type is not a reply
		if ( empty( $_GET['action'] ) || ( 'edit' !== $_GET['action'] ) ) {
			return;
		}

		// Bail if no subscriptions
		if ( ! bbp_is_subscriptions_active() ) {
			return;
		}

		// Add the meta-box
		add_meta_box(
			'bbp_forum_subscriptions_metabox',
			__( 'Subscriptions', 'bbpress' ),
			'bbp_forum_subscriptions_metabox',
			$this->post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Remove comments & discussion meta-boxes if comments are not supported
	 *
	 * @since 2.6.0 bbPress (r6186)
	 */
	public function comments_metabox() {
		if ( ! post_type_supports( $this->post_type, 'comments' ) ) {
			remove_meta_box( 'commentstatusdiv', $this->post_type, 'normal' );
			remove_meta_box( 'commentsdiv',      $this->post_type, 'normal' );
		}
	}

	/**
	 * Pass the forum attributes for processing
	 *
	 * @since 2.0.0 bbPress (r2746)
	 *
	 * @param int $forum_id Forum id
	 * @uses current_user_can() To check if the current user is capable of
	 *                           editing the forum
	 * @uses bbp_get_forum() To get the forum
	 * @uses bbp_is_forum_closed() To check if the forum is closed
	 * @uses bbp_is_forum_category() To check if the forum is a category
	 * @uses bbp_is_forum_private() To check if the forum is private
	 * @uses bbp_close_forum() To close the forum
	 * @uses bbp_open_forum() To open the forum
	 * @uses bbp_categorize_forum() To make the forum a category
	 * @uses bbp_normalize_forum() To make the forum normal (not category)
	 * @uses bbp_privatize_forum() To mark the forum as private
	 * @uses bbp_publicize_forum() To mark the forum as public
	 * @uses do_action() Calls 'bbp_forum_attributes_metabox_save' with the
	 *                    forum id
	 * @return int Forum id
	 */
	public function save_meta_boxes( $forum_id ) {

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $forum_id;
		}

		// Bail if not a post request
		if ( ! bbp_is_post_request() ) {
			return $forum_id;
		}

		// Nonce check
		if ( empty( $_POST['bbp_forum_metabox'] ) || ! wp_verify_nonce( $_POST['bbp_forum_metabox'], 'bbp_forum_metabox_save' ) ) {
			return $forum_id;
		}

		// Only save for forum post-types
		if ( ! bbp_is_forum( $forum_id ) ) {
			return $forum_id;
		}

		// Bail if current user cannot edit this forum
		if ( ! current_user_can( 'edit_forum', $forum_id ) ) {
			return $forum_id;
		}

		// Parent ID
		$parent_id = ( ! empty( $_POST['parent_id'] ) && is_numeric( $_POST['parent_id'] ) )
			? (int) $_POST['parent_id']
			: 0;

		// Update the forum meta bidness
		bbp_update_forum( array(
			'forum_id'    => $forum_id,
			'post_parent' => $parent_id
		) );

		do_action( 'bbp_forum_attributes_metabox_save', $forum_id );

		return $forum_id;
	}

	/**
	 * Toggle forum
	 *
	 * Handles the admin-side opening/closing of forums
	 *
	 * @since 2.6.0 bbPress (r5254)
	 *
	 * @uses bbp_get_forum() To get the forum
	 * @uses current_user_can() To check if the user is capable of editing
	 *                           the forum
	 * @uses wp_die() To die if the user isn't capable or the post wasn't
	 *                 found
	 * @uses check_admin_referer() To verify the nonce and check referer
	 * @uses bbp_is_forum_open() To check if the forum is open
	 * @uses bbp_close_forum() To close the forum
	 * @uses bbp_open_forum() To open the forum
	 * @uses do_action() Calls 'bbp_toggle_forum_admin' with success, post
	 *                    data, action and message
	 * @uses add_query_arg() To add custom args to the url
	 * @uses bbp_redirect() Redirect the page to custom url
	 */
	public function toggle_forum() {

		// Bail if not a forum toggle action
		if ( ! bbp_is_get_request() || empty( $_GET['action'] ) || empty( $_GET['forum_id'] ) ) {
			return;
		}

		// Bail if not an allowed action
		$action = sanitize_key( $_GET['action'] );
		if ( empty( $action ) || ! in_array( $action, $this->get_allowed_action_toggles(), true ) ) {
			return;
		}

		// Bail if forum is missing
		$forum_id = bbp_get_forum_id( $_GET['forum_id'] );
		if ( ! bbp_get_forum( $forum_id ) ) {
			wp_die( __( 'The forum was not found.', 'bbpress' ) );
		}

		// What is the user doing here?
		if ( ! current_user_can( 'keep_gate', $forum_id ) ) {
			wp_die( __( 'You do not have permission to do that.', 'bbpress' ) );
		}

		// Defaults
		$post_data = array( 'ID' => $forum_id );
		$message   = '';
		$success   = false;

		switch ( $action ) {
			case 'bbp_toggle_forum_close' :
				check_admin_referer( 'close-forum_' . $forum_id );

				$is_open = bbp_is_forum_open( $forum_id );
				$message = ( true === $is_open )
					? 'closed'
					: 'opened';
				$success = ( true === $is_open )
					? bbp_close_forum( $forum_id )
					: bbp_open_forum( $forum_id );

				break;
		}

		// Setup the message
		$retval = array(
			'bbp_forum_toggle_notice' => $message,
			'forum_id'                => $forum_id
		);

		// Prepare for failure
		if ( ( false === $success ) || is_wp_error( $success ) ) {
			$retval['failed'] = '1';
		}

		// Filter all message args
		$retval = apply_filters( 'bbp_toggle_forum_action_admin', $retval, $forum_id, $action );

		// Do additional forum toggle actions (admin side)
		do_action( 'bbp_toggle_forum_admin', $success, $post_data, $action, $retval );

		// Redirect back to the forum
		$redirect = add_query_arg( $retval, remove_query_arg( array( 'action', 'forum_id' ) ) );
		bbp_redirect( $redirect );
	}

	/**
	 * Toggle forum notices
	 *
	 * Display the success/error notices from
	 * {@link BBP_Admin::toggle_forum()}
	 *
	 * @since 2.6.0 bbPress (r5254)
	 *
	 * @uses bbp_get_forum() To get the forum
	 * @uses bbp_get_forum_title() To get the forum title of the forum
	 * @uses esc_html() To sanitize the forum title
	 * @uses apply_filters() Calls 'bbp_toggle_forum_notice_admin' with
	 *                        message, forum id, notice and is it a failure
	 */
	public function toggle_forum_notice() {

		// Bail if missing topic toggle action
		if ( ! bbp_is_get_request() || empty( $_GET['forum_id'] ) || empty( $_GET['bbp_forum_toggle_notice'] ) ) {
			return;
		}

		// Bail if not an allowed notice
		$notice = sanitize_key( $_GET['bbp_forum_toggle_notice'] );
		if ( empty( $notice ) || ! in_array( $notice, $this->get_allowed_notice_toggles(), true ) ) {
			return;
		}

		// Bail if no forum_id or notice
		$forum_id = bbp_get_forum_id( $_GET['forum_id'] );
		if ( empty( $forum_id ) ) {
			return;
		}

		// Bail if forum is missing
		if ( ! bbp_get_forum( $forum_id ) ) {
			return;
		}

		// Use the title in the responses
		$forum_title = bbp_get_forum_title( $forum_id );
		$is_failure  = ! empty( $_GET['failed'] );
		$message     = '';

		switch ( $notice ) {
			case 'opened' :
				$message = ( $is_failure === true )
					? sprintf( __( 'There was a problem opening the forum "%1$s".', 'bbpress' ), $forum_title )
					: sprintf( __( 'Forum "%1$s" successfully opened.',             'bbpress' ), $forum_title );
				break;

			case 'closed' :
				$message = ( $is_failure === true )
					? sprintf( __( 'There was a problem closing the forum "%1$s".', 'bbpress' ), $forum_title )
					: sprintf( __( 'Forum "%1$s" successfully closed.',             'bbpress' ), $forum_title );
				break;
		}

		// Do additional forum toggle notice filters (admin side)
		$message = apply_filters( 'bbp_toggle_forum_notice_admin', $message, $forum_id, $notice, $is_failure );
		$class   = ( $is_failure === true )
			? 'error'
			: 'updated';

		?>

		<div id="message" class="<?php echo esc_html( $class ); ?> fade">
			<p style="line-height: 150%"><?php echo esc_html( $message ); ?></p>
		</div>

		<?php
	}

	/**
	 * Returns an array of notice toggles
	 *
	 * @since 2.6.0 bbPress (r6396)
	 *
	 * @return array
	 */
	private function get_allowed_notice_toggles() {

		// Filter & return
		return apply_filters( 'bbp_admin_forums_allowed_notice_toggles', array(
			'opened',
			'closed'
		) );
	}

	/**
	 * Returns an array of notice toggles
	 *
	 * @since 2.6.0 bbPress (r6396)
	 *
	 * @return array
	 */
	private function get_allowed_action_toggles() {

		// Filter & return
		return apply_filters( 'bbp_admin_forums_allowed_action_toggles', array(
			'bbp_toggle_forum_close'
		) );
	}

	/**
	 * Manage the column headers for the forums page
	 *
	 * @since 2.0.0 bbPress (r2485)
	 *
	 * @param array $columns The columns
	 * @uses apply_filters() Calls 'bbp_admin_forums_column_headers' with
	 *                        the columns
	 * @return array $columns bbPress forum columns
	 */
	public function column_headers( $columns ) {

		// Set list table column headers
		$columns = array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Forum',      'bbpress' ),
			'bbp_forum_topic_count' => __( 'Topics',     'bbpress' ),
			'bbp_forum_reply_count' => __( 'Replies',    'bbpress' ),
			'bbp_forum_mods'        => __( 'Moderators', 'bbpress' ),
			'author'                => __( 'Creator',    'bbpress' ),
			'bbp_forum_created'     => __( 'Created' ,   'bbpress' ),
			'bbp_forum_freshness'   => __( 'Last Post',  'bbpress' )
		);

		// Remove forum mods column if not enabled
		if ( ! bbp_allow_forum_mods() ) {
			unset( $columns['bbp_forum_mods'] );
		}

		// Filter & return
		return apply_filters( 'bbp_admin_forums_column_headers', $columns );
	}

	/**
	 * Print extra columns for the forums page
	 *
	 * @since 2.0.0 bbPress (r2485)
	 *
	 * @param string $column Column
	 * @param int $forum_id Forum id
	 * @uses bbp_forum_topic_count() To output the forum topic count
	 * @uses bbp_forum_reply_count() To output the forum reply count
	 * @uses get_the_date() Get the forum creation date
	 * @uses get_the_time() Get the forum creation time
	 * @uses esc_attr() To sanitize the forum creation time
	 * @uses bbp_get_forum_last_active_time() To get the time when the forum was
	 *                                    last active
	 * @uses do_action() Calls 'bbp_admin_forums_column_data' with the
	 *                    column and forum id
	 */
	public function column_data( $column, $forum_id ) {

		switch ( $column ) {
			case 'bbp_forum_topic_count' :
				bbp_forum_topic_count( $forum_id );
				break;

			case 'bbp_forum_reply_count' :
				bbp_forum_reply_count( $forum_id );
				break;

			case 'bbp_forum_mods' :
				bbp_moderator_list( $forum_id, array(
					'before' => '',
					'after'  => '',
					'none'   => esc_html__( '&mdash;', 'bbpress' )
				) );
				break;

			case 'bbp_forum_created':
				printf( '%1$s <br /> %2$s',
					get_the_date(),
					esc_attr( get_the_time() )
				);

				break;

			case 'bbp_forum_freshness' :
				$last_active = bbp_get_forum_last_active_time( $forum_id, false );
				if ( ! empty( $last_active ) ) {
					echo esc_html( $last_active );
				} else {
					esc_html_e( 'No Topics', 'bbpress' );
				}

				break;

			default:
				do_action( 'bbp_admin_forums_column_data', $column, $forum_id );
				break;
		}
	}

	/**
	 * Forum Row actions
	 *
	 * Remove the quick-edit action link and display the description under
	 * the forum title and add the open/close links
	 *
	 * @since 2.0.0 bbPress (r2577)
	 *
	 * @param array $actions Actions
	 * @param array $forum Forum object
	 * @uses bbp_get_public_status_id() To get the published forum id's
	 * @uses bbp_get_private_status_id() To get the private forum id's
	 * @uses bbp_get_hidden_status_id() To get the hidden forum id's
	 * @uses bbp_get_closed_status_id() To get the closed forum id's
	 * @uses wp_nonce_url() To nonce the url
	 * @uses bbp_is_forum_open() To check if a forum is open
	 * @uses bbp_forum_content() To output forum description
	 * @return array $actions Actions
	 */
	public function row_actions( $actions, $forum ) {

		unset( $actions['inline hide-if-no-js'] );

		// Only show the actions if the user is capable of viewing them :)
		if ( current_user_can( 'keep_gate', $forum->ID ) ) {

			// Show the 'close' and 'open' link on published, private, hidden and closed posts only
			if ( in_array( $forum->post_status, array( bbp_get_public_status_id(), bbp_get_private_status_id(), bbp_get_hidden_status_id(), bbp_get_closed_status_id() ), true ) ) {
				$close_uri = wp_nonce_url( add_query_arg( array( 'forum_id' => $forum->ID, 'action' => 'bbp_toggle_forum_close' ), remove_query_arg( array( 'bbp_forum_toggle_notice', 'forum_id', 'failed', 'super' ) ) ), 'close-forum_' . $forum->ID );
				if ( bbp_is_forum_open( $forum->ID ) ) {
					$actions['closed'] = '<a href="' . esc_url( $close_uri ) . '" title="' . esc_attr__( 'Close this forum', 'bbpress' ) . '">' . _x( 'Close', 'Close a Forum', 'bbpress' ) . '</a>';
				} else {
					$actions['closed'] = '<a href="' . esc_url( $close_uri ) . '" title="' . esc_attr__( 'Open this forum',  'bbpress' ) . '">' . _x( 'Open',  'Open a Forum',  'bbpress' ) . '</a>';
				}
			}
		}

		// simple hack to show the forum description under the title
		bbp_forum_content( $forum->ID );

		return $actions;
	}

	/**
	 * Custom user feedback messages for forum post type
	 *
	 * @since 2.0.0 bbPress (r3080)
	 *
	 * @global int $post_ID
	 * @uses bbp_get_forum_permalink()
	 * @uses wp_post_revision_title()
	 * @uses esc_url()
	 * @uses add_query_arg()
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	public function updated_messages( $messages ) {
		global $post_ID;

		// URL for the current forum
		$forum_url = bbp_get_forum_permalink( $post_ID );

		// Current forum's post_date
		$post_date = bbp_get_global_post_field( 'post_date', 'raw' );

		// Messages array
		$messages[ $this->post_type ] = array(
			0 =>  '', // Left empty on purpose

			// Updated
			1 =>  sprintf(
				'%1$s <a href="%2$s">%3$s</a>',
				__( 'Forum updated.', 'bbpress' ),
				$forum_url,
				__( 'View forum', 'bbpress' )
			),

			// Custom field updated
			2 => __( 'Custom field updated.', 'bbpress' ),

			// Custom field deleted
			3 => __( 'Custom field deleted.', 'bbpress' ),

			// Forum updated
			4 => __( 'Forum updated.', 'bbpress' ),

			// Restored from revision
			// translators: %s: date and time of the revision
			5 => isset( $_GET['revision'] )
					? sprintf( __( 'Forum restored to revision from %s', 'bbpress' ), wp_post_revision_title( (int) $_GET['revision'], false ) )
					: false,

			// Forum created
			6 => sprintf(
				'%1$s <a href="%2$s">%3$s</a>',
				__( 'Forum created.', 'bbpress' ),
				$forum_url,
				__( 'View forum', 'bbpress' )
			),

			// Forum saved
			7 => __( 'Forum saved.', 'bbpress' ),

			// Forum submitted
			8 => sprintf(
				'%1$s <a href="%2$s" target="_blank">%3$s</a>',
				__( 'Forum submitted.', 'bbpress' ),
				esc_url( add_query_arg( 'preview', 'true', $forum_url ) ),
				__( 'Preview forum', 'bbpress' )
			),

			// Forum scheduled
			9 => sprintf(
				'%1$s <a target="_blank" href="%2$s">%3$s</a>',
				sprintf(
					__( 'Forum scheduled for: %s.', 'bbpress' ),
					// translators: Publish box date format, see http://php.net/date
					'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'bbpress' ), strtotime( $post_date ) ) . '</strong>'
				),
				$forum_url,
				__( 'Preview forum', 'bbpress' )
			),

			// Forum draft updated
			10 => sprintf(
				'%1$s <a href="%2$s" target="_blank">%3$s</a>',
				__( 'Forum draft updated.', 'bbpress' ),
				esc_url( add_query_arg( 'preview', 'true', $forum_url ) ),
				__( 'Preview forum', 'bbpress' )
			),
		);

		return $messages;
	}
}
endif; // class_exists check

/**
 * Setup bbPress Forums Admin
 *
 * This is currently here to make hooking and unhooking of the admin UI easy.
 * It could use dependency injection in the future, but for now this is easier.
 *
 * @since 2.0.0 bbPress (r2596)
 *
 * @param WP_Screen $current_screen Current screen object
 * @uses BBP_Forums_Admin
 */
function bbp_admin_forums( $current_screen ) {

	// Bail if not a forum screen
	if ( empty( $current_screen->post_type ) || ( bbp_get_forum_post_type() !== $current_screen->post_type ) ) {
		return;
	}

	// Init the forums admin
	bbpress()->admin->forums = new BBP_Forums_Admin();
}
