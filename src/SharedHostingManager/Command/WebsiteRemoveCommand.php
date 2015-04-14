<?php
 
namespace SharedHostingManager\Command;
 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;

use SharedHostingManager\Classes\Website;
use SharedHostingManager\Classes\Server;
 
class WebsiteRemoveCommand extends Command
{
    private $input;
    private $output;
    private $helper;
    private $website;

    protected function configure()
    {
        $this
            ->setName('website:remove')
            ->setDescription('Delete a website and all its folder form your server')
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

        $this->output->writeln("\n".'<question>Delete a website?</question>');
        $this->url();

        $confirmation = new ConfirmationQuestion(
            "\n".'<comment>Are you sure you want to delete it? [y/N]</comment> ', 
            false
        );
        $validated = $this->helper->ask(
            $this->input,
            $this->output,
            $confirmation
        );

        if($validated)
        {
            $progress = new ProgressBar($this->output, 6);
            $progress->start();

            $server = new Server($this->website);

            $server->removeFTP();
            $progress->advance();

            $server->removeMySQL();
            $progress->advance();

            $server->removeNginx();
            $progress->advance();

            $server->removePHP();
            $progress->advance();

            $server->restartServers();
            $progress->advance();

            $server->removeUser();
            $progress->advance();

            $progress->finish();
            $this->output->writeln("\n<error>Goodbye my friend</error>\n");
        }
        else
        {
            $this->output->writeln("\n<info>You almost delete it. You just saved a website!</info>\n");
        }

        // Debug
        if(!$GLOBALS['cfg']->prod)
            $this->output->writeln($server->getLog());
    }

    private function url()
    {
        do
        {
            $question = new Question('<label>Website URL to delete (www.example.com):</label> ');
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
}