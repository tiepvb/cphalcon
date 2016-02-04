<?php
namespace Phalcon\Build;

/**
 * Generates safe Phalcon extension, which is suitable for any platform (32-bit, 64-bit)
 */
class Generator_Safe
{
    /**
     * Directory with Phalcon source code
     *
     * @var string
     */
    protected $sourceDir;

    /**
     * Directory, where source code for building Phalcon will be put
     *
     * @var string
     */
    protected $outputDir;

    /**
     * Generator for phalcon.h
     *
     * @var Generator_File_PhalconH
     */
    protected $phalconH;

    /**
     * Generator for phalcon.c
     *
     * @var Generator_File_PhalconC
     */
    protected $phalconC;

    /**
     * Generator for config.m4 or config.w32
     *
     * @var array
     */
    protected $configs = array();

    /**
     * Generator for Makefile.frag
     *
     * @var Generator_File_MakefileFrag
     */
    protected $makefileFrag;

    /**
     * @param string $rootDir
     * @param string $outputDir
     */
    public function __construct($rootDir, $outputDir)
    {
        $this->sourceDir = $rootDir . '/ext';
        $this->outputDir = $outputDir;

        $configDir = $rootDir . '/build/_resource/config';

        $this->phalconH = new Generator_File_PhalconH($this->sourceDir, $outputDir);
        $this->phalconC = new Generator_File_PhalconC($rootDir, $this->sourceDir, $configDir, $outputDir);

        $this->configs[] = new Generator_File_ConfigM4($this->sourceDir, $outputDir);
        
        if (preg_match('/^WIN/', PHP_OS)) {
            $this->configs[] = new Generator_File_ConfigW32($this->sourceDir, $outputDir);
        }
    }

    /**
     * Generator source code, from which Phalcon extension can be built
     */
    public function run()
    {
        echo 'Generating safe build... ';

        Util::cleanDirectory($this->outputDir);
        $this->generateFiles();

        echo "OK\n";
    }

    /**
     * Generate all the files in the output directory
     */
    protected function generateFiles()
    {
        $includedHeaderFiles = $this->phalconH->generate();
        $this->phalconC->generate($includedHeaderFiles);
        
        foreach($this->configs as $config) {
            $config->generate();
        }

        copy($this->sourceDir . '/php_phalcon.h', $this->outputDir . '/php_phalcon.h');
        $this->processKernelGlobals();
    }

    /**
     * Resolves headers in the php_phalcon.h file
     */
    protected function processKernelGlobals()
    {
        $lines = array();
        foreach (file($this->outputDir . '/php_phalcon.h') as $line) {
            if (preg_match('@^#include "(kernel/.+)"@', $line, $matches)) {
                $content = file_get_contents($this->sourceDir . '/' . $matches[1]);
                $lines[] = $content . PHP_EOL;
            } else {
                $lines[] = $line;
            }
        }
        file_put_contents($this->outputDir . '/php_phalcon.h', join('', $lines));
    }
}
