<?php
/**
 * Main admin file which loads all settings panels and sets up the menus.
 *
 * DISCLAIMER
 *
 * Do not edit or add directly to this file if you wish to upgrade Jigoshop to newer
 * versions in the future. If you wish to customise Jigoshop core for your needs,
 * please use our GitHub repository to publish essential changes for consideration.
 *
 * @package             Jigoshop
 * @category            Admin
 * @author              Jigowatt
 * @copyright           Copyright © 2011-2012 Jigowatt Ltd.
 * @license             http://jigoshop.com/license/commercial-edition
 */

require_once( 'jigoshop-install.php' );
require_once( 'jigoshop-write-panels.php' );
require_once( 'jigoshop-admin-settings-api.php' );
require_once( 'jigoshop-admin-attributes.php' );
require_once( 'jigoshop-admin-post-types.php' );
require_once( 'jigoshop-admin-product-quick-bulk-edit.php' );
require_once( 'jigoshop-admin-taxonomies.php' );

// Contextual help only works for 3.3 due to updated API
if ( get_bloginfo('version') >= '3.3' ) {
	require_once( 'jigoshop-admin-help.php' );
}

add_action('admin_init', 'jigoshop_admin_init');
function jigoshop_admin_init() {
	add_action('wp_dashboard_setup', 'jigoshop_setup_dashboard_widgets' );
}

add_action('admin_notices', 'jigoshop_update');
function jigoshop_update() {
	// Run database upgrade if required
	if ( is_admin() && get_site_option('jigoshop_db_version') < JIGOSHOP_VERSION ) {

		if ( isset($_GET['jigoshop_update_db']) && (bool) $_GET['jigoshop_update_db'] ) {
			require_once( jigoshop::plugin_path().'/jigoshop_upgrade.php' );
			$response = jigoshop_upgrade();

		} else {

			// Display upgrade nag
			echo '
				<div class="update-nag">
					'.sprintf(__('Your database needs an update. Please <strong>backup</strong> &amp; %s.', 'jigoshop'), '<a href="' . add_query_arg('jigoshop_update_db', 'true') . '">' . __('update now', 'jigoshop') . '</a>').'
				</div>
			';
		}
	}
}

/**
 * Admin Menus
 *
 * Sets up the admin menus in wordpress.
 *
 * @since 		1.0
 */
add_action('admin_menu', 'jigoshop_before_admin_menu', 9);
function jigoshop_before_admin_menu() {

	global $menu;

	$menu[54] = array( '', 'read', 'separator-jigoshop', '', 'wp-menu-separator jigoshop' );

	add_menu_page( __('Jigoshop'), __('Jigoshop'), 'manage_options', 'jigoshop', 'jigoshop_dashboard', null, 55);
	add_submenu_page('jigoshop', __('Dashboard', 'jigoshop'), __('Dashboard', 'jigoshop'), 'manage_options', 'jigoshop', 'jigoshop_dashboard');
	add_submenu_page('jigoshop', __('Reports','jigoshop'), __('Reports','jigoshop'), 'manage_options', 'jigoshop_reports', 'jigoshop_reports');
	add_submenu_page('edit.php?post_type=product', __('Attributes','jigoshop'), __('Attributes','jigoshop'), 'manage_options', 'jigoshop_attributes', 'jigoshop_attributes');

	do_action('jigoshop_before_admin_menu');

}

add_action('admin_menu', 'jigoshop_after_admin_menu', 50);
function jigoshop_after_admin_menu() {

	$admin_page = add_submenu_page( 'jigoshop', __( 'Settings' ), __( 'Settings' ), 'manage_options', 'jigoshop_settings', array( Jigoshop_Admin_Settings::instance(), 'output_markup' ) );
	add_action( 'admin_print_scripts-' . $admin_page, array( Jigoshop_Admin_Settings::instance(), 'settings_scripts' ) );
	add_action( 'admin_print_styles-' . $admin_page, array( Jigoshop_Admin_Settings::instance(), 'settings_styles' ) );

	add_submenu_page( 'jigoshop', __('System Information','jigoshop'), __('System Info','jigoshop'), 'manage_options', 'jigoshop_system_info', 'jigoshop_system_info');

	do_action('jigoshop_after_admin_menu');

}

