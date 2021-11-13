<?php
namespace App\Service;

class CountryLookup
{
    public function __construct() {
        $this->data = $this->getData();
    }

    public function getByPhone(string $phoneNumber) {
        $phoneCode = (int)substr($phoneNumber, 0, 3);
        $res = $this->data->filter(function($country) use($phoneCode) {
            $countryPhoneCode = isset($country['phone']) ? (int)preg_replace( '/[^0-9]/', '', substr($country['phone'], 0, 3)) : null;
            return $phoneCode === $countryPhoneCode;
        });

        return $res->first();
    }
    
    private function getData() {
        $rawData = file($_SERVER['DOCUMENT_ROOT'].'countryInfo.txt');
        $data = [];
        $columns = [
            'iso',
            'iso3',
            'iso_numeric',
            'fips',
            'name',
            'capital_name',
            'countrycapitalarea',
            'population',
            'continent',
            'tld',
            'currencycode',
            'currencyname',
            'phone',
            'postal_code_format',
            'postal_code_regex',
            'languages',
            'geonameid',
            'neighbours',
            'equivalentfipscode'
        ];

        foreach($rawData as $row) {
            $vals = preg_split('/\t+/', $row);

            $rowData = [];
            foreach($vals as $colIndex => $val) {
                $col = @$columns[$colIndex];
                if($col) {
                    $rowData[$col] = strlen($val) > 1 ? $val : null;
                }
                
            }

            $data[] = $rowData;
        }

        return collect($data);
    }
}