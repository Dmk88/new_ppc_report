<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Google_Service_Sheets_ClearValuesRequest;


class GoogleSheet extends Model
{

    static protected function setToSheet($service, $spreadsheetId, $range, $spreadsheetRows)
    {
        $body      = new \Google_Service_Sheets_ValueRange(array(
            'values' => $spreadsheetRows,
        ));
        $optParams = ["valueInputOption" => "USER_ENTERED"];
        $result    = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $optParams);
        return $result;
    }
    static protected function getOfSheet($service, $spreadsheetId, $range )
    {
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        if (count($values) == 0) {
            return "No data found";
        } else {
            return $values;
        }
    }

    /**
     * Delete previous results
     * @param $spreadsheetId
     * @param $range
     */
    static protected function deletePreviousValues($service, $spreadsheetId, $range)
    {
        $clearRequest = new Google_Service_Sheets_ClearValuesRequest();
        $response = $service->spreadsheets_values->clear($spreadsheetId, $range, $clearRequest);
    }
}
