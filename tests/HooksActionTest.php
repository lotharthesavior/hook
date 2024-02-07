<?php

namespace tests;

use Hook\Hooks;

/**
 * Class HooksTest
 */
class HooksActionTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @var Hooks
   */
    protected $hooks;

  /**
   * test action
   */
    public function testAction()
    {
        $done = false;

        $this->hooks->addAction(
            'bar',
            function () use (&$done) {
                $done = true;
            }
        );

        $this->hooks->doAction('bar');

        self::assertTrue($done);
    }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
    protected function setUp(): void
    {
        $this->hooks = Hooks::getInstance();
    }
}
