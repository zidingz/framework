<?php

namespace SPF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SPF\Exception\InvalidArgumentException;
use SPF\Rpc\Config;
use SPF\Rpc\Tool\Tars2php\FileConverter;
use SPF\Rpc\Tool\Tars2php\Utils;
use Throwable;

class Tars2php extends Command
{
    protected function configure()
    {
        $this->setName('tars2php')
            ->setDescription('automatic generate structs and interfaces according to tars file')
            ->setHelp('You can automatic generate structs and interfaces according to tars file using this command')
            ->setDefinition(
                new InputDefinition([
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tarsConfig = Config::getOrFailed('app.tars');
        if (empty($tarsConfig['nsPrefix'])) {
            $tarsConfig['nsPrefix'] = Config::getOrFailed('app.namespacePrefix');
        }

        $rootPath = Config::$rootPath;

        Utils::setConsoleOutput($output);
        
        $fileConverter = new FileConverter($tarsConfig, $rootPath);
        
        $fileConverter->moduleScanRecursive();
        $fileConverter->moduleParserRecursive();
    }

    /**
     * Get option value from input.
     * 
     * @param InputInterface $input
     * @param string $name option`s name
     * @param string $default default value if the option null
     * 
     * @return string
     */
    protected function getOption(InputInterface $input, $name, $default = null)
    {
        $option = $input->getOption($name);
        if (is_null($option) && is_null($default)) {
            throw new InvalidArgumentException("option [$name] cannot be empty");
        }

        return $option ?: $default;
    }

    /**
     * Resolve path to full path.
     * 
     * @param string $path
     * 
     * @return string
     */
    protected function resolvePath($path)
    {
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();

        return mb_substr($path, 0, 1) == '/' ? $path : $rootPath.'/'.$path;
    }

    /**
     * Validate the source`s value
     * 
     * @param string $src
     */
    protected function validSource($src)
    {
        if (!is_dir($src)) {
            throw new InvalidArgumentException("option source`s value [$src] is not a directory");
        }
    }
}