function jigoshop_reports() {
	require_once( 'jigoshop-admin-reports.php' );
	$jigoshop_dashboard = new Jigoshop_reports();
}

function jigoshop_dashboard() {
	require_once( 'jigoshop-admin-dashboard.php' );
	$jigoshop_dashboard = new jigoshop_dashboard();
}

/**
 * Admin Head
 *
 * Outputs some styles in the admin <head> to show icons on the jigoshop admin pages
 *
 * @since 		1.0
 */
function jigoshop_admin_head() {
	?>
	<style type="text/css">

		<?php if ( isset($_GET['taxonomy']) && $_GET['taxonomy']=='product_cat' ) : ?>
			.icon32-posts-product { background-position: -243px -5px !important; }
		<?php elseif ( isset($_GET['taxonomy']) && $_GET['taxonomy']=='product_tag' ) : ?>
			.icon32-posts-product { background-position: -301px -5px !important; }
		<?php endif; ?>

	</style>
	<?php
}
add_action('admin_head', 'jigoshop_admin_head');

/**
 * System info
 *
 * Shows the system info panel which contains version data and debug info
 *
 * @since 		1.0
 * @usedby 		jigoshop_settings()
 */
function jigoshop_system_info() {
	?>
<div class="wrap jigoshop">
		<div class="icon32 icon32-jigoshop-debug" id="icon-jigoshop"><br/></div>
		<h2><?php _e('System Information','jigoshop') ?></h2>
		<p>Use the information below when submitting technical support requests via <a href="http://jigoshop.com/support/" title="Jigoshop Support" target="_blank">Jigoshop Support</a>.</p>

<textarea readonly="readonly" id="system-info-textarea" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).">

	### Begin System Info ###

	Multi-site:               <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

	SITE_URL:                 <?php echo site_url() . "\n"; ?>
	HOME_URL:                 <?php echo home_url() . "\n"; ?>

	Jigoshop Version:         <?php echo jigoshop_get_plugin_data() . "\n"; ?>
	WordPress Version:        <?php echo get_bloginfo('version') . "\n"; ?>
	
	<?php require_once('browser.php'); $browser =  new Browser(); echo $browser ; ?>
	
	PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
	MySQL Version:            <?php echo mysql_get_server_info() . "\n"; ?>
	Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

	PHP Memory Limit:         <?php echo ini_get('memory_limit') . "\n"; ?>
	PHP Post Max Size:        <?php echo ini_get('post_max_size') . "\n"; ?>

	WP_DEBUG:                 <?php echo defined('WP_DEBUG') ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

	Show On Front:            <?php echo get_option('show_on_front') . "\n" ?>
	Page On Front:            <?php echo get_option('page_on_front') . "\n" ?>
	Page For Posts:           <?php echo get_option('page_for_posts') . "\n" ?>
	
	Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
	Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
	Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
	Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
	Use Cookies:              <?php echo (ini_get('session.use_cookies') ? 'On' : 'Off'); ?><?php echo "\n"; ?>
	Use Only Cookies:         <?php echo (ini_get('session.use_only_cookies') ? 'On' : 'Off'); ?><?php echo "\n"; ?>

	UPLOAD_MAX_FILESIZE:      <?php if(function_exists('phpversion')) echo (jigoshop_let_to_num(ini_get('upload_max_filesize'))/(1024*1024))."MB"; ?><?php echo "\n"; ?>
	POST_MAX_SIZE:            <?php if(function_exists('phpversion')) echo (jigoshop_let_to_num(ini_get('post_max_size'))/(1024*1024))."MB"; ?><?php echo "\n"; ?>
	WordPress Memory Limit:   <?php echo (jigoshop_let_to_num(WP_MEMORY_LIMIT)/(1024*1024))."MB"; ?><?php echo "\n"; ?>
	WP_DEBUG:                 <?php echo (WP_DEBUG) ? __('On', 'jigoshop') : __('Off', 'jigoshop'); ?><?php echo "\n"; ?>
	DISPLAY ERRORS:           <?php echo (ini_get('display_errors')) ? 'On (' . ini_get('display_errors') . ')' : 'N/A'; ?><?php echo "\n"; ?>
	FSOCKOPEN:                <?php echo (function_exists('fsockopen')) ? __('Your server supports fsockopen.', 'jigoshop') : __('Your server does not support fsockopen.', 'jigoshop'); ?><?php echo "\n"; ?>

	ACTIVE PLUGINS:

