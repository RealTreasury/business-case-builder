<?php
defined( 'ABSPATH' ) || exit;

/**
	* Workflow Tracker for monitoring and debugging the report generation process.
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
	* Currently running step.
	*
	* @var array|null
	*/
private $current_step = null;

/**
	* Workflow start time.
	*
	* @var float
	*/
private $start_time;

/**
	* Workflow start timestamp.
	*
	* @var string
	*/
private $start_timestamp;

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
	* Recorded prompts sent to the AI.
	*
	* @var array
	*/
private $prompts = [];

/**
	* Constructor.
	*/
public function __construct() {
$this->start_time      = microtime( true );
$this->start_timestamp = function_exists( 'current_time' ) ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s' );
}

/**
	* Start a new workflow step.
	*
	* @param string $step_name  Step identifier.
	* @param mixed  $input_data Optional input data.
	* @return void
	*/
public function start_step( $step_name, $input_data = null ) {
$this->current_step = [
'name'       => $step_name,
'start_time' => microtime( true ),
'input_data' => $input_data,
'status'     => 'running',
];

       RTBCB_Logger::log(
               'workflow_start',
               [
                       'step' => $step_name,
               ]
       );
}

/**
	* Complete the current workflow step.
	*
	* @param string $step_name   Step identifier.
	* @param mixed  $output_data Optional output data.
	* @return void
	*/
public function complete_step( $step_name, $output_data = null ) {
if ( $this->current_step && $this->current_step['name'] === $step_name ) {
$this->current_step['end_time']     = microtime( true );
$this->current_step['duration']     = $this->current_step['end_time'] - $this->current_step['start_time'];
$this->current_step['output_data']  = $output_data;
$this->current_step['status']       = 'completed';
$this->current_step['memory_usage'] = memory_get_usage( true );

if ( $this->is_ai_step( $step_name ) ) {
$this->ai_calls++;
}

$this->steps[]     = $this->current_step;
$this->current_step = null;

do_action( 'rtbcb_workflow_step_completed', $step_name );

       RTBCB_Logger::log(
               'workflow_step_completed',
               [
                       'step'     => $step_name,
                       'duration' => round( $this->steps[ count( $this->steps ) - 1 ]['duration'], 2 ),
               ]
       );
}
}

/**
	* Add a warning to the current or last step.
	*
	* @param string $code    Warning code.
	* @param string $message Warning message.
	* @return void
	*/
public function add_warning( $code, $message ) {
	$step_name = $this->current_step ? $this->current_step['name'] : ( $this->steps ? end( $this->steps )['name'] : 'unknown' );
	$warning   = [
		'code'      => $code,
		'message'   => $message,
		'timestamp' => microtime( true ),
		'step'      => $step_name,
	];

	$this->warnings[] = $warning;
       rtbcb_log_error(
               'Workflow warning',
               [
                       'code'    => $code,
                       'message' => $message,
                       'step'    => $step_name,
               ]
       );
	do_action( 'rtbcb_workflow_warning', $warning );
}

/**
	* Add an error to the workflow.
	*
	* @param string $code    Error code.
	* @param string $message Error message.
	* @return void
	*/
public function add_error( $code, $message ) {
	$step_name = $this->current_step ? $this->current_step['name'] : ( $this->steps ? end( $this->steps )['name'] : 'unknown' );
	$error     = [
		'code'      => $code,
		'message'   => $message,
		'timestamp' => microtime( true ),
		'step'      => $step_name,
	];

	$this->errors[] = $error;
       rtbcb_log_error(
               'Workflow error',
               [
                       'code'    => $code,
                       'message' => $message,
                       'step'    => $step_name,
               ]
       );
	do_action( 'rtbcb_workflow_error', $error );
}

/**
        * Get completed steps summary.
        *
        * @return array
        */
       public function get_completed_steps() {
               return array_map(
                       function( $step ) {
                               return [
                                       'name'     => $step['name'],
                                       'duration' => round( $step['duration'], 2 ),
                                       'elapsed'  => round( $step['end_time'] - $this->start_time, 2 ),
                                       'status'   => $step['status'],
                                       'memory_mb' => round( $step['memory_usage'] / 1024 / 1024, 1 ),
                               ];
                       },
                       $this->steps
               );
       }

/**
	* Record a prompt sent to the AI service.
	*
	* @param array $prompt Prompt data containing 'instructions' and 'input'.
	* @return void
	*/
public function add_prompt( $prompt ) {
$step_name      = $this->current_step ? $this->current_step['name'] : 'unknown';
$this->prompts[] = [
'step'         => $step_name,
'instructions' => sanitize_textarea_field( $prompt['instructions'] ?? '' ),
'input'        => sanitize_textarea_field( $prompt['input'] ?? '' ),
'timestamp'    => microtime( true ),
];
}

/**
	* Retrieve recorded prompts.
	*
	* @return array
	*/
public function get_prompts() {
return $this->prompts;
}

/**
	* Get AI call count.
	*
	* @return int
	*/
public function get_ai_call_count() {
return $this->ai_calls;
}

/**
	* Get warnings.
	*
	* @return array
	*/
public function get_warnings() {
return $this->warnings;
}

/**
	* Get debug information.
	*
	* @return array
	*/
public function get_debug_info() {
return [
'started_at'    => $this->start_timestamp,
'total_steps'   => count( $this->steps ),
'total_duration'=> microtime( true ) - $this->start_time,
'ai_calls'      => $this->ai_calls,
'warnings_count'=> count( $this->warnings ),
'errors_count'  => count( $this->errors ),
'steps'         => $this->get_completed_steps(),
'prompts'       => $this->prompts,
'warnings'      => $this->warnings,
'errors'        => $this->errors,
];
}

/**
	* Check if step involves AI calls.
	*
	* @param string $step_name Step identifier.
	* @return bool
	*/
private function is_ai_step( $step_name ) {
$ai_steps = [ 'ai_enrichment', 'hybrid_rag_analysis', 'strategic_analysis' ];
return in_array( $step_name, $ai_steps, true );
}
}
