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
 
class WebsitePasswordCommand extends Command
{
    private $input;
    private $output;
    private $helper;
    private $website;

    protected function configure()
    {
        $this
            ->setName('website:password')
            ->setDescription('Change a password for a website: FTP, MySQL')
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

        $this->output->writeln("\n".'<question>Want to change a password?</question>');
        $this->url();

        $question = new ChoiceQuestion(
            '<label>Which password you want to update?</label> ',
            ['FTP', 'MySQL', 'both'],
            0
        );
        $choice = $this->helper->ask(
            $this->input,
            $this->output,
            $question
        );

        // FTP
        if($choice == 'FTP' || $choice == 'both')
        {
            $this->ftp();
        }

        // MySQL
        if($choice == 'MySQL' || $choice == 'both')
        {
            $this->mysql();
        }

        $progress = new ProgressBar($this->output, ($choice == 'both')? 2 : 1);
        $progress->start();

        $server = new Server($this->website);

        if($choice == 'FTP' || $choice == 'both')
        {
            $server->updateFTP();
            $progress->advance();
        }

        if($choice == 'MySQL' || $choice == 'both')
        {
            $server->updateMySQL();
            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln("\n<info>Password changed</info>\n");

        // Debug
        if(!$GLOBALS['cfg']->prod)
            $this->output->writeln($server->getLog());
    }

    private function url()
    {
        do
        {
            $question = new Question('<label>Website to update (www.example.com):</label> ');
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
            $this->output->writeln('<comment>'.$this->website->getFTPPassword().'</comment>');
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
            $this->output->writeln('<comment>'.$this->website->getMySQLPassword().'</comment>');
        }
    }
}