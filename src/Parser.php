<?php

namespace App;

use Carbon\Carbon;

class Parser
{
    /**
     * The source file that needs to be analyzed.
     *
     * @var
     */
    protected $inputFile;

    /**
     * Configuration file.
     *
     * @var mixed
     */
    protected $config;

    /**
     * Extension of given file.
     *
     * @var string
     */
    protected string $extension;

    /**
     * The separator of the input file.
     *
     * @var string
     */
    protected string $separator;

    /**
     * @var array
     */
    protected array $rates = [];

    /**
     * @var int
     */
    protected int $totalAmount = 0;

    /**
     * @var int
     */
    protected int $withdrawCount = 0;

    /**
     * @var null
     */
    protected $lastPaymentDate = null;

    /**
     * @var array
     */
    protected array $output = [];
    /**
     * @param $file
     * @param $rates
     */
    public function __construct($file, $rates)
    {
        $this->rates = $rates;
        $this->config = $GLOBALS['config'];
        $this->inputFile = $file;
        $this->checkIfFileReadable();
        $this->checkIfFileEmpty();
        $this->checkMimeTypes();
        $this->setSeparator();
    }

    /**
     * Parsing input file.
     */
    public function parse()
    {
        $this->chooseParsingMethod();
    }

    /**
     * Checking whether the file exists and whether it can be read.
     */
    private function checkIfFileReadable()
    {
        if (!is_readable($this->inputFile)) die("\033[31mFile not found or it is invalid.\n");
    }

    /**
     * Exit code if the file is empty.
     */
    private function checkIfFileEmpty()
    {
        if (!filesize($this->inputFile)) die("\033[31mFile is empty.\n");
    }

    /**
     * Set separator for parsing file.
     */
    private function setSeparator()
    {
        $this->separator = $this->detectDelimiter();
    }

    /**
     * Get separator for parsing file.
     *
     * @return mixed
     */
    private function getSeparator()
    {
        return $this->separator;
    }

    /**
     * Detect separator for input file.
     *
     * @return false|int|string
     */
    private function detectDelimiter()
    {
        $delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

        $handle = fopen($this->inputFile, "r");
        $firstLine = fgets($handle);
        fclose($handle);

        foreach ($delimiters as $delimiter => &$count) {
            $count = count(str_getcsv($firstLine, $delimiter));
        }

        return array_search(max($delimiters), $delimiters);
    }

    /**
     * If an XML document -- that is, the unprocessed,
     * source XML document -- is readable by casual users,
     * text/xml is preferable to application/xml.
     * MIME user agents (and web user agents)
     * that do not have explicit support for text/xml
     * will treat it as text/plain,
     * for example, by displaying the XML MIME entity as plain text.
     * And so we need to check the file extension to select the parsing method.
     *
     * From the RFC (3023 | https://www.rfc-editor.org/rfc/rfc3023), under section 3, XML Media Types:
     */
    private function checkMimeTypes()
    {
        $this->extension = strtolower(pathinfo($this->inputFile, PATHINFO_EXTENSION));

        if (!in_array($this->extension, $this->config['allowed_extension'])) {
            die("\033[31mThe mime type of file is not supported.\n");
        }
    }

    /**
     * Choosing the parsing method depending on the file extension.
     * This way, in the future we will be able to create new methods for new files.
     */
    private function chooseParsingMethod()
    {
        switch ($this->extension) {
            case 'csv':
            case 'tsv':
                $this->parseCSV();
                break;
            case 'xml':
                $this->parseXML();
                break;
            case 'json':
                $this->parseJSON();
                break;
            default:
                die("A file with the .$this->extension extension is not supported for parsing\n");
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function withdraw($data)
    {
        if ($data['user_type'] === 'business') {
            $this->output[] = number_format($data['operation_amount'] * 0.5 / 100, 2);
        }

        if ($data['user_type'] === 'private') {
            $date = Carbon::parse($data['operation_date']);

            if (is_null($this->lastPaymentDate) || !$date->isSameWeek($this->lastPaymentDate)) {
                $this->withdrawCount = 0;
                $this->totalAmount = 0;
            }

            $amount = $this->changeCurrencyIfNeeded($data);

            if ($this->withdrawCount < $this->config['free_withdraw_count']) {

                if ($this->totalAmount < $this->config['free_amount']) {
                    $remains = $this->config['free_amount'] - $this->totalAmount;

                    if ($amount < $remains) {
                        $this->output[] = number_format(0, 2);
                    } else {
                        $amount -= $remains;
                        $this->output[] = number_format($amount * 0.3 / 100, 2);
                    }

                    $this->totalAmount += $remains;
                } else {
                    $this->output[] = number_format($data['operation_amount'] * 0.3 / 100, 2);
                }

                $this->withdrawCount++;
            } else {
                $this->output[] = number_format($data['operation_amount'] * 0.3 / 100, 2);
            }

            $this->lastPaymentDate = $date;
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function deposit($data)
    {
        $this->output[] = number_format($data['operation_amount'] * 0.03 / 100, 2);
    }

    /**
     * @param $data
     * @return float|int|mixed
     */
    private function changeCurrencyIfNeeded($data)
    {
        if ($data['operation_currency'] !== 'EUR') {
            return round($data['operation_amount'] / $this->rates[$data['operation_currency']], 2);
        }

        return $data['operation_amount'];
    }

    /**
     * @return void
     */
    private function parseCSV()
    {
        $handle = fopen($this->inputFile, 'r') or die('Could not open source file');
        $fileData = [];

        while (($line = fgetcsv($handle)) !== FALSE) {
            $fileData[] = array_combine($this->config['file_headers'], $line);
        }

        fclose($handle);

        foreach ($fileData as $data) {
            if ($data['operation_type'] === 'withdraw') {
                $this->withdraw($data);
            }

            if ($data['operation_type'] === 'deposit') {
                $this->deposit($data);
            }
        }

        print_r($this->output);
    }

    private function parseXML()
    {

    }

    private function parseJSON()
    {

    }
}
