<?php
/**
 * Plugin Name:       Cookie Consent Analytics
 * Plugin URI:        https://github.com/superhuit-agency/wp-cookie-consent-analytics
 * Description:       Cookie Consent Analytics empowers you to track user interactions with your cookie banner through dedicated API routes that record acceptances, rejections, and personalization choices. The plugin includes a user-friendly dashboard to visualize analytics with interactive graphs and enables you to easily export your data in CSV format for further analysis.
 * Author:            superhuit
 * Author URI:        https://www.superhuit.ch
 * Version:           1.0.0
 * license:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Requires PHP:      8.2
 * Text Domain:       cookie-analytics
 * Requires at least: 6.0
 * Tested up to:      6.8
 *
 * @package WpCookieConsentAnalytics
 * @category Core
 * @author Superhuit, Snugglejuice
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COOKIE_ANALYTICS_VERSION', '1.0.0' );
define( 'COOKIE_ANALYTICS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COOKIE_ANALYTICS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load classes via Composer autoload.
if ( ! file_exists( COOKIE_ANALYTICS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function() {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'Please install composer dependencies for Cookie Consent Analytics to work.', 'cookie-analytics' ); ?></p>
			</div>
			<?php
		}
	);
	return;
}

require_once COOKIE_ANALYTICS_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Activation hook: create database tables and schedule cleanup.
 */
function cookie_analytics_activate() {
	Cookie_Analytics_DB::create_tables();
	if ( ! wp_next_scheduled( 'cookie_analytics_daily_cleanup' ) ) {
		wp_schedule_event( time(), 'daily', 'cookie_analytics_daily_cleanup' );
	}
}
register_activation_hook( __FILE__, 'cookie_analytics_activate' );

/**
 * Deactivation hook: clear scheduled cleanup.
 */
function cookie_analytics_deactivate() {
	wp_clear_scheduled_hook( 'cookie_analytics_daily_cleanup' );
}
register_deactivation_hook( __FILE__, 'cookie_analytics_deactivate' );

/**
 * Daily cleanup: delete events older than the retention period.
 */
function cookie_analytics_run_cleanup() {
	$days = Cookie_Analytics_DB::get_retention_days();
	if ( $days > 0 ) {
		Cookie_Analytics_DB::delete_events_older_than( $days );
	}
}
add_action( 'cookie_analytics_daily_cleanup', 'cookie_analytics_run_cleanup' );

/**
 * Initialize plugin components.
 */
function cookie_analytics_init() {
	new Cookie_Analytics_API();
	if ( is_admin() ) {
		new Cookie_Analytics_Admin();
	}
}
add_action( 'plugins_loaded', 'cookie_analytics_init' );
