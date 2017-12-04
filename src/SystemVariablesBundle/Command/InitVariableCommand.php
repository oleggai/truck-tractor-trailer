<?php

namespace SystemVariablesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitVariableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('ttt:variable:init')
            ->setDescription('This command initialize variable')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->addArgument('unit', InputArgument::REQUIRED);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $variableService = $this->getContainer()->get('ttt.variable');
        $variable = $variableService->init(
            $input->getArgument('name'),
            $input->getArgument('value'),
            $input->getArgument('unit'));

        if ($variable == false) {
            $output->writeln('This variable is exist!');
        }
    }
}