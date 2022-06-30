<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'vinatis:security:ldap:test';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('<info>Vinatis Security LDAP loaded !</info>');

        return Command::SUCCESS;
    }
}
