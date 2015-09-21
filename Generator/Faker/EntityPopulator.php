<?php

namespace TMSolution\GeneratorBundle\Generator\Faker;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use TMSolution\GeneratorBundle\Generator\Faker\ColumnTypeGuesser;
use \Faker\Guesser\Name;
use TMSolution\GeneratorBundle\Generator\Faker\Generator;

/**
 * Service class for populating a table through a Doctrine Entity class.
 */
class EntityPopulator extends \Faker\ORM\Doctrine\EntityPopulator {

    /**
     * @var ClassMetadata
     */
    protected $class;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $columnFormatters = array();

    /**
     * @var array
     */
    protected $modifiers = array();
    protected $formatters = array();

    /**
     * Class constructor.
     *
     * @param ClassMetadata $class
     */
    public function __construct(ClassMetadata $class, $manager) {
        $this->class = $class;
        $this->manager = $manager;
    }

    /**
     * @return string
     */
    public function getClass() {
        return $this->class->getName();
    }

    public function setColumnFormatters($columnFormatters) {
        $this->columnFormatters = $columnFormatters;
    }

    public function getColumnFormatters() {
        return $this->columnFormatters;
    }

    public function mergeColumnFormattersWith($columnFormatters) {
        $this->columnFormatters = array_merge($this->columnFormatters, $columnFormatters);
    }

    public function setModifiers(array $modifiers) {
        $this->modifiers = $modifiers;
    }

    public function getModifiers() {
        return $this->modifiers;
    }

    public function mergeModifiersWith(array $modifiers) {
        $this->modifiers = array_merge($this->modifiers, $modifiers);
    }

    public function createFieldsFormatters($columnTypeGuesser, $generator) {
        $formatters = Array();
        $nameGuesser = new Name($generator);

        foreach ($this->class->getFieldNames() AS $fieldName) {

            //echo "pola standardowe: " . $fieldName . "\n";
            if ($this->class->isIdentifier($fieldName) || !$this->class->hasField($fieldName)) {
                 //echo "pola standardowe kończę: " . $fieldName . "\n";
                  
                continue;
            }

            //echo "pola standardowe dalej: " . $fieldName . "\n";
            
            if ($formatter = $nameGuesser->guessFormat($fieldName)) {
                $formatters[$fieldName] = $formatter;
                continue;
            }
            if ($formatter = $columnTypeGuesser->guessFormat($fieldName, $this->class)) {
                $formatters[$fieldName] = $formatter;
                continue;
            }
        }

        return $formatters;
    }

    /**
     * Wyszukuje asocjacje dla encji
     * 
     * @return type
     */
    public function getAssociationEntities() {
        $associationEntities = Array();
        $associationMappings = $this->class->getAssociationMappings();

        foreach ($associationMappings as $associationMapping) {

            $associationType = $this->getAssociationType($associationMapping);

            if ($associationType != ClassMetadata::ONE_TO_MANY) {
                //echo "Wyszukana asocjacja dla encji: " . $associationMapping['fieldName'] . " associationType:" . $associationType . "\n";
                $associationEntities[] = Array($associationMapping['targetEntity'],$associationType);
            }
        }

        return $associationEntities;
    }

    public function createAssociationFormatters($columnTypeGuesser) {
        $formatters = Array();
        // $getAssociationNames = $this->class->getAssociationNames();
    
        // foreach ($getAssociationNames AS $assocName) {
        // $relatedClass = $this->class->getAssociationTargetClass($assocName);
        $associationMappings = $this->class->getAssociationMappings();
        foreach ($associationMappings as $associationMapping) {
            
                if ($associationType = $this->getAssociationType($associationMapping)) {

                    //echo "pola asocjacyjne: " . $associationMapping['fieldName'] . "\n";
                    $formatter = $columnTypeGuesser->getAssociation($associationMapping['fieldName'], $associationMapping['targetEntity'], $associationType, $this->manager);
                  
                    if($formatter){
                    $formatters[$associationMapping['fieldName']] = $formatter;}
                }
        }
        $index = 0;
        /* $formatters[$assocName] = function($inserted) use ($relatedClass, &$index, $relation) {
          if ($unique && isset($inserted[$relatedClass])) {
          return $inserted[$relatedClass][$index++];
          } elseif (isset($inserted[$relatedClass])) {
          return $inserted[$relatedClass][mt_rand(0, count($inserted[$relatedClass]) - 1)];
          }

          return null;

          }; */
        //}

        return $formatters;
    }

    public function guessColumnFormatters(\Faker\Generator $generator) {


        $columnTypeGuesser = new ColumnTypeGuesser($generator);

        $this->formatters = array_merge($this->createFieldsFormatters($columnTypeGuesser, $generator), $this->createAssociationFormatters($columnTypeGuesser));


        return $this->formatters;
    }

    protected function getAssociationType($associationMapping) {
        if ($associationMapping['type'] == ClassMetadata::ONE_TO_ONE) {
            $associationType = ClassMetadata::ONE_TO_ONE;
        } elseif ($associationMapping['type'] == ClassMetadata::ONE_TO_MANY) {
            //    $associationType = ClassMetadata::ONE_TO_MANY;
            /**
             * Asocjacje one to many wyklucza się, bo nie losujemy wartości dla nich
             * 
             */
            return null;
        } elseif ($associationMapping['type'] == ClassMetadata::MANY_TO_MANY) {
            $associationType = ClassMetadata::MANY_TO_MANY;
        } elseif ($associationMapping['type'] == ClassMetadata::MANY_TO_ONE) {
            $associationType = ClassMetadata::MANY_TO_ONE;
        }

        return $associationType;
    }

    /**
     * Insert one new record using the Entity class.
     */
    public function execute(ObjectManager $manager, $insertedEntities, $generateId = false) {
        $obj = $this->class->newInstance();




        $this->fillColumns($obj, $insertedEntities);
        $this->callMethods($obj, $insertedEntities);

        if ($generateId) {
            $ids = $this->class->getIdentifier();
            
            for($i=0;$i<count($ids);$i++){
                
           // foreach ($idsName as $idName) {
                
             

                $generateId = $this->generateId($obj, $ids[$i], $manager);
                $this->class->reflFields[ $ids[$i]]->setValue($obj, $generateId);
            }
        }
        
        //echo "Wykonuję persist";
        
        $manager->persist($obj);
        $manager->flush($obj);

        return $obj;
    }

    private function fillColumns($obj, $insertedEntities) {
        foreach ($this->columnFormatters as $field => $format) {

            if (null !== $format) {

                $value = is_callable($format) ? $format($insertedEntities, $obj) : $format;

       
               
                $this->class->reflFields[$field]->setValue($obj, $value);
                
               
               
            }
        }
    }

    private function callMethods($obj, $insertedEntities) {
        
         
        foreach ($this->getModifiers() as $modifier) {
            $modifier($obj, $insertedEntities);
        }
    }

    private function generateId($obj, $column, EntityManagerInterface $manager) {
        /* @var $repository \Doctrine\ORM\EntityRepository */
        $repository = $manager->getRepository(get_class($obj));
        $result = $repository->createQueryBuilder('e')
                ->select(sprintf('e.%s', $column))
                ->getQuery()
                ->getResult();
        $ids = array_map('current', $result);

        $id = null;
        do {
            $id = rand();
        } while (in_array($id, $ids));

        return $id;
    }

}


