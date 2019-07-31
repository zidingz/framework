<?php

namespace SPF\Validator;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PhpParser\ParserFactory;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node\Stmt;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ValidateRpcMethodParams
{
    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * Symfony console output instance.
     * 
     * @var OutputInterface
     */
    protected $output = null;

    /**
     * Test cases source path.
     * 
     * @var string
     */
    protected $rootPath = null;

    /**
     * Find error count.
     * 
     * @var int
     */
    protected $errorCount = 0;

    /**
     * Allowed class method return type
     * 
     * @var array
     */
    protected $allowedReturnType = [
        'int', 'array', 'string', 'float', 'bool',
    ];

    /**
     * Allowed class method param type
     * 
     * @var array
     */
    protected $allowedParamType = [
        'int', 'array', 'string', 'float', 'bool', 'callable',
    ];

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output = null)
    {
        if (is_null($output)) {
            $output = new ConsoleOutput();
        }
        $this->output = $output;

        $this->initParser();
    }

    /**
     * Handle.
     * 
     * @param string $root
     */
    public function handle($root)
    {
        $this->rootPath = $root;
        $this->errorCount = 0;

        $this->writeln("<info>start check methods`s params of classes<info>");

        $this->validate();

        return $this->errorCount === 0;
    }

    public function validate()
    {
        $dir = new RecursiveDirectoryIterator($this->rootPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($dir);

        foreach ($files as $file) {
            $code = file_get_contents($file);
            $stmts = $this->getParser()->parse($code);
            $this->recursiveReadStmts($stmts, $file);
        }
    }

    /**
     * @param array $stmts php-parser stmts array
     * @param int $classCount class count
     */
    protected function recursiveReadStmts($stmts, $file, &$classCount = 0, &$namespace = null, &$class = null)
    {
        foreach ($stmts as $node) {
            // get the code file`s namesapce
            if ($node instanceof Stmt\Namespace_) {
                $namespace = (string) $node->name;
            }

            // get the class name
            if (($node instanceof Stmt\Class_) || ($node instanceof Stmt\Interface_) || ($node instanceof Stmt\Trait_)) {
                $class = (string) $node->name;
                $classCount++;

                // if class great than 1, then throw error
                if ($classCount > 1) {
                    $classFullName = $this->getClassFullName($class, $namespace);

                    $this->writeln("<error>同一个文件 [{$file}] 中不允许定义超过一个类 [{$classFullName}]</error>");
                    $this->errorCount++;
                }
            }

            // get method params`s type and return type
            if ($node instanceof Stmt\ClassMethod) {
                $methodName = (string) $node->name;
                $classFullName = $this->getClassFullName($class, $namespace);
                
                $allowedParamType = $this->getAllowedParamType();
                $allowedParamTypeString = implode(', ', $allowedParamType);
                foreach($node->params as $param) {
                    $paramName = (string)$param->var->name;
                    $paramType = (string)$param->type;
                    if (!$paramType) {
                        $this->writeln(
                            "<error>方法 <comment>[{$classFullName}::{$methodName}]</comment> 的参数 ".
                            "<comment>{$paramName}</comment> 类型不能为空</error>"
                        );
                        $this->errorCount++;
                    } elseif (!in_array($paramType, $allowedParamType)) {
                        $this->writeln(
                            "<error>方法 <comment>[{$classFullName}::{$methodName}]</comment> 的参数 ".
                            "<comment>{$paramName}</comment> 类型 <comment>{$paramType}</comment> ".
                            "不在允许范围内 <comment>[$allowedParamTypeString]</comment></error>"
                        );
                        $this->errorCount++;
                    }
                }

                $allowedReturnType = $this->getAllowedReturnType();
                $allowedReturnTypeString = implode(', ', $allowedReturnType);

                $returnType = (string) $node->returnType;

                if (!$returnType) {
                    $this->writeln(
                        "<error>方法 <comment>[{$classFullName}::{$methodName}]</comment> 的返回值类型不能为空</error>"
                    );
                    $this->errorCount++;
                } elseif (!in_array($returnType, $allowedReturnType)) {
                    $this->writeln(
                        "<error>方法 <comment>[{$classFullName}::{$methodName}]</comment> 的返回值类型 ".
                        "<comment>{$returnType}</comment> 不在允许范围内 <comment>[$allowedReturnTypeString]</comment></error>"
                    );
                    $this->errorCount++;
                }
            }

            // recursive read stmts if there is any other class
            if (isset($node->stmts)) {
                $this->recursiveReadStmts($node->stmts, $file, $classCount, $namespace, $class);
                continue;
            }
        }
    }

    /**
     * Get class full name by namespace and class simple name
     * 
     * @param string $class
     * @param string $namesapce
     * 
     * @return string
     */
    protected function getClassFullName($class, $namespace = null)
    {
        if (is_null($namespace)) {
            return $class;
        } else {
            return $namespace . '\\' . $class;
        }
    }

    /**
     * Get allowed class method return type.
     * 
     * @return array
     */
    protected function getAllowedReturnType()
    {
        return $this->allowedReturnType;
    }

    /**
     * Get allowed class method param type.
     * 
     * @return array
     */
    protected function getAllowedParamType()
    {
        return $this->allowedParamType;
    }

    /**
     * Initializa PHP-Parser instance.
     */
    protected function initParser()
    {
        $lexer = new Lexer([
            'usedAttributes' => ['comments'],
        ]);

        $factory = new ParserFactory;

        $this->parser = $factory->create(ParserFactory::ONLY_PHP7, $lexer);
    }

    /**
     * Get the PHP-Parser instance.
     * 
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Get the symfony console instance.
     * 
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Output log and line feed.
     * 
     * @param string $msg
     */
    public function writeln($msg)
    {
        $this->log($msg, "writeln");
    }

    /**
     * Output log.
     * if there doesn`s have symfony output instance, then console log by echo.
     * 
     * @param string $msg
     * @return string $method
     */
    public function log($msg, $method = "write")
    {
        if (is_null($this->getOutput())) {
            echo $method == 'writeln' ? $msg . PHP_EOL : $msg;
        } else {
            call_user_func([$this->getOutput(), $method], $msg);
        }
    }
}
