<?php

namespace TMSolution\GeneratorBundle\AbstractFixture;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use TMSolution\GeneratorBundle\Generator\Faker\Populator;
use CCO\CallCenterBundle\Entity\UserSkillGradationDictionary;
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

    protected function createEntities($dataFixture, $model)
    {
        $nr = 0;
        $entities = new \Doctrine\Common\Collections\ArrayCollection;
        foreach ($dataFixture as $array) {
            $entity = $model->getEntity();
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
                $model = $this->container->get('model_factory')->getModel($this->association[$key]);

                try{
                $value = $this->getReference($this->association[$key] . ':' . $value);
                } catch(\Exception $e) {

                    $value = $model->getManager()->getReference($this->association[$key], $value);
                }
            }
            $setter = "set" . $key;
            $entity->$setter($value);
        }
    }

    public function load(ObjectManager $manager)
    {
        $dataFixture = $this->provideData();

        $model = $this
                ->container
                ->get('model_factory')
                ->getModel($this->entityName);
        $model->createEntities($this->createEntities($dataFixture, $model), false);
        $model->flush();
    }

}
