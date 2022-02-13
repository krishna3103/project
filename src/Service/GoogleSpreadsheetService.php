<?php

namespace App\Service;

use Google\Exception;

class GoogleSpreadsheetService {

    private string $spreadsheetId;
    private string $sheetTitle;

    public function __construct() { }

    public function setSpreadsheetId($id) {
        $this->spreadsheetId = $id;
    }

    public function getSpreadsheetId(): string
    {
        return $this->spreadsheetId;
    }

    public function setSheetTitle($title) {
        $this->sheetTitle = $title;
    }

    public function getSheetTitle(): string
    {
        return $this->sheetTitle;
    }


    public function serviceInit(): \Google_Service_Sheets
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google sheets and php');
        $client->setScopes(\Google_Service_Sheets::SPREADSHEETS);
        $client->setAccessType('offline');

        try {
            $client->setAuthConfig(getcwd() . '/credentials.json');
            $service = new \Google_Service_Sheets($client);
        } catch (\Google_Exception | Exception $e) {
            $service = $e;
        }
        return $service;
    }

    public function clearValues()
    {
        $requestBody = new \Google_Service_Sheets_ClearValuesRequest();
        return $this->serviceInit()->spreadsheets_values->clear($this->spreadsheetId, $this->sheetTitle, $requestBody);
    }

    public function checkSpreadsheet(): \Google_Service_Sheets_Spreadsheet
    {
        $service = $this->serviceInit();
        return $service->spreadsheets->get($this->spreadsheetId);
    }

    public function checkSheet(): bool
    {
        $service = $this->serviceInit();
        $sheetInfo = $service->spreadsheets->get($this->spreadsheetId);
        $allSheetInfo = $sheetInfo['sheets'];
        $sheetData = array_column($allSheetInfo, 'properties');
        if(!empty($sheetData)) {
            foreach ($sheetData AS $element) {
                if ($element->title == $this->sheetTitle) {
                    return true;
                }
            }
            return false;
        }
    }

    public function addSheet(): \Google_Service_Sheets_BatchUpdateSpreadsheetResponse
    {
        $service = $this->serviceInit();
        $request_body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $request_body->setRequests([
            "addSheet" => new \Google_Service_Sheets_AddSheetRequest([
                "properties" => new \Google_Service_Sheets_SheetProperties([
                    "title" => $this->sheetTitle
                ])
            ])
        ]);
        return $service->spreadsheets->batchUpdate($this->spreadsheetId, $request_body);
    }

    public function create()
    {
        $service = $this->serviceInit();
        $spreadsheet = new \Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => $this->sheetTitle
            ]
        ]);
        $spreadsheet = $service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
        return $spreadsheet->spreadsheetId;
    }

    public function clearAndSaveDataInGoogleSheet($data) : int
    {
        //Checking for sheet exist if not then create new sheet.
        $sheetStatus = $this->checkSheet();
        if ($sheetStatus === false) {
            $this->addSheet();
        }

        //Clear the google spreadsheet data
        $this->clearValues();

        $body = new \Google_Service_Sheets_ValueRange([
            'values' => $data
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $insert = [
            'insertDataOption' => 'INSERT_ROWS'
        ];

        //Started updating the data to google sheet
        $result = $this->serviceInit()->spreadsheets_values->append(
            $this->spreadsheetId,
            $this->sheetTitle,
            $body,
            $params,
            $insert
        );

        return $result->getUpdates()->getUpdatedRows();
    }

}
