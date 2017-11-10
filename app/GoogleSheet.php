<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


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
    static protected function saveFormulasOfSheets($service, $spreadsheetId, $range, $spreadsheetRows){
        $response = $service->spreadsheets_values->get($spreadsheetId, $spreadsheetRows);
        $values = $response->getValues();
        if (count($values) == 0) {
            return "No data found";
        }
        for($i=0;$i<count($values);$i++ ){
            for($j=0;$j<count($values($i));$j++){
                $cell=$values[$i][$j];
                if(strpos((string)$cell,'=')!==false){
                    $range[$i][$j]=$values[$i][$j];
                }
            }
        }
        return $range;
    }

}
