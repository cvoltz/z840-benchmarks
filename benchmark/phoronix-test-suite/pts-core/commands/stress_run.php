<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2016, Phoronix Media
	Copyright (C) 2015 - 2016, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class stress_run implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will run the passed tests/suites in the multi-process stress-testing mode. The stress-run mode will not produce a result file but is rather intended for running multiple test profiles concurrently to stress / burn-in the system. The number of tests to run concurrently can be toggled via the PTS_CONCURRENT_TEST_RUNS environment variable and by default is set to a value of 2.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($to_run)
	{
		$test_run_manager = new pts_test_run_manager(array(
			'UploadResults' => false,
			'SaveResults' => false,
			'PromptForTestDescription' => false,
			'RunAllTestCombinations' => false,
			'PromptSaveName' => false,
			'PromptForTestIdentifier' => false,
			'OpenBrowser' => false
			));

		$tests_to_run_concurrently = 2;

		if(($j = getenv('PTS_CONCURRENT_TEST_RUNS')) && is_numeric($j) && $j > 1)
		{
			$tests_to_run_concurrently = $j;
			echo 'PTS_CONCURRENT_TEST_RUNS set; running ' . $tests_to_run_concurrently . ' tests concurrently.' . PHP_EOL;
		}

		/*
		if(count($to_run) < $tests_to_run_concurrently)
		{
			echo PHP_EOL . 'More tests must be specified in order to run ' . $tests_to_run_concurrently . ' tests concurrently.';
			return false;
		}
		*/

		if($test_run_manager->initial_checks($to_run, 'SHORT') == false)
		{
			return false;
		}

		// Load the tests to run
		if($test_run_manager->load_tests_to_run($to_run) == false)
		{
			return false;
		}

		// Run the actual tests
		$total_loop_time = pts_client::read_env('TOTAL_LOOP_TIME');
		if($total_loop_time == 'infinite')
		{
			$total_loop_time = 'infinite';
			echo 'TOTAL_LOOP_TIME set; running tests in an infinite loop until otherwise triggered' . PHP_EOL;
		}
		else if($total_loop_time && is_numeric($total_loop_time) && $total_loop_time > 9)
		{
			$total_loop_time = $total_loop_time * 60;
			echo 'TOTAL_LOOP_TIME set; running tests for ' . ($total_loop_time / 60) . ' minutes' . PHP_EOL;
		}
		else
		{
			$total_loop_time = false;
		}
		//$test_run_manager->pre_execution_process();
		$test_run_manager->multi_test_stress_run_execute($tests_to_run_concurrently, $total_loop_time);
	}
	public static function invalid_command($passed_args = null)
	{
		pts_tests::invalid_command_helper($passed_args);
	}
}

?>
