<?php

namespace TMSolution\GeneratorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TMSolutionGeneratorBundle extends Bundle
{
    
     public function getParent()
    {
        return 'SensioGeneratorBundle';
    }
    
    
    /*
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // register extensions that do not follow the conventions manually
        $container->registerExtension(new UnconventionalExtensionClass());
    }*/
}