<?php
$plugins = get_plugins();
$active_plugins = get_option('active_plugins', array());

foreach ( $plugins as $plugin_path => $plugin ):

	//If the plugin isn't active, don't show it.
	if ( !in_array($plugin_path, $active_plugins) )
		continue;
?>
	<?php echo $plugin['Name']; ?>: <?php echo $plugin['Version']; ?>

<?php endforeach; ?>

	CURRENT THEME:

	<?php 
	if ( get_bloginfo('version') < '3.4' ) {
		$theme_data = get_theme_data(get_stylesheet_directory() . '/style.css');
		echo $theme_data['Name'] . ': ' . $theme_data['Version'];
	} else {
		$theme_data = wp_get_theme();
		echo $theme_data->Name . ': ' . $theme_data->Version;
	}
?>


	### End System Info ###
</textarea>

	</div>
</div>
<?php
}

function jigoshop_get_plugin_data( $key = 'Version' ) {
	$data = get_plugin_data( jigoshop::plugin_path().'/jigoshop.php' );

	return $data[$key];
}

function jigoshop_feature_product() {

	if( !is_admin() ) die;

	if( !current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.') );

	// if( !check_admin_referer()) wp_die( __('You have taken too long. Please go back and retry.', 'jigoshop') );

	$post_id = isset($_GET['product_id']) && (int)$_GET['product_id'] ? (int)$_GET['product_id'] : '';

	if(!$post_id) die;

	$post = get_post($post_id);
	if(!$post) die;

	if($post->post_type !== 'product') die;

	$product = new jigoshop_product($post->ID);

	update_post_meta( $post->ID, 'featured', ! $product->is_featured() );

	$sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
	wp_redirect( $sendback );
	exit;

}
add_action('wp_ajax_jigoshop-feature-product', 'jigoshop_feature_product');

/**
 * Returns proper post_type
 */
function jigoshop_get_current_post_type() {

	global $post, $typenow, $current_screen;

	if( $current_screen && @$current_screen->post_type ) return $current_screen->post_type;

	if( $typenow ) return $typenow;

	if( !empty($_REQUEST['post_type']) ) return sanitize_key( $_REQUEST['post_type'] );

	if ( !empty($post) && !empty($post->post_type) ) return $post->post_type;

	if( ! empty($_REQUEST['post']) && (int)$_REQUEST['post'] ) {
		$p = get_post( $_REQUEST['post'] );
		return $p ? $p->post_type : '';
	}

	return '';
}

/**
 * Categories ordering
 */

/**
 * Load needed scripts to order categories
 */
function jigoshop_categories_scripts() {

	if( !isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'product_cat') return;

	wp_register_script('jigoshop-categories-ordering', jigoshop::assets_url() . '/assets/js/categories-ordering.js', array('jquery-ui-sortable'));
	wp_print_scripts('jigoshop-categories-ordering');

}
add_action('admin_footer-edit-tags.php', 'jigoshop_categories_scripts');

/**
 * Ajax request handling for categories ordering
 */
function jigoshop_categories_ordering() {

	global $wpdb;

	$id = (int)$_POST['id'];
	$next_id  = isset($_POST['nextid']) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;

	if( ! $id || ! $term = get_term_by('id', $id, 'product_cat') ) die(0);

	jigoshop_order_categories( $term, $next_id);

	$children = get_terms('product_cat', "child_of=$id&menu_order=ASC&hide_empty=0");
	if( $term && sizeof($children) ) {
		echo 'children';
		die;
	}

}
add_action('wp_ajax_jigoshop-categories-ordering', 'jigoshop_categories_ordering');


if (!function_exists('boolval')) {
	/**
	 * Helper function to get the boolean value of a variable. If not strict, this function will return true
	 * if the variable is not false and not empty. If strict, the value of the variable must exactly match a
	 * value in the true test array to evaluate to true
	 *
	 * @param $in The input variable
	 * @param bool $strict
	 * @return bool|null|string
	 */
	function boolval($in, $strict = false) {
		if (is_bool($in)){
			return $in;
		}
		$in = strtolower($in);
		$out = null;
		if (in_array($in, array('false', 'no', 'n', 'off', '0', 0, null), true)) {
			$out = false;
		} else if ($strict) {
			if (in_array($in, array('true', 'yes', 'y', 'on', '1', 1), true)) {
				$out = true;
			}
		} else {
			$out = ($in ? true : false);
		}
		return $out;
	}
}

/**
 * Replaces Recent Comments Dashboard widget with shop message filtered ver
 *
 * The only difference between the two is the query now takes into account
 * shop order messages. Unfortunately WordPress hasn't made this very easy
 * to achieve due to the query being hard coded so a complete
 * replacement was necessary :(
 *
 * Sourced from dashboard.php
 */
function jigoshop_setup_dashboard_widgets() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	wp_add_dashboard_widget('jigoshop_recent_comments', 'Recent Comments', 'jigoshop_dashboard_recent_comments', 'jigoshop_dashboard_recent_comments_control');
}

