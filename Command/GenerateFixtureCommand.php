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
use Symfony\Component\Console\Output\NullOutput;

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

    const DEFAULT_QUANTITY = 100;

    /**
     * @see Command
     */
    protected function configure() {
        $this
                ->setDefinition(array(
                    new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The name of entity'),
                    new InputOption('bundle', '', InputOption::VALUE_REQUIRED, 'The name of bundle'),
                    new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The name of bundle', 'ORM'),
                    new InputOption('quantity', '', InputOption::VALUE_REQUIRED, 'quantity', 100),
                    new InputOption('silent', null, InputOption::VALUE_NONE, 'silent')
                ))
                ->setDescription('Generates a Fixture Classes')
                ->setHelp(<<<EOT
The <info>generate:fixture</info> command helps you generates new fixtures classes for test data loading.
EOT
                )->setName('tmsolution:generate:fixture')->setAliases(array('generate:fixture'));
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

         
        if (true === $input->getOption('silent')) {
            $output = new NullOutput();
        }
        
        $generationParams = $this->getGenerationParams();
        //dump($generationParams);exit;
        if (!$this->input->getOption('silent') && $this->input->isInteractive()) {
            $questionHelper = $this->getQuestionHelper();
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);





            if (!$this->questionHelper->ask($this->input, $this->output, $question)) {
                $this->output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        $generator = $this->getGenerator();

        $bundleName=$generationParams['bundleName'];
        $bundles=$this->getBundles($bundleName);
        $entitiesMetadata = $generator->prepare($bundles);
     

        $entitiesMetadata = $this->showGenerationWizard($entitiesMetadata);
        $generator->generate($bundles, $generationParams['entityName'], $this->type, $generationParams['overrideFiles'], $entitiesMetadata, $this->input->getOption('dir'));
    }

    protected function showGenerationWizard($entitiesMetadata) {

        $quantity = $this->input->getOption('quantity');
        if (!$this->input->getOption('silent') && $this->input->isInteractive()) {

            $questionHelper = $this->getQuestionHelper();
            $questionConf = new ConfirmationQuestion($questionHelper->getQuestion('Are you sure to create files?', 'yes', '?'), true);

            if (!$this->questionHelper->ask($this->input, $this->output, $questionConf)) {
                $this->output->writeln('<error>Command aborted</error>');

                return 1;
            } else {

                foreach ($entitiesMetadata as $entityMetadata) {

                    
                    if (!$this->input->getOption('silent') && $this->input->isInteractive() && !$quantity) {
                        $question2 = new Question($questionHelper->getQuestion('How many object of type ' . $entityMetadata->name . ' you want to create <comment>(default ' . $defaultQuantity . ')</comment>?', $defaultQuantity), true);
                        $quantity = $this->questionHelper->ask($this->input, $this->output, $question2);
                        if ($quantity != null) {
                            $quantity = $this->input->getOption('quantity');
                        }
                    }
                    $entityMetadata->quantity = $quantity;
                }
            
            }
        }
        else
        {
            foreach ($entitiesMetadata as $entityMetadata) {
                 $entityMetadata->quantity = $quantity;
            }
        }
        
            return $entitiesMetadata;
    }

    /**
     * Gets array of Bundle objects
     *
     * @return array Bundle Interface $bundle
     */
    protected function getBundles($bundleName) {


        $name = ltrim($bundleName, "Bundle");
        if ('bundle' == $this->type) {

            try {
                $bundle = $this->kernel->getBundle($name);
            } catch (\Exception $e) {

                $this->output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $name));
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
        } else {
            $this->type = "bundle";
            $bundle = null;
            if ($this->input->getOption('bundle')) {
                $bundle = $this->input->getOption('bundle');
            } else {

                if (!$this->input->getOption('silent') && $this->input->isInteractive()) {
                    $this->output->writeln(array(
                        'You choose <info>bundle</info> option for generation.',
                        'Each bundle is hosted under a namespace (like <comment>Acme/Bundle/BlogBundle</comment>).',
                        'Please write namespace of your bundle to generate fixture data.'
                    ));

                    
                    $question = new Question($this->questionHelper->getQuestion('Please, set  <comment>bundle name</comment>', $bundle), $bundle);
                    $bundle = $this->questionHelper->ask($this->input, $this->output, $question);
                }
                
            }
          
            $bundleName = strtr($bundle, array('\\' => ''));
        }

        $overrideFiles = null;
        //changed askconfirmation()  into doAsk()
        $questionFiles = new Question($this->questionHelper->getQuestion('Override existing backup files', $bundle), $bundle);
        $overrideFiles = true; //$this->questionHelper->doAsk($this->output, $questionFiles, true);
        return array('bundleName' =>$bundleName , 'entityName' => !empty($entityName) ? $entityName : null, 'overrideFiles' => !empty($overrideFiles) ? $overrideFiles : null);
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
