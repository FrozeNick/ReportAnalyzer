<?php
namespace App\Service;

use App\Service\IPStack;
use App\Service\CountryLookup;
use Carbon\Carbon;

class ReportAnalyzer
{
    private $reportFileName;
    private $reportFile;
    private $reportData = [];

    // Define available columns
    CONST COLUMNS = [
        [
            'name' => 'customer_id',
            'type' => 'int'
        ],
        [
            'name' => 'call_date',
            'type' => 'datetime'
        ],
        [
            'name' => 'call_duration',
            'type' => 'int'
        ],
        [
            'name' => 'call_to',
            'type' => 'string'
        ],
        [
            'name' => 'customer_ip',
            'regex_remove' => '/\s+/',
            'type' => 'string'
        ]
    ];

    public function __construct(string $reportFileName)
    {
        $this->reportFileName = $reportFileName;
        $this->reportFile = $this->loadReportFile();
    }

    // Analyze data from uploaded report file
    public function analyze() {
        // Collect report data by parsing the file
        $this->reportData = collect($this->parse());

        // Get ip data for all unique ip addresses on file
        $ipData = IPStack::lookup($this->reportData->map(function($row) {
            return $row['customer_ip'];
        })->unique()->toArray());

        // Create a new CountryLookup instance for later use
        // written here because we don't want to call it again for each row
        $countryLookup = new CountryLookup;

        // Set important information about the customer and the call
        $this->reportData = $this->reportData->map(function($row) use($ipData, $countryLookup) {
            // Get country of the customer by his IP address
            $getCountryByIp = isset($ipData[$row['customer_ip']]) ? $ipData[$row['customer_ip']] : null;
            // Get country of the phone number customer dialed to
            $getCountryByPhone = $countryLookup->getByPhone($row['call_to']);

            if($getCountryByIp) {
                // Set customer continent if we actually got data from his IP address
                $row['customer_continent'] = $getCountryByIp['continent_code'];
            }

            if($getCountryByPhone) {
                // Set call continent distantation if we got data from dialed phone number
                $row['call_to_continent'] = $getCountryByPhone['continent'];
            }
            
            // Check if both the customer continent and phone continent are the same
            $row['is_same_continent'] = $getCountryByPhone && $getCountryByIp ? $row['customer_continent'] === $row['call_to_continent'] : false;

            return $row;
        });

        return $this;
    }

    // Get report data collection
    public function get() {
        return $this->reportData;
    }

    // Get report data collection for each individual customer
    public function getCustomerData() {
        return $this->reportData->groupBy('customer_id')->map(function($calls) {
            return [
                'customer_id' => $calls->first()['customer_id'],
                'call_stats' => [
                    'total' => $calls->count(),
                    'total_same_continent' => $calls->where('is_same_continent', true)->count(),
                    'total_duration' => $calls->sum('call_duration'),
                    'total_duration_same_continent' => $calls->where('is_same_continent', true)->sum('call_duration')
                ]
            ];
        });
    }

    // Check if report file was loaded to this class
    public function doesExist() {
        return $this->reportFile ? true : false;
    }

    // Parse data from report file and return an col => val formatted array
    private function parse() {
        $parsedData = [];

        foreach($this->reportFile as $line) {
            $vals = explode(',', $line);
            $row = [];
            foreach($vals as $key => $val) {
                $col = self::COLUMNS[$key];
                $row[$col['name']] = $this->parseColValue($col, $val);
            }

            $parsedData[] = $row;
        }

        return $parsedData;
    }

    // Parse the value of a given col
    private function parseColValue(array $col, $value) {
        if($col['type'] === 'string') {
            $value = (string)$value;
        } else if($col['type'] === 'int') {
            $value = (int)$value;
        } else if($col['type'] === 'datetime') {
            $value = Carbon::parse($value);
        }

        if(isset($col['regex_remove'])) {
            $value = preg_replace($col['regex_remove'], '', $value);
        }

        return $value;
    }

    // Load the report file
    private function loadReportFile() {
        try {
            return file($_SERVER['DOCUMENT_ROOT'].$this->reportFileName);
        } catch(\Exception $e) {
            return null;
        }
    }
}