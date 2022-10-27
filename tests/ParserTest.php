<?php

namespace php_shell;
require __DIR__ . '../vendor/autoload.php';
$GLOBALS['config'] = require_once 'config.php';

use App\Parser;

require_once 'helpers.php';

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * Lines to add to the text file.
     *
     * @var int
     */
    protected $lines;

    /**
     * File for testing.
     *
     * @var string
     */
    protected $testCSVFileName;

    public function __construct()
    {
        parent::__construct();
        $this->lines = 100000;
        $this->testCSVFileName = __DIR__ . 'ParserTest.php/' . time() . '-test-csv-file.csv';
    }

    /**
     * File header names.
     *
     * @return string[]
     */
    public function filHeadings()
    {
        return $GLOBALS['config']['file_headers'];
    }

    /**
     *
     * @return array
     */
    public function generateRandomValuesForFields()
    {
        $csvLine = [];
        $randomNumber = rand(10, 30);

        for ($i = 0; $i < count($this->filHeadings()); $i++) {
            //
        }

        return $csvLine;
    }

    /**
     * Creating a text file that needs to be parsed.
     */
    public function createTestFile()
    {
        $csv = fopen($this->testCSVFileName, 'w');
        fputcsv($csv, $this->filHeadings());

        for ($i = 0; $i < $this->lines; $i++) {
            fputcsv($csv, $this->generateRandomValuesForFields());
        }

        fclose($csv);
    }

    /**
     * Options from command line.
     *
     * @return array
     */
    public function getCommandOptions()
    {
        return [
            'file' => $this->testCSVFileName,
        ];
    }

    /**
     * Parsing file
     */
    public function testParseFile()
    {
        // Given
        $this->createTestFile();
        $rates = [];

        try {
            $rates = loadRates();
        } catch (\Exception $e) {
            echo "\033[31m" . $e->getMessage() . "\n";
            echo "\33[0;36mA local source was used to calculate the rates.\n";
            $rates = $GLOBALS['config']['rates'];
        }

        // When
        (new Parser($this->getCommandOptions(), $rates))->parse();

        // Then
//        $this->assertFileExists();
    }
}
