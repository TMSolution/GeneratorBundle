<?php

namespace TMSolution\GeneratorBundle\AbstractFixture;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture as AbstractFixtureData;

abstract class AbstractFixture extends AbstractFixtureData implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;
    protected $entityName = null;
    protected $association = [
    ];

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    protected function createEntities($dataFixture)
    {
        $nr = 0;
        $entities = new \Doctrine\Common\Collections\ArrayCollection;
        foreach ($dataFixture as $array) {
            $entityClass=$this->entityName;
            $entity = new $entityClass;
            ++$nr;
            $this->addReference("{$this->entityName}:{$nr}", $entity);
            $this->setValues($entity, $array);
            $entities[] = $entity;
        }
        return $entities;
    }

    protected function setValues($entity, $array)
    {

        foreach ($array as $key => $value) {


            if (isset($this->association[$key]) && !empty($value)) {
                
                // An association
                
                $entityManager = $this->container
                ->get('doctrine')
                ->getManager(); 
                  
               
                $references = [];
                if (is_array($value)) {
                    
                    // ::Many
                    foreach($value as $ref) {
                        $references[] = $this->getReference(
                            $this->association[$key] . ':' . $ref
                        );
                    }
                    
                    $debug = true;
                    
                } else {
                    
                    // ::One
                    
                    try{
                        $references[] = $this->getReference(
                            $this->association[$key] . ':' . $value
                        );
                    } catch(\Exception $e) {
                        $references[] = $entityManager->getReference(
                            $this->association[$key], $value
                        );
                    }
                }
                
                $setter = "set" . $key;
                foreach($references as $ref) {
                    $entity->$setter($ref);
                }
                
 
            } else {
            
                // Not an association                
                $setter = "set" . $key;
                $entity->$setter($value);
            }
        }
    }

    public function load(ObjectManager $manager)
    {
        $dataFixture = $this->provideData();
        $model = new Flexix\ModelBundle\Util\Model( $this->container->get('doctrine')->getManager());
        $this->saveEntitites($this->createEntities($dataFixture));
    }

}
