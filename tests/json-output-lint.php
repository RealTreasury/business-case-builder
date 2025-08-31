<?php
/**
	* Lints PHP files that output JSON to prevent stray output.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

function rtbcb_lint_json_output() {
	$directory = dirname(__DIR__);
	$iterator  = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory)
	);
	$errors = [];

	foreach ($iterator as $file) {
		if ($file->isDir()) {
			continue;
		}

		if ('.php' !== substr($file->getFilename(), -4)) {
			continue;
		}

		if (false !== strpos($file->getPathname(), '/vendor/')) {
			continue;
		}

		$contents = file_get_contents($file->getPathname());

		if (preg_match('/wp_send_json|json_encode/', $contents)) {
			if (0 !== strpos($contents, '<?php')) {
				$errors[] = $file->getPathname() . ' does not start with <?php';
			}

			if (preg_match('/\?>\s*$/', $contents)) {
				$errors[] = $file->getPathname() . ' ends with a closing ?> tag';
			}
		}
	}

	if (!empty($errors)) {
		echo implode(PHP_EOL, $errors) . PHP_EOL;
		exit(1);
	}

	echo "JSON output lint passed." . PHP_EOL;
}

rtbcb_lint_json_output();
