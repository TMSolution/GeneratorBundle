<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TMSolution\GeneratorBundle\Generator;
use Symfony\Component\DependencyInjection\Container;
use TMSolution\GeneratorBundle\Generator\Generator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Finder\Finder;
use TMSolution\GeneratorBundle\PHPAnalyzer;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Core\BaseBundle\Annotation\FixtureDataType;


/**
 * Generator is the base class for all generators.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Generator {

    private $skeletonDirs;
    protected $filesystem = null;
    protected $container = null;
    protected $doctrine = null;
    protected $entities = null;
    protected $entitiesMetadata = array();
    protected $relations = array
        (
        1 => "OneToOne",
        2 => "ManyToOne",
        4 => "OneToMany",
        8 => "ManyToMany",
        3 => "ToOne",
        12 => "ToMany"
    );

    public function __construct(Container $container) {

        $this->container = $container;
        $this->filesystem = $this->container->get('filesystem');
        $this->doctrine = $this->container->get('doctrine');
    }

    protected function getFullClassName($file) {
        $phpAnalyzer = new PHPAnalyzer($file->getPathName());
        $classNames = $phpAnalyzer->getClassName();
        $className = $classNames[0];
        return $phpAnalyzer->getNamespace() . '\\' . $className;
    }

    protected function readEntityFiles($bundle) {
        $entityDir = $bundle->getPath() . '/Entity';
        if (is_dir($entityDir)) {
            $finder = new Finder();
            $files = $finder->files()->in($entityDir)->name('*.php')->notName('*.php~');
        } else {
            $files = array();
        }
        return $files;
    }

    /*
     * priorytetyzacja encji 
     */

    protected function prioritizationEntities($entitiesMetadata) {

        foreach ($entitiesMetadata as $el) {

            foreach ($el->associationMappings as $mapEntity) {
                if($mapEntity->relationName!=="OneToMany" && $mapEntity->relationName!=="ManyToMany"){
                if (isset($entitiesMetadata[$mapEntity->targetEntity]) && $entitiesMetadata[$mapEntity->targetEntity]->priority >= $el->priority) {
                    $el->priority = $entitiesMetadata[$mapEntity->targetEntity]->priority + 1;
                } elseif (!isset($entitiesMetadata[$mapEntity->targetEntity])) {
                    echo "Remember! Generated entity (" . $el->name . ") is related to entity from other bundle (" . $mapEntity->targetEntity . ").\r\nRelated entity fixture won't be generated.\r\n";
                }
            }}
        }

        return $entitiesMetadata;
    }

    /* Sortowanie */

    protected function sortEntitiesMetadata($entitiesMetadata) {

        $sort = [];
        foreach ($entitiesMetadata as $el) {
            $sort[] = $el->priority;
        }
        array_multisort($sort, SORT_ASC, $entitiesMetadata);
        return $entitiesMetadata;
    }

    protected function createAssociationMapping($entity, $el) {

        $associationMapping = new \stdClass();
        $associationMapping->fieldName = $el['fieldName'];
        $associationMapping->targetEntity = $el['targetEntity'];
        $associationMapping->relationName = $this->relations[$el['type']];
        $associationMapping->annotation = $el;
        return $associationMapping;
    }

    protected function createAssociationMappings($classMetadata, $entityName) {
        $associationMappings = array();
        foreach ($classMetadata->getAssociationMappings() as $associationMapping) {
            $associationMappings[] = $this->createAssociationMapping($entityName, $associationMapping);
        }
        return $associationMappings;
    }

    protected function readFieldMappings($entityMetadata) {
        $fieldMappings = array();
        foreach ($entityMetadata->getReflectionProperties() as $reflectionProperty) {

            $fieldname = $reflectionProperty->getName();
            if ($entityMetadata->hasField($fieldname)) {
                $fieldMappings[$reflectionProperty->getName()] = $entityMetadata->getFieldMapping($fieldname);
            }
        }
        return $fieldMappings;
    }

    /**
     * Sets an array of directories to look for templates.
     *
     * The directories must be sorted from the most specific to the most
     * directory.
     *
     * @param array $skeletonDirs An array of skeleton dirs
     */
    public function setSkeletonDirs($skeletonDirs) {
        $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
        //throw new \Exception(json_encode($this->skeletonDirs));
    }

    protected function render($template, $parameters) {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ));
        return $twig->render($template, $parameters);
    }

    protected function renderFile($template, $target, $parameters) {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        return file_put_contents($target, $this->render($template, $parameters));
    }

}
