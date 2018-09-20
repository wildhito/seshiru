<?php

namespace App\Service;

class Stock
{
    private $jsonAuth;
    private $sheetId;
    private $sheetRange;

    public function __construct()
    {
        $this->jsonAuth = json_decode(getenv('JSON_AUTH'), true);
        $this->sheetId = getenv("SPREADSHEET_ID");
        $this->sheetRange = getenv("SHEET_RANGE");
    }

    public function sheetUrl()
    {
        return "https://docs.google.com/spreadsheets/d/".$this->sheetId."/edit#gid=1141467398";
    }

    public function append($data)
    {
        $client = $this->getGoogleClient();
        $service = new \Google_Service_Sheets($client);
        $requestBody = new \Google_Service_Sheets_ValueRange([
            "range" => $this->sheetRange,
            'majorDimension' => 'ROWS',
            "values" => [
                [ "values" => $data ],
            ]
        ]);
        $service->spreadsheets_values->append($this->sheetId, $this->sheetRange, $requestBody, ['valueInputOption' => 'USER_ENTERED']);
    }

    private function getGoogleClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets Seshiru API v1');
        $client->setScopes(\Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig($this->jsonAuth);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        return $client;
    }
}
