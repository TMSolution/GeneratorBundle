<?php

namespace TMSolution\GeneratorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Core\SecurityBundle\Annotations\Permissions;

class MemorytestController extends Controller {

    /**
     * @Permissions(rights={MaskBuilder::MASK_MASTER})
     */
    public function indexAction() {

        echo "START: " . $this->convert(memory_get_usage()) . "\n";

        
        $testArr = Array();
        for ($i = 0; $i < 100000; $i++) {
            $testArr[] = $i;
        }

        echo "\n STOP: " . $this->convert(memory_get_usage()) . "\n";
        
       
    }

    protected function convert($size) {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

}
