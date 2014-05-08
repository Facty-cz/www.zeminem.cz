<?php

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class SignPresenterTest extends Tester\TestCase {

	public function __construct(Nette\DI\Container $container) {
		$this->tester = new Presenter($container);
	}

	public function setUp() {
		$this->tester->init('Sign');
	}

	public function testRenderIn() {
		$this->tester->testAction('in');
	}

}

$test = new SignPresenterTest($container);
$test->run();