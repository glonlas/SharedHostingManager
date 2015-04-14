<?php

namespace SharedHostingManager\Classes;

use Symfony\Component\Process\Process;

use SharedHostingManager\Classes\Website;

class Server
{
    private $website;
    private $log = null;

    function __construct(Website $website)
    {
        $this->website  = $website;
    }

    public function addUser()
    {
        return $this->command(
            'adduser --ingroup '.$GLOBALS['cfg']->nginx->webGroup.' --shell /bin/false --disabled-password --quiet --home '.$this->website->getHome().' '.$this->website->getUsername()
        );
    }

    public function addHome()
    {
        return $this->command(
            'mkdir -p '.$this->website->getHtdocs().' && chown -R '.$this->website->getUsername().':'.$GLOBALS['cfg']->nginx->webGroup.' '.$this->website->getHome()
        );
    }

    public function addFTP()
    {
        // Get user group ID
        $process = new Process('id -u '.$this->website->getUsername());
        $process->run();
        $userId = $process->getIncrementalOutput();

        // Get web group ID
        $process = new Process('id -g  '.$GLOBALS['cfg']->nginx->webGroup);
        $process->run();
        $groupId = $process->getIncrementalOutput();

        return $this->query(
             'INSERT INTO '
            .$GLOBALS['cfg']->pureftpd->dbName.".".$GLOBALS['cfg']->pureftpd->baseName." "
            //."('User', 'status', 'Password', 'Uid', 'Gid', 'Dir', 'ULBandwidth', 'DLBandwidth', 'comment', 'ipaccess', 'QuotaSize', 'QuotaFiles') "
            ."VALUES ('".$this->website->getUsername()."', '1', MD5('".$this->website->getFTPPassword()."'), '".intval(trim($userId))."', '".intval(trim($groupId))."', '".$this->website->getHome()."', '".$GLOBALS['cfg']->pureftpd->quota->ULBandwidth."', '".$GLOBALS['cfg']->pureftpd->quota->DLBandwidth."', '', '*', '".$GLOBALS['cfg']->pureftpd->quota->QuotaSize."', '".$GLOBALS['cfg']->pureftpd->quota->QuotaFiles."');",
            $GLOBALS['cfg']->pureftpd->dbName
        );
    }

    public function addPmaPass()
    {
        $passCrypt = crypt($this->website->getMySQLPassword(), base64_encode($this->website->getMySQLPassword()));
        return $this->command(
            "echo '".$this->website->getUsername().":".$passCrypt."' >> ".$GLOBALS['cfg']->phpmyadmin->htpass
        );
    }

    public function addMySQL()
    {
        if(isset($GLOBALS['cfg']->phpmyadmin->htpass) && !is_null($GLOBALS['cfg']->phpmyadmin->htpass))
        {
            $this->addPmaPass();
        }

        return $this->query(
             "CREATE USER '".$this->website->getUsername()."'@'".$GLOBALS['cfg']->mysql->host."' IDENTIFIED BY '".$this->website->getMySQLPassword()."'; "
            ."CREATE DATABASE ".$this->website->getUsername()."; "
            ."GRANT SELECT , INSERT , UPDATE , DELETE , CREATE , DROP , INDEX , ALTER , CREATE TEMPORARY TABLES , CREATE VIEW , EVENT, TRIGGER, SHOW VIEW , CREATE ROUTINE, ALTER ROUTINE, EXECUTE ON  `".$this->website->getUsername()."` . * TO  '".$this->website->getUsername()."'@'".$GLOBALS['cfg']->mysql->host."'; "
            ."FLUSH PRIVILEGES;"
        );
    }

    public function addNginx()
    {
        $content = $this->getTemplate($this->website->getFramework().'.conf.nginx.tpl');
        $content = str_replace(
            '%url%',
            $this->website->getUrl(),
            $content
        );
        $content = str_replace(
            '%hostname%',
            $this->website->getHostname(),
            $content
        );
        $content = str_replace(
            '%htdocs%',
            $this->website->getHtdocs(),
            $content
        );

        $this->saveFile(
            $GLOBALS['cfg']->nginx->siteAvailable.$this->website->getUrl().'.conf', 
            $content
        );

        return $this->command(
            'cd '.$GLOBALS['cfg']->nginx->siteEnabled.' && ln -s '.$GLOBALS['cfg']->nginx->siteAvailable.$this->website->getUrl().'.conf ./'
        );
    }

    public function addPHP()
    {
        $content = $this->getTemplate($this->website->getFramework().'.conf.php.tpl');
        $content = str_replace(
            '%url%',
            $this->website->getUrl(),
            $content
        );
        $content = str_replace(
            '%home%',
            $this->website->getHome(),
            $content
        );
        $content = str_replace(
            '%username%',
            $this->website->getUsername(),
            $content
        );

        return $this->saveFile(
            $GLOBALS['cfg']->php->pool.$this->website->getUrl().'.conf', 
            $content
        );
    }

    public function removeUser()
    {
        return $this->command(
            'userdel -r '.$this->website->getUsername()
        );
    }

    public function removeFTP()
    {
        return $this->query(
            "DELETE FROM ".$GLOBALS['cfg']->pureftpd->dbName.".".$GLOBALS['cfg']->pureftpd->baseName." WHERE User = '".$this->website->getUsername()."'",
            $GLOBALS['cfg']->pureftpd->dbName
        );
    }

    public function removePmaPass()
    {
        return $this->command(
            "sed -i -n '/^".$this->website->getUsername().":/!p' ".$GLOBALS['cfg']->phpmyadmin->htpass
        );
    }


    public function removeMySQL()
    {
        if(isset($GLOBALS['cfg']->phpmyadmin->htpass) && !is_null($GLOBALS['cfg']->phpmyadmin->htpass))
        {
            $this->removePmaPass();
        }

        return $this->query(
             "DROP USER '".$this->website->getUsername()."'@'".$GLOBALS['cfg']->mysql->host."'; "
            ."DROP DATABASE ".$this->website->getUsername()."; "
            ."FLUSH PRIVILEGES;"
        );
    }

    public function removeNginx()
    {
        return $this->command(
            'rm '.$GLOBALS['cfg']->nginx->siteAvailable.$this->website->getUrl().'.conf '.$GLOBALS['cfg']->nginx->siteEnabled.$this->website->getUrl().'.conf'
        );
    }

    public function removePHP()
    {
        return $this->command(
            "rm ".$GLOBALS['cfg']->php->pool.$this->website->getUrl().'.conf'
        );
    }

    public function restartServers()
    {
        return $this->command(
             $GLOBALS['cfg']->php->restartCmd .' && '
            .$GLOBALS['cfg']->nginx->restartCmd . ' && '
            .$GLOBALS['cfg']->pureftpd->restartCmd
        );
    }

    public function updateFTP()
    {
        return $this->query(
            "UPDATE ".$GLOBALS['cfg']->pureftpd->dbName.".".$GLOBALS['cfg']->pureftpd->baseName." SET Password = MD5('".$this->website->getFTPPassword()."') WHERE User = '".$this->website->getUsername()."'; ",
            $GLOBALS['cfg']->pureftpd->dbName
        );
    }

    public function updateMySQL()
    {
        if(isset($GLOBALS['cfg']->phpmyadmin->htpass) && !is_null($GLOBALS['cfg']->phpmyadmin->htpass))
        {
            $this->removePmaPass();
            $this->addPmaPass();
        }

        return $this->query(
             "SET PASSWORD FOR '".$this->website->getUsername()."'@'".$GLOBALS['cfg']->mysql->host."' = PASSWORD('".$this->website->getMySQLPassword()."'); "
            ."FLUSH PRIVILEGES;"
        );
    }

    public function getLog()
    {
        return $this->log;
    }

    private function command($command)
    {
        $this->log .= "\n\n#-- COMMAND ---------------------------------\n";
        $this->log .= $command;
        
        if($GLOBALS['cfg']->prod)
        {
            $process = new Process($command);
            $process->run();

            return $process->getOutput();
        }
    }

    private function query($sql, $db = null)
    {
        (!is_null($db))? $use = '-D '.$db.' ' : $use = '';

        $tmpFile = '/tmp/'.uniqid().'.sql';
        $this->saveFile(
            $tmpFile, 
            $sql
        );

        return $this->command(
            'mysql '
                .'-h "'.$GLOBALS['cfg']->mysql->host.'" '
                .'-u "'.$GLOBALS['cfg']->mysql->user.'" '
                .'-p'.$GLOBALS['cfg']->mysql->password.' '
                . $use
                .'< '.$tmpFile
            .' && rm '.$tmpFile
        );
    }

    private function getTemplate($filename)
    {
        $file = __DIR__.'/../Resources/templates/'.$filename;

        return file_get_contents($file);
    }

    private function saveFile($file, $content)
    {
        $this->log .= "\n\n#-- FILE: ".$file." ---------------------------------\n";
        $this->log .= $content;

        if($GLOBALS['cfg']->prod)
        {
           return file_put_contents(
                $file, 
                $content
            ); 
        }  
    }
}