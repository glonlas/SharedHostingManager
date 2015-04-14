<?php
 
namespace SharedHostingManager\Command;
 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;

use SharedHostingManager\Classes\Website;
use SharedHostingManager\Classes\Server;

class WebsiteAddCommand extends Command
{
    private $input;
    private $output;
    private $helper;
    private $website;

    protected function configure()
    {
        $this
            ->setName('website:add')
            ->setDescription('Add a new website to your server')
        ;
    }
 
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->website = new Website();
        $this->helper = $this->getHelper('question');

        // Style
        $this->output->getFormatter()->setStyle(
            'label',
            new OutputFormatterStyle('white')
        );

        $validated = 0;
        do
        {
            $this->output->writeln("\n".'<question>Website information</question>');
            //$this->email();
            $this->url();
            $this->ftp();
            $this->mysql();
            $this->framework();

            // Confirm is data are correct
            $this->output->writeln("\n".'<question>Summary</question>');
            //$this->output->writeln('<label>Email:</label> '.$this->website->getEmail());
            $this->output->writeln('<label>URL:</label> '.$this->website->getUrl());
            $this->output->writeln('<label>Server preset:</label> '.$this->website->getFramework());

            $confirmation = new ConfirmationQuestion(
                "\n".'<comment>Is it correct? [Y/n]</comment> ', 
                true
            );
            $validated = $this->helper->ask(
                $this->input,
                $this->output,
                $confirmation
            );
        }
        while($validated == 0);

        
        //
        $this->output->writeln("\n<comment>Let's go!</comment>\n");
        $progress = new ProgressBar($this->output, 7);
        $progress->start();

        $server = new Server($this->website);

        $server->addUser();
        $progress->advance();

        $server->addHome();
        $progress->advance();

        $server->addFTP();
        $progress->advance();

        $server->addMySQL();
        $progress->advance();

        $server->addNginx();
        $progress->advance();

        $server->addPHP();
        $progress->advance();

        $server->restartServers();
        $progress->advance();
        
        $progress->finish();

        $this->output->writeln("\n\n".'<question>FTP</question>');
        $this->output->writeln("<label>Host:</label> ftp.".$this->website->getHostname());
        $this->output->writeln("<label>Login:</label> ".$this->website->getUsername());
        $this->output->writeln("<label>Password:</label> ".$this->website->getFTPPassword());

        $this->output->writeln("\n".'<question>MySQL</question>');
        $this->output->writeln("<label>URL PHPMyAdmin:</label> ".$GLOBALS['cfg']->phpmyadmin->url);
        $this->output->writeln("<label>Host:</label> ".$GLOBALS['cfg']->mysql->host);
        $this->output->writeln("<label>Login:</label> ".$this->website->getUsername());
        $this->output->writeln("<label>Password:</label> ".$this->website->getMySQLPassword());

        // Debug
        if(!$GLOBALS['cfg']->prod)
            $this->output->writeln($server->getLog());
    }

    private function email()
    {
        do
        {
            $question = new Question('<label>Customer email:</label> ');
            $isOk = $this->website->setEmail(
                $this->helper->ask(
                    $this->input,
                    $this->output,
                    $question
                )
            );

            if (!$isOk)
                $this->output->writeln('<error>Wrong email format</error>');
        }
        while(!$isOk);
    }

    private function url()
    {
        do
        {
            $question = new Question('<label>Website URL (www.example.com):</label> ');
            $isOk = $this->website->setUrl(
                $this->helper->ask(
                    $this->input,
                    $this->output,
                    $question
                )
            );

            if (!$isOk)
                $this->output->writeln('<error>Wrong URL Format</error>');
        }
        while(!$isOk);
    }

    private function framework()
    {
        $question = new ChoiceQuestion(
            '<label>Does your website is based on a Framework?</label> ',
            ['Wordpress preset', 'Symfony2 preset', 'Standard preset'],
            0
        );
        $this->website->setFramework(
            $this->helper->ask(
                $this->input,
                $this->output,
                $question
            )
        );
    }

    private function ftp()
    {
        $question = new Question('<label>FTP password:</label> ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $manualInput = $this->website->setFTPPassword(
            $this->helper->ask(
                $this->input,
                $this->output,
                $question
            )
        );

        if(!$manualInput)
        {
            $this->output->writeln('<comment>Random password generated</comment>');
        }
    }
    private function mysql()
    {
        $question = new Question('<label>MySQL password:</label> ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $manualInput = $this->website->setMySQLPassword(
            $this->helper->ask(
                $this->input,
                $this->output,
                $question
            )
        );

        if(!$manualInput)
        {
            $this->output->writeln('<comment>Random password generated</comment>');
        }
    }
}