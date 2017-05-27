<?php

namespace TMSolution\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use TMSolution\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Core\BaseBundle\Annotation\FixtureDataType;

/**
 * Generates fixture classes for entities.
 *
 * @author Mariusz Piela <mariusz.piela@tmsolution.pl>
 */
class FixtureGenerator extends Generator
{

    public function prepare($bundles)
    {

        $entitiesMetadata = $this->prioritizationEntities($this->createEntitiesMetadata($bundles));
        return $this->sortEntitiesMetadata($entitiesMetadata);
    }

    protected function setBasePriority($entityMetadata)
    {

        foreach ($entityMetadata->associationMappings as $associationMapping) {

            if ($associationMapping->targetEntity != $entityMetadata->name) {

                $entityMetadata->priority += 1;
            }
        }
    }

    protected function createEntitiesMetadata($bundles)
    {

        $entitiesMetadata = array();
        $bundlesWithEntityDir = array();
        foreach ($bundles as $bundle) {

            $files = $this->readEntityFiles($bundle);
            if (count($files) > 0) {
                $this->createDataFixturesFolder($bundle->getPath());
                foreach ($files as $file) {

                    $entityName = $this->getFullClassName($file);
                    $entitiesMetadata[$entityName] = $this->createEntityMetadata($entityName, $bundle);
                    $this->setBasePriority($entitiesMetadata[$entityName]);
                    $this->createFixtureAnnotation($entityName, $entitiesMetadata[$entityName]->fieldMappings);
                }
            }
        }
        return $entitiesMetadata;
    }

//fixture
    public function generate($bundles, $entityName, $type, $overrideFiles, $entitiesMetadata,$dir)
    {

        if ('bundle' == $type) {

            $this->generateBundleFixture($overrideFiles, $entitiesMetadata,$dir);
        } 
    }

//fixture
    protected function generateBundleFixture($overrideFiles, $entitiesMetadataArr,$dir)
    {

        $order = 0;
        foreach ($entitiesMetadataArr as $entitiesMetadata) {

            $entity = explode('\\', $entitiesMetadata->name);
            $className = end($entity);
            $target = $entitiesMetadata->bundlePath .sprintf('/DataFixtures/%s/',$dir) . $className;
            array_pop($entity);
            $namespace = implode('\\', $entity);
            $this->createFixtureClassFile($target, $className, $namespace, $overrideFiles, $entitiesMetadata, ++$order);
        }
    }

    protected function getEntities($bundles)
    {
        
    }

//fixture
    protected function createFixtureClassFile($target, $className, $nameSpace, $overrideFiles, $entitiesMetadata, $order)
    {
        $parameters = Array(
            'namespace' => str_replace('\Entity', '', $nameSpace),
            'fixture_class' => $className,
            'entity_path' => $nameSpace . '\\' . $className,
            'fields' => Array(),
            'quantity' => $entitiesMetadata->quantity,
            'order' => $order,
            'entitiesMetadata' => serialize($entitiesMetadata)


                /*
                 * 
                 * 'name' => function() use ($generator) { return $generator->firstname()); },
                 * 
                 */
        );


        $target = $target . '.php';

        if ($overrideFiles && file_exists($target)) {

            echo "Save the backup file " . $target . "~\r\n";
            $fs = new Filesystem();
            $fs->copy($target, $target . '~', true);
        }

        echo "Save the file " . $target . "\r\n";
        $this->renderFile('Fixture.php.twig', $target, $parameters);
    }

    /**
     * Create annotation line containing information about...
     * @param type $entity
     * @param type $fieldMappings
     */
    //fixture
    protected function createFixtureAnnotation($entity, &$fieldMappings)
    {

        $anotationReader = new AnnotationReader();
        foreach ($fieldMappings as $fieldName => $fieldValue) {

            $propertyAnnotations = $anotationReader->getPropertyAnnotations(new \ReflectionProperty($entity, $fieldName));
            foreach ($propertyAnnotations as $annotation) {
                if ($annotation instanceof FixtureDataType) {
                    $fieldMappings[$fieldName]["fixtureDataType"] = $annotation->value;
                }
            }
        }
    }

    /**
     * 
     * @param string $entity name of Entity
     * @param Bundle $bundle 
     * @return \stdClass
     */
    protected function createEntityMetadata($entityName, $bundle)
    {

        $em = $this->doctrine->getManager();
        $classMetadata = $em->getClassMetadata($entityName);
        $entityMetadata = new \stdClass();
        $entityMetadata->bundleName = $bundle->getName();
        $entityMetadata->bundlePath = $bundle->getPath();
        $entityMetadata->name = $entityName;
        $entityMetadata->fieldMappings = $this->readFieldMappings($classMetadata);
        $entityMetadata->priority = 0;
        $entityMetadata->associationMappings = $this->createAssociationMappings($classMetadata, $entityName);
        return $entityMetadata;
    }

//dla fixutre
    protected function createDataFixturesFolder($bundlePath)
    {

        $fs = new Filesystem();

        if (!$fs->exists($bundlePath . '/DataFixtures')) {

            try {
                echo "Create folder DataFixtures for " . $bundlePath . "\r\n";
                $fs->mkdir(Array($bundlePath . '/DataFixtures', $bundlePath . '/DataFixtures/ORM'), 0650);
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while creating your directory at " . $e->getPath() . "\r\n";
            }
        }
    }

}
