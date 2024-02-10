<?php

namespace tests;

use Hook\Action;
use PHPUnit\Framework\TestCase;

/**
 * Class HooksTest
 */
class HooksActionTest extends TestCase
{
  /**
   * test action
   */
    public function testAction()
    {
        $done = false;

        Action::addAction(
            'bar',
            function () use (&$done) {
                $done = true;
            }
        );

        Action::doAction('bar');

        self::assertTrue($done);
    }
}
