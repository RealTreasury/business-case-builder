<?php
defined( 'ABSPATH' ) || exit;

/**
 * Workflow tracker for monitoring AJAX generation steps.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_Workflow_Tracker {
/**
 * Recorded steps.
 *
 * @var array
 */
private $steps = [];

/**
 * Recorded warnings.
 *
 * @var array
 */
private $warnings = [];

/**
 * Recorded errors.
 *
 * @var array
 */
private $errors = [];

/**
 * Count of AI calls made.
 *
 * @var int
 */
private $ai_calls = 0;

/**
 * Start tracking a workflow step.
 *
 * @param string $name Step identifier.
 * @param mixed  $data Optional data.
 * @return void
 */
public function start_step( $name, $data = null ) {
$this->steps[ $name ] = [
'start' => microtime( true ),
'data'  => $data,
];
if ( false !== strpos( $name, 'ai' ) ) {
$this->ai_calls++;
}
}

/**
 * Mark a step as complete.
 *
 * @param string $name   Step identifier.
 * @param mixed  $result Result data.
 * @return void
 */
public function complete_step( $name, $result = null ) {
if ( isset( $this->steps[ $name ] ) ) {
$this->steps[ $name ]['end']    = microtime( true );
$this->steps[ $name ]['result'] = $result;
}
}

/**
 * Add a warning.
 *
 * @param string $code    Warning code.
 * @param string $message Warning message.
 * @return void
 */
public function add_warning( $code, $message ) {
$this->warnings[] = [
'code'    => $code,
'message' => $message,
];
}

/**
 * Add an error.
 *
 * @param string $code    Error code.
 * @param string $message Error message.
 * @return void
 */
public function add_error( $code, $message ) {
$this->errors[] = [
'code'    => $code,
'message' => $message,
];
}

/**
 * Retrieve list of completed steps.
 *
 * @return array
 */
public function get_completed_steps() {
return array_keys( $this->steps );
}

/**
 * Get number of AI-related calls.
 *
 * @return int
 */
public function get_ai_call_count() {
return $this->ai_calls;
}

/**
 * Get recorded warnings.
 *
 * @return array
 */
public function get_warnings() {
return $this->warnings;
}

/**
 * Get debugging information.
 *
 * @return array
 */
public function get_debug_info() {
return [
'steps'    => $this->steps,
'warnings' => $this->warnings,
'errors'   => $this->errors,
];
}
}

