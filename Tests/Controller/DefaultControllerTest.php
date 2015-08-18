<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TMSolution\GeneratorBundle\Tests\Controller;

use TMSolution\TestingBundle\Functional\AppTestCase;
use TMSolution\TestingBundle\Functional\Url;

/**
 * Functional test for TMSolution\GeneratorBundle\Controller\DefaultController 
 */
class DefaultControllerTest extends AppTestCase
{
    /**
     * Function test for TMSolution\GeneratorBundle\Controller\DefaultController::indexAction
     *
     * @Url("")
     */
    public function testIndexAction()
    {
        $this->assertTrue(true);
    }
    
}
