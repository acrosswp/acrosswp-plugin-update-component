<?php
/**
 * View Analytics.
 *
 * @package AcrossWP\Updater
 * @since View Analytics 1.0.0
 */

  
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Updater-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    AcrossWP
 * @subpackage AcrossWP/Updater
 * @author     AcrossWP <contact@acrosswp.com>
 */
abstract class AcrossWP_Update_Component {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	public $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	public $version;

	/**
	 * The key to check for the update
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	public $key;

	/**
	 * The per page 
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	public $per_page = 10;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $key ) {

		$this->plugin_name		= $plugin_name;
		$this->version_compare	= $version;
		$this->key	= $key;

		$this->update();
	}

	/**
	 * Get all the result
	 */
	abstract public function get_all_result();

	/**
	 * Get the table name
	 */
	abstract public function table_name();

	/**
	 * Get per loop result
	 */
	abstract public function get_result( $per_page, $offset );

	/**
	 * Update row value
	 */
	abstract public function update_result( $results );

	/**
	 * Run this on every event 
	 */
	public function update() {
		
		$update_running = $this->get_option();


		if ( empty( $update_running ) ) {
			
			$update_running = $this->get_all_result_status();

			$this->update_option( $update_running );

			$this->schedule_actions();

		} elseif( isset( $update_running['current_page'] ) ) {

			$current_page = $update_running['current_page'];
			$total_page = $update_running['total_page'];
			$offset = $current_page * $this->per_page;
			$current_page++;

			$results = $this->get_result( $this->per_page, $offset );

			if( ! empty( $results ) ) {
				$this->update_result( $results );
			}

			$update_running = $this->next_page( $update_running, $current_page, $total_page );

			$this->update_option( $update_running );

			$this->schedule_actions();
		}
	}

	/**
	 * get the table name
	 */
	public function get_all_result_count() {
		$results = $this->get_all_result();

		return count( $results );
	}

	/**
	 * get the table name
	 */
	public function get_all_result_status() {

		$count_result = $this->get_all_result_count();

		$total_page = $count_result <= $this->per_page ? 1 : ceil( $count_result/$this->per_page );
		$current_page = 0;

		return array(
			'current_page' => $current_page,
			'count_result' => $count_result,
			'total_page' => $total_page,
		);
	}

	public function next_page( $update_running, $current_page, $total_page ) {
		if( $current_page == $total_page ) {
			$update_running = 'completed';
		} else {
			$update_running['current_page'] = $current_page;
		}

		return $update_running;
	}

	public function update_option( $update_running ) {
		update_option( $this->key, $update_running );
	}

	public function get_option() {
		return get_option( $this->key, false );
	}

	public function schedule_actions() {
		as_schedule_single_action( strtotime( '+1 minutes' ), $this->key, array(), 'view_analytics', true );
	}
}