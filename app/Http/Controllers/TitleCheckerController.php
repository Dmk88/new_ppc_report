<?php
/**
 * Checks page titles from https://docs.google.com/spreadsheets/d/1bjK7r4Ld__HcbVgB8tmlBc0prvVBcA7orLbJc0AJ5Wc/edit#gid=1270649255
 */

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use Google_Service_Sheets;
use Mail;

class TitleCheckerController extends Controller
{
    private $spreadSheetID = '1bjK7r4Ld__HcbVgB8tmlBc0prvVBcA7orLbJc0AJ5Wc';
    private $spreadSheetRange = 'Auto-tracking!A2:B';

    /**
     * Parse title of web page by url
     * @param $url
     * @return null|string
     */
    private function getTitle($url)
    {
        try {
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTMLFile($url);
            $xpath = new \DOMXPath($doc);
            $nlist = $xpath->query("//head/title");
            return trim($nlist->item(0)->nodeValue);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Web action
     */
    public function index()
    {
        ini_set("max_execution_time", 0);

        $error = null;
        $urlsToCheck = $urlsWithErrors = [];

        try {
            $params = [
                'APPLICATION_NAME' => 'Google Sheets API',
                'CREDENTIALS_PATH' => app_path() . '/ApiSources/google-sheets.json',
                'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret_sheets.json',
                'SCOPES' => [Google_Service_Sheets::SPREADSHEETS_READONLY]
            ];
            $client = GoogleClient::get_instance($params);
            $service = new Google_Service_Sheets($client->client);

            $values = $service->spreadsheets_values->get($this->spreadSheetID, $this->spreadSheetRange);
            if (count($values) == 0) {
                $error = 'No data found in google spreadsheet';
            } else {
                foreach ($values as $row) {
                    $urlsToCheck[trim($row[0])] = trim($row[1]);
                }
            }
        } catch (\Throwable $e) {
            $error = 'Error opening google spreadsheet. Code ' . $e->getCode();
        }

        if (!$error) {
            foreach ($urlsToCheck as $url => $title) {
                $fetchedTitle = $this->getTitle($url);
                if ($fetchedTitle != $title) {
                    $urlsWithErrors[$url] = [$fetchedTitle, $title];
                }
            }
        }

        if ($error || count($urlsWithErrors)) {
            $recipients = explode(',', env('TITLE_CHECKER_RECIPIENTS'));
            $data = [
                'error' => $error,
                'urlsToCheck' => $urlsToCheck,
                'urlsWithErrors' => $urlsWithErrors
            ];

            Mail::send('title_checker.email', $data, function ($message) use ($recipients) {
                $message->to($recipients)->subject('Altoros Titles Report');
            });
        }
    }
}