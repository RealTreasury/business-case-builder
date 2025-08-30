<?php
/**
 * Admin Workflow Visualizer.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the admin workflow visualizer page.
 */
class RTBCB_Admin_Workflow_Visualizer {
	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'wp_ajax_rtbcb_get_workflow_history', [ $this, 'ajax_get_workflow_history' ] );
		add_action( 'wp_ajax_rtbcb_clear_workflow_history', [ $this, 'ajax_clear_workflow_history' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add submenu page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'rtbcb-dashboard',
			__( 'Workflow Visualizer', 'rtbcb' ),
			__( 'Workflow Visualizer', 'rtbcb' ),
			'manage_options',
			'rtbcb-workflow-visualizer',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		include RTBCB_DIR . 'admin/workflow-visualizer-page.php';
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'rtbcb-workflow-visualizer' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'rtbcb-workflow-visualizer',
			RTBCB_URL . 'admin/css/workflow-visualizer.css',
			[],
			RTBCB_VERSION
		);

		wp_enqueue_script(
			'rtbcb-workflow-visualizer',
			RTBCB_URL . 'admin/js/workflow-visualizer.js',
			[ 'jquery' ],
			RTBCB_VERSION,
			true
		);

		wp_localize_script(
			'rtbcb-workflow-visualizer',
			'rtbcbWorkflow',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'rtbcb_workflow_visualizer' ),
				'strings'  => [
					'refresh_success' => __( 'Workflow history refreshed', 'rtbcb' ),
					'clear_success'   => __( 'Workflow history cleared', 'rtbcb' ),
					'error'           => __( 'An error occurred', 'rtbcb' ),
				],
			]
		);
	}

	/**
	 * AJAX handler to get workflow history.
	 *
	 * @return void
	 */
	public function ajax_get_workflow_history() {
		if ( ! check_ajax_referer( 'rtbcb_workflow_visualizer', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'rtbcb' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'rtbcb' ) );
		}

		$history = $this->get_workflow_history_from_logs();

		wp_send_json_success(
			[
				'history' => $history,
				'summary' => [
					'total_executions' => count( $history ),
					'avg_duration'     => $this->calculate_average_duration( $history ),
					'success_rate'     => $this->calculate_success_rate( $history ),
				],
			]
		);
	}

	/**
	 * AJAX handler to clear workflow history.
	 *
	 * @return void
	 */
	public function ajax_clear_workflow_history() {
		if ( ! check_ajax_referer( 'rtbcb_workflow_visualizer', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'rtbcb' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'rtbcb' ) );
		}

		// Placeholder: implement actual clearing of logs if needed.
		wp_send_json_success();
	}

	/**
	 * Retrieve workflow history from logs.
	 *
	 * @return array
	 */
	private function get_workflow_history_from_logs() {
		return [];
	}

	/**
	 * Calculate average duration.
	 *
	 * @param array $history Workflow history.
	 *
	 * @return float
	 */
	private function calculate_average_duration( $history ) {
		if ( empty( $history ) ) {
			return 0;
		}
		$total = 0;
		$count = 0;
		foreach ( $history as $run ) {
			if ( isset( $run['duration'] ) ) {
				$total += (float) $run['duration'];
				$count++;
			}
		}
		return $count ? round( $total / $count, 2 ) : 0;
	}

	/**
	 * Calculate success rate percentage.
	 *
	 * @param array $history Workflow history.
	 *
	 * @return float
	 */
	private function calculate_success_rate( $history ) {
		if ( empty( $history ) ) {
			return 0;
		}
		$success = 0;
		foreach ( $history as $run ) {
			if ( isset( $run['status'] ) && 'success' === $run['status'] ) {
				$success++;
			}
		}
		return round( ( $success / count( $history ) ) * 100, 2 );
	}
}