function jigoshop_dashboard_recent_comments() {
	global $wpdb;

	if ( current_user_can('edit_posts') )
		$allowed_states = array('0', '1');
	else
		$allowed_states = array('1');

	// Select all comment types and filter out spam later for better query performance.
	$comments = array();
	$start = 0;

	$widgets = get_option( 'dashboard_widget_options' );
	$total_items = isset( $widgets['dashboard_recent_comments'] ) && isset( $widgets['dashboard_recent_comments']['items'] )
		? absint( $widgets['dashboard_recent_comments']['items'] ) : 5;

	while ( count( $comments ) < $total_items && $possible = $wpdb->get_results( "SELECT * FROM $wpdb->comments c LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID WHERE p.post_status != 'trash' AND c.comment_type != 'jigoshop' ORDER BY c.comment_date_gmt DESC LIMIT $start, 50" ) ) {

		foreach ( $possible as $comment ) {
			if ( count( $comments ) >= $total_items )
				break;
			if ( in_array( $comment->comment_approved, $allowed_states ) && current_user_can( 'read_post', $comment->comment_post_ID ) )
				$comments[] = $comment;
		}

		$start = $start + 50;
	}

	if ( $comments ) :
?>

		<div id="the-comment-list" class="list:comment">
<?php
		foreach ( $comments as $comment )
			_wp_dashboard_recent_comments_row( $comment );
?>

		</div>

<?php
		if ( current_user_can('edit_posts') ) { ?>
			<?php _get_list_table('WP_Comments_List_Table')->views(); ?>
<?php	}

		wp_comment_reply( -1, false, 'dashboard', false );
		wp_comment_trashnotice();

	else :
?>

	<p><?php _e( 'No comments yet.' ); ?></p>

<?php
	endif; // $comments;
}

/**
 * The recent comments dashboard widget control.
 *
 * @since 3.0.0
 */
function jigoshop_dashboard_recent_comments_control() {
    $jigoshop_options = Jigoshop_Base::get_options();
	if ( !$widget_options = get_option( 'dashboard_widget_options' ) )
		$widget_options = array();

	if ( !isset($widget_options['dashboard_recent_comments']) )
		$widget_options['dashboard_recent_comments'] = array();

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget-recent-comments']) ) {
		$number = absint( $_POST['widget-recent-comments']['items'] );
		$widget_options['dashboard_recent_comments']['items'] = $number;
		$jigoshop_options->set_option( 'dashboard_widget_options', $widget_options );
	}

	$number = isset( $widget_options['dashboard_recent_comments']['items'] ) ? (int) $widget_options['dashboard_recent_comments']['items'] : '';

	echo '<p><label for="comments-number">' . __('Number of comments to show:') . '</label>';
	echo '<input id="comments-number" name="widget-recent-comments[items]" type="text" value="' . esc_attr( $number ) . '" size="3" /></p>';
}