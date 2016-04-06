<?php

namespace TMSolution\GeneratorBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use TMSolution\GeneratorBundle\Command\Helper\QuestionHelper;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use TMSolution\GeneratorBundle\Generator\FixtureGenerator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Command for Generates fixture.
 *
 * @author Mariusz Piela <mariusz.piela@tmsolution.pl>
 */

class GenerateFixtureCommand extends GeneratorCommand {

    protected $questionHelper = null;
    protected $kernel = null;
    protected $type = null;
    protected $input = null;
    protected $output = null;

    /**
     * @see Command
     */
    protected function configure() {
        $this
                ->setDefinition(array(
                    new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The name of entity'),
                    new InputOption('project', '', InputOption::VALUE_NONE, 'The name of project'),
                    new InputOption('bundle', '', InputOption::VALUE_REQUIRED, 'The name of bundle')
                ))
                ->setDescription('Generates a Fixture Classes')
                ->setHelp(<<<EOT
The <info>generate:fixture</info> command helps you generates new fixtures classes for test data loading.
EOT
                )->setName('generate:tmsolution:fixture')->setAliases(array('generate:fixture'));
    }

    protected function interact(InputInterface $input, OutputInterface $output) {

        $this->questionHelper = $this->getQuestionHelper();
        $this->kernel = $this->getContainer()->get('kernel');
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper->writeSection($this->output, 'Welcome to the TMSolution fixture generator');
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $generationParams = $this->getGenerationParams();
        //dump($generationParams);exit;
        if ($this->input->isInteractive()) {
            $questionHelper = $this->getQuestionHelper();
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);


//            $bundle  = $input->getOption('bundle');
//            
//            $questionHelper = $this->getQuestionHelper();
//            $questionConfirm = new ConfirmationQuestion($questionHelper->getQuestion(
//                            'Do you confirm generation', $bundle ? 'yes' : 'no'
//                    ), $bundle);
//            $bundle = $this->questionHelper->ask($this->input, $this->output, $questionConfirm);
            //$input->setOption('bundle', $bundle);


            if (!$this->questionHelper->ask($this->input, $this->output, $question)) {
                $this->output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        $generator = $this->getGenerator();
//        dump($generator);exit;
        //dump($this->getBundles($generationParams['bundleName']));exit;
        //die('test dobry');
        //step 1
        $entitiesMetadata = $generator->prepare($this->getBundles($generationParams['bundleName']));
        //dump($entitiesMetadata);exit;

        $entitiesMetadata = $this->showGenerationWizard($entitiesMetadata);
        //die('ok');
        //step 2
        //@todo -> shoud look this way  $generator->generate($entitiesMetadata, $generationParams['overrideFiles']);
        $generator->generate($this->getBundles($generationParams['bundleName']), $generationParams['entityName'], $this->type, $generationParams['overrideFiles'], $entitiesMetadata);
    }

    protected function showGenerationWizard($entitiesMetadata) {
        //1. generate file yes/no
        //foreach enity ask: how many elements generate
        //write result into quantity parameter;
        //or save file with default values and end program
        //developer should start this command one more time, with parameter --entities-metdata-file=path-to-file
        //if this parameter will be set, program should do procedure startnig from step 2

        

//        $yes = null;
//        $questionConfirm = new Question($this->questionHelper->getQuestion('Do you confirm generation', 'yes', '?', $yes), $yes);
//        $yes = $this->questionHelper->ask($this->input, $this->output, $questionConfirm);

        if ($this->input->isInteractive()) {

            
            $questionHelper = $this->getQuestionHelper();
            $questionConf = new ConfirmationQuestion($questionHelper->getQuestion('Are you sure to create files?', 'yes', '?'), true);

            if (!$this->questionHelper->ask($this->input, $this->output, $questionConf)) {
                $this->output->writeln('<error>Command aborted</error>');

                return 1;
            }

//            $createFiles = null;
//            $questionWizard = new Question($this->questionHelper->getQuestion('Are you sure to create files?', 'yes', '?', $createFiles), $createFiles);
//            $createFiles = $this->questionHelper->ask($this->input, $this->output, $questionWizard);
//            if (!$this->questionHelper->doAsk($this->output, $questionWizard)) {
//                $this->output->writeln('<error>Command aborted</error>');
//                return 1;
//            } 
            else {
                
                foreach ($entitiesMetadata as $entityMetadata) {
                    
                    if ($this->input->isInteractive()) {
                        
                        $defaultQuantity = 100;
                        $question2 = new Question($questionHelper->getQuestion('How many object of type ' . $entityMetadata->name . ' you want to create <comment>(default ' . $defaultQuantity . ')</comment>?', $defaultQuantity), true);
                        $quantity = $this->questionHelper->ask($this->input, $this->output, $question2);
                        //die('ok');
                        if ($quantity!=null) {
                            $quantity = $defaultQuantity;
                        }
                        $entityMetadata->quantity = $quantity;
                    }
                }
                return $entitiesMetadata;
            }
        }
    }

    /**
     * Gets array of Bundle objects
     *
     * @return array Bundle Interface $bundle
     */
    protected function getBundles($bundleName) {
        //dump($bundleName);exit;
        //for delete 'Bundle' form bundleName
        $name = ltrim($bundleName,"Bundle");
        //dump($name);exit;
        //$bundleName = 'PhantomBundle';
        //wyzej zmieniono, gdyz przechodzi niewalsciwa sciezka
        //die($bundleName); - to daje  BundlePhantomBundle
        if ('project' == $this->type) {
            return $this->getApplicationBundles();
        } else if ('bundle' == $this->type) {

            try {
                $bundle = $this->kernel->getBundle($name);
            } catch (\Exception $e) {

                $this->output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }

            return array($bundle);
        }
    }

    protected function createGenerator() {


        return new FixtureGenerator($this->getContainer());
    }

    /**
     * Gets type based on input params.
     *
     * @return array('bundleName','entityName')
     */
    protected function getGenerationParams() {

        if ($this->input->getOption('entity')) {
            $this->type = 'entity';

            $this->output->writeln(array(
                'You choose <info>entity</info> option for generation.',
                'Each <info>entity</info> is hosted under a namespace (like <comment>AcmeBlogBundle:Article</comment>).',
            ));
            $entityName = null;
            $entityName = $this->questionHelper->ask($this->input, $this->output, $this->questionHelper->getQuestion('Please, set  <comment>entity namespace</comment>', $entityName));
            $entityName = Validators::validateEntityName($entityName);
            $entityNameArray = explode(':', $entityName);
            $bundleName = $entityNameArray[0];
        } else if ($this->input->getOption('project')) {
            $this->type = 'project';
            $this->output->writeln('You choose <info>project</info> option for generation.');
        } else {
            $this->type = "bundle";

            if ($this->input->isInteractive()) {
                $this->output->writeln(array(
                    'You choose <info>bundle</info> option for generation.',
                    'Each bundle is hosted under a namespace (like <comment>Acme/Bundle/BlogBundle</comment>).',
                    'Please write namespace of your bundle to generate fixture data.'
                ));

                $namespace = null;
                //DialogHelper has not support anymore
                $question = new Question($this->questionHelper->getQuestion('Please, set  <comment>bundle namespace</comment>', $namespace), $namespace);
                $namespace = $this->questionHelper->ask($this->input, $this->output, $question);
                $namespace = Validators::validateBundleNamespace($namespace);
                $bundleName = strtr($namespace, array('\\' => ''));
            }
        }

        $overrideFiles = null;
        //changed askconfirmation()  into doAsk()
        $questionFiles = new Question($this->questionHelper->getQuestion('Override existing backup files', $namespace), $namespace);
        $overrideFiles = $this->questionHelper->doAsk($this->output, $questionFiles, true);
        return array('bundleName' => !empty($bundleName) ? Validators::validateBundleName($bundleName) : null, 'entityName' => !empty($entityName) ? $entityName : null, 'overrideFiles' => !empty($overrideFiles) ? $overrideFiles : null);
    }

    /**
     * Returns Base dir.
     *
     * @return path to Base dir
     */
    protected function getBaseDir() {
        
        $dirParts = explode('/', $this->kernel->getRootDir());
        array_pop($dirParts);
        $baseDir = implode('/', $dirParts);
        return $baseDir;
    }

    /**
     * Returns application bundles.
     *
     * @return BundleInterface object;
     */
    protected function getApplicationBundles() {

        $projectDir = $this->getBaseDir();
        $projectBundles = array();
        //die('ok');
        foreach ($this->kernel->getBundles() as $bundle) {
            if (strstr($bundle->getPath(), $projectDir . '/src/')) {
                $projectBundles[] = $bundle;
            }
        }
        return $projectBundles;
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null) {
        $skeletonDirs = array();

        if (isset($bundle) && is_dir($dir = $bundle->getPath() . '/Resources/SensioGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->kernel->getRootdir() . '/Resources/SensioGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $skeletonDirs[] = __DIR__ . '/../Resources/skeleton';
        $skeletonDirs[] = __DIR__ . '/../Resources';

        return $skeletonDirs;
    }

}
