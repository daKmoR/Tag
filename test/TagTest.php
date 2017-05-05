<?php
	define('BOWER_PATH', __DIR__ . '/../bower_components');
	require_once(__DIR__ . '/../Tag.php');

	class TagTest extends PHPUnit_Framework_TestCase {

		/**
		 * @param $input0
		 * @param $input1
		 * @param $input2
		 * @param $expectedResult
		 *
		 * @internal     param $input
		 * @dataProvider providerTestArrayToDotString
		 */
		public function testGenericTag($input0, $input1, $input2, $expectedResult) {
			$result = Tag::genericTag($input0, $input1, $input2);

			$this->assertEquals($expectedResult, $result);
		}

		public function providerTestArrayToDotString() {
			return array(
				array(
					'p',
					'Something',
					array(
						'class' => 'power',
						'_echo' => false
					),
					'<p class="power">Something</p>
'
				)

			);
		}

	}