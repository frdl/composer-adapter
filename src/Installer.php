<?php

namespace Webfan\ComposerAdapter;


use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;

use Webfan\InstallerInterface;
use Webfan\InstallerSequenceInterface;
use Webfan\InstallerEventsInterface;

class Installer implements InstallerInterface
{

    /**
     * The working path to regenerate from.
     *
     * @var string
     */
    protected $workingPath;
    protected $config;
    /**
     * Create a new ComposerHelper instance.
     *
     * @param  string $workingPath
     */
    public function __construct($workingPath = null, array $config = [])
    {
      //  set_time_limit(0);

        $this->workingPath = $workingPath;
        $this->config = $config;
    }


 


    public function setDirectory(string $dir){
       return $this->setWorkingPath($dir);
    }

  
 
    /**
     * Require one or multiple packages.
     *
     * @param array $packages Package name.
     * @param array $options
     * @return Process  
     */
    public function require(array | string $packages, array $options = []){
        if(!is_array($packages)){
           $packages = [$packages];
        }
        return $this->requirePackages($packages, $options);
    }

    /**
     * Remove one or more packages.
     *
     * @param array $packages Package name.
     * @param array $options
     * @return Process  
     */
    public function remove(array | string $packages, array $options = []){
        if(!is_array($packages)){
           $packages = [$packages];
        }
        return $this->removePackages($packages, $options);
    }

    
    /**
     * Call composer command.
     *
     * @param array $options
     * @return string
     */
    public function composer(array $options = [])
    {
       return $this->run( '', $options);
    }
    public function init(array $options = []){
       return $this->composer($options);
    }
    /**
     * Install composer packages.
     *
     * @param array $options
     * @return Process
     */
    public function install(array $options = [])
    {
        return $this->run( 'install', $options);
    }

    public function run(string $command, array $args = [])
    {
        $process = $this->getProcess();
        $process->setCommandLine(sprintf($this->findComposer().'%', $command) . $this->normalizeOptions($args));

        return $this->runProcess($process);
    }
    /**
     * Generates zip/tar
     * @param array $options
     * @return Process
     */
    public function archive(array $options = [])
    {
        return $this->run( 'archive', $options);
    }

    /**
     * Update composer packages.
     *
     * @param array $options
     * @return Process
     */
    public function update(array $options = [])
    {
       return $this->run( 'update', $options);
    }

    /**
     * Require one or multiple packages.
     *
     * @param array $packages Package name.
     * @param array $options
     * @return Process
     */
    public function requirePackages(array $packages, array $options = [])
    {
        $packageString = $this->normalizePackages($packages);
        $optionsString = $this->normalizeOptions($options);

        $process = $this->getProcess();
        $process->setCommandLine($this->findComposer() . 'require ' . $packageString . $optionsString);

        return $this->runProcess($process);
    }

    /**
     * Remove one or more packages.
     *
     * @param array $packages Package name.
     * @param array $options
     * @return Process
     */
    public function removePackages(array $packages, array $options = [])
    {
        $packageString = $this->normalizePackages($packages, [
            'packageVersion' => false
        ]);
        $optionsString = $this->normalizeOptions($options);

        $process = $this->getProcess();
        $process->setCommandLine($this->findComposer() . 'remove ' . $packageString . $optionsString);

        return $this->runProcess($process);
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    public function findComposer(?bool $tryGlobal = true, ?bool $preInstall = false) : string
    {
        if (!file_exists($this->workingPath . \DIRECTORY_SEPARATOR.'composer.phar') || true === $tryGlobal ) {           
            try{
                 $c = function_exists('exec') ? exec('which composer') : 'composer';
                if(!empty($c) ){
                    $c.=' ';
                }else{
                    $c = 'composer ';
                }
                 return $c;
            }catch(\Exception $e){
                  
            }           
        }

        if (!file_exists($this->workingPath . \DIRECTORY_SEPARATOR.'composer.phar') && true === $preInstall ) {           
            $this->preInstall($this->workingPath, $tryGlobal);
        }

        if (!file_exists($this->workingPath . \DIRECTORY_SEPARATOR.'composer.phar')   ) {           
            throw new \Exception('command path not found in '.__METHOD__);
        }        

        $binary = ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));

        if (defined('HHVM_VERSION')) {
            $binary .= ' --php';
        }

