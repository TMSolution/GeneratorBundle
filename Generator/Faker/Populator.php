<?php

namespace TMSolution\GeneratorBundle\Generator\Faker;

use Doctrine\Common\Persistence\ObjectManager;
use TMSolution\GeneratorBundle\Generator\Faker\EntityPopulator;
use TMSolution\GeneratorBundle\Generator\Faker\Generator;

/**
 * Service class for populating a database using the Doctrine ORM or ODM.
 * A Populator can populate several tables using ActiveRecord classes.
 */
class Populator extends \Faker\ORM\Doctrine\Populator {

    protected $generator;
    protected $manager;
    protected $entities = array();
    protected $quantities = array();
    protected $generateId = array();

    public function __construct(Generator $generator, ObjectManager $manager = null) {
        $this->generator = $generator;
        $this->manager = $manager;
    }

    /**
     * Populate the database using all the Entity classes previously added.
     *
     * @param EntityManager $entityManager A Propel connection object
     *
     * @return array A list of the inserted PKs
     */
    public function execute($entityManager = null) {

        if (null === $entityManager) {
            $entityManager = $this->manager;
        }

        if (null === $entityManager) {
            throw new \InvalidArgumentException("No entity manager passed to Doctrine Populator.");
        }


        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        ini_set("memory_limit", "-1");


        $insertedEntities = array();
        foreach ($this->quantities as $class => $number) {

            $generateId = $this->generateId[$class];

            $counter = 0;
            $packageSize = 10000;
            $packageQuantity = ceil((float) $number / $packageSize);

            for ($j = 0; $j < $packageQuantity; $j++) {

                for ($i = $j * $packageSize; $i < ($j + 1) * $packageSize; $i++) {

                    if ($counter++ == $number) {

                        break;
                    }


                    $insertedEntities[] = $this->getEntity($class,$entityManager, $insertedEntities, $generateId);
                }
            }


            try {
                $entityManager->flush();
                $entityManager->clear();
            } catch (Exception $e) {
                echo "BŁĄD: " . $e->getMessage();
            }

            echo "Wykorzystanie pamięci " . $class . ": " . $this->convert(memory_get_usage()) . "\n";
        }

        return $insertedEntities;
    }
    
    
    protected function getEntity($class,$entityManager, $insertedEntities, $generateId){
        
      return   $this->entities[$class]->execute($entityManager, $insertedEntities, $generateId);
        
    }

    /**
     * Add an order for the generation of $number records for $entity.
     *
     * @param mixed $entity A Doctrine classname, or a \Faker\ORM\Doctrine\EntityPopulator instance
     * @param int   $number The number of entities to populate
     */
    public function addEntity($entity, $number, $entityManager = array(), $customColumnFormatters = array(), $customModifiers = array(), $generateId = false) {
        if (!$entity instanceof EntityPopulator) {
            if (null === $this->manager) {
                throw new \InvalidArgumentException("No entity manager passed to Doctrine Populator.");
            }
            //echo   "Klasa:".$entity."\n";
            $entityPopulator = new EntityPopulator($this->manager->getClassMetadata($entity), $this->manager);


            $associationEntities = $entityPopulator->getAssociationEntities();
            $this->generator->findAssociatedIdentifiers($associationEntities, $this->manager);
        }

        $entityPopulator->setColumnFormatters($entityPopulator->guessColumnFormatters($this->generator));

        /* if (!empty($customColumnFormatters)) {
          $entityPopulator->mergeColumnFormattersWith($fieldMappings);
          } */
        $entityPopulator->mergeModifiersWith($customModifiers);
        $this->generateId[$entityPopulator->getClass()] = $generateId;

        $class = $entityPopulator->getClass();
        $this->entities[$class] = $entityPopulator;
        $this->quantities[$class] = $number;
    }

    protected function convert($size) {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

}
