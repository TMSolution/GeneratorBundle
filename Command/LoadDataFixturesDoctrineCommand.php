<?php

/*
 * This file is part of the Doctrine Fixtures Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TMSolution\GeneratorBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DataFixtures;
use InvalidArgumentException;
use TMSolution\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Load data fixtures from bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
ini_set('max_execution_time', 5000);
class LoadDataFixturesDoctrineCommand extends DoctrineCommand {

    
    
    protected function configure() {
        $this
                ->setName('tmsolution:fixtures:load')
                ->setDescription('Load data fixtures to your database.')
                ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory or file to load data fixtures from.')
                ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
                ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
                ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Fixtures dir','ORM')
                ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
                ->addOption('silent', null, InputOption::VALUE_NONE, 'silent')
                ->setHelp(<<<EOT
The <info>doctrine:fixtures:load</info> command loads data fixtures from your bundles:

  <info>./app/console doctrine:fixtures:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console doctrine:fixtures:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console doctrine:fixtures:load --append</info>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console doctrine:fixtures:load --purge-with-truncate</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        
        if (true === $input->getOption('silent')) {
            $output = new NullOutput();
        }
        
        
        /** @var $doctrine \Doctrine\Common\Persistence\ManagerRegistry */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));


        $dirOrFile = $input->getOption('fixtures');

        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            $paths = array();
            foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
                $paths[] = $bundle->getPath() . sprintf('/DataFixtures/%s',$input->getOption('dir'));
            }
        }

        $loader = new DataFixturesLoader($this->getContainer());
     
        foreach ($paths as $path) {
            if (is_dir($path)) {
                
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();
        //dump($fixtures);
        
        if (!$fixtures) {

            throw new InvalidArgumentException(
            sprintf('Could not find any fixtures to load in: %s', "\n\n- " . implode("\n- ", $paths))
            );
        }


        $purger = new ORMPurger($em);
        $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(function($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, true /* $input->getOption('append') */);
    }

}