        return "{$binary} composer.phar ";
    }

    
    public function available(?string $toPath = null, ?bool $tryGlobal = false) : bool
    {
        $available = false;
        if(null === $toPath){
          $toPath = $this->workingPath;
        }
           try{
                $available = 'string' === $this->findCommandPath( $tryGlobal , false);
            }catch(\Exception $e){

               $available = false;
            }                   

        return $available;
    }
    
    public function findCommandPath(?bool $tryGlobal = false, ?bool $preInstall = false) : string
    {
       return $this->findComposer($tryGlobal, $preInstall);
    }
//https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
/* 
#!/bin/sh

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php
exit $RESULT
*/
    public function preInstall(?string $toPath = null, ?bool $tryGlobal = false) : bool
    {
        if(true === $this->available($toPath, true)){
           return true;
        }
        
        if(null === $toPath){
          $toPath = $this->workingPath;
        }
        
          // throw new \Exception('Not implemented yet in '.__METHOD__);

         $setupfile =  $this->workingPath.\DIRECTORY_SEPARATOR.'composer-setup.php';
         $checksumfile = $this->workingPath.\DIRECTORY_SEPARATOR.'composer-installer.sig';
        if(!file_exists($checksumfile) || filemtime($checksumfile) < 5 * 60){
               $EXPECTED_CHECKSUM = file_get_contents('https://composer.github.io/installer.sig');
               file_put_contents($checksumfile,$EXPECTED_CHECKSUM);
        }else{
            $EXPECTED_CHECKSUM = file_get_contents($checksumfile);
        }

        if(!file_exists($setupfile) || filemtime($setupfile) < 5 * 60){
            copy('https://getcomposer.org/installer', $setupfile);
        }

        
           $ACTUAL_CHECKSUM =  hash_file('sha384',$setupfile);

           if($EXPECTED_CHECKSUM !== $ACTUAL_CHECKSUM){
              unlink($setupfile);
           }

         $process = $this->getProcess();

        $binary = ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
        if (defined('HHVM_VERSION')) {
            $binary .= ' --php';
        }
        $command = "{$binary} composer-setup.php";
        
        
         $process->setCommandLine(sprintf( ' --php'.' %', $command) . $this->normalizeOptions(['--quiet']));

         $result = $this->runProcess($process);

          unlink($setupfile);
        
        return $result;
    }

    
    /**
     * Get a new Symfony process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess()
    {
        return (new Process('', $this->workingPath))->setTimeout(null);
    }

    /**
     * Runs the process and debugs result.
     *
     * @param Process $process
     * @return Process
     */
    protected function runProcess(Process $process)
    {
        $process->mustRun(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo '<pre>ERR > ' . $buffer . '</pre>';
            } else {
                echo '<pre>OUT > ' . $buffer . '</pre>';
            }
        });
        return $process;
    }

    /**
     * Returns a list of packages into a string to use in composer call.
     *
     * Example input: [
     *      'symfony/yaml' => 'dev-master',
     *      'symfony/config'
     * ];
     *
     * Example output: "symfony/yaml:dev-master" "symfony/config"
     *
     * ### Options
     * - `packageVersion` - Add package version into the string. If false, only the package name will be used.
     *
     * @param array $packages
     * @param array $options
     * @return string
     */
    protected function normalizePackages(array $packages, array $options = [])
    {
        $_options = [
            'packageVersion' => true
        ];
        $options = array_merge($_options, $options);

        $packageList = [];
        foreach ((array)$packages as $packageName => $packageVersion) {
            if (is_int($packageName)) {
                $packageName = $packageVersion;
                $packageVersion = false;
            }
            if ($options['packageVersion'] === false) {
                $packageVersion = false;
            }
            $packageList[] = escapeshellarg($packageName . (($packageVersion) ? ":" . $packageVersion : ""));
        }
        return implode(" ", $packageList);
    }

    /**
     * Returns a list of options into a string of options to use in composer call.
     *
     * @param array $options
     * @return string
     */
    //https://getcomposer.org/doc/03-cli.md
    protected function normalizeOptions(array $options)
    {
        $optionsList = [];
        foreach ((array)$options as $option => $value) {
            if (is_int($option)) {
                $option = $value;
                $value = false;
            }
            $optionsList[] = $option . (($value) ? " " . escapeshellarg($value) : "");
        }
        return " " . implode(" ", $optionsList);
    }

    /**
     * Set the working path used by the class.
     *
     * @param  string $path
     * @return $this
     */
    protected function setWorkingPath($path)
    {
        $this->workingPath = realpath($path);

        return $this;
    }

}
