<?php

namespace TMSolution\GeneratorBundle\AbstractFixture;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use TMSolution\GeneratorBundle\Generator\Faker\Populator;
use CCO\CallCenterBundle\Entity\UserSkillGradationDictionary;

abstract class AbstractFixture /* extends AbstractFixtureData */ implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

    /**
     * @var ContainerInterface
     */
    protected $container;
    protected $entityName = null;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }
    
    protected function createEntities($dataFixture, $model) {
        $entities = new \Doctrine\Common\Collections\ArrayCollection;
        foreach ($dataFixture as $array) {
            $entity = $model->getEntity();
            $this->setValues($entity, $array);
            $entities[] = $entity;
        }
        return $entities;
    }

    protected function setValues($entity, $array) {
        foreach ($array as $key => $value) {
            $setter = "set" . $key;
            $entity->$setter($value);
        }
    }


    public function load(ObjectManager $manager) {
        $dataFixture = $this->provideData();

        $model = $this
                ->container
                ->get('model_factory')
                ->getModel($this->entityName);
        $model->createEntities($this->createEntities($dataFixture, $model), true);
    }

    


}
