<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;

class BookController extends AbstractController
{
    /**
     * @Route("/book/add", name="add_book")
     * @Template
     */
    public function add()
    {
        return [
        	"remoteHost" => getenv("REMOTE_HOST"),
            "sheetUrl" => "https://docs.google.com/spreadsheets/d/".getenv("SPREADSHEET_ID")."/edit#gid=1141467398"
        ];
    }

    /**
     * @Route("/book/search", name="search_book")
     */
    public function search(Request $request)
    {
    	$isbn = $request->query->get("isbn");
        if (!$isbn) {
            return new JsonResponse([
                "error" => "L'ISBN est vide !",
            ], 400);
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', getenv("REMOTE_HOST") . $isbn);
        if ($res->getStatusCode() !== 200) {
            return new JsonResponse([
                "error" => "Le serveur a renvoyé une " . $res->getStatusCode(),
            ], 400);
        }

        $body = $res->getBody()->getContents();
        $crawler = new Crawler($body);
        if (!$body) {
            return new JsonResponse([
                "error" => "Corps de réponse vide",
            ], 400);
        };

        $title = $this->getProperty($crawler, '#book-title-and-details h1');
        $subtitle = $this->getProperty($crawler, '#book-title-and-details h2');
        $author = $this->getProperty($crawler, '#book-title-and-details #creators a');
        $weight = $this->getProperty($crawler, '#weight');
        $dimensions = $this->getProperty($crawler, '#dimensions');
        $postalFees = $this->getPostalFees(intval($weight));

        return new JsonResponse([
            "isbn" => $isbn,
            "title" => $title,
            "subtitle" => $subtitle,
            "author" => $author,
            "weight" => $weight,
            "dimensions" => $dimensions,
            "postalFees" => $postalFees,
        ]);
    }

    /**
     * @Route("/book/stock", name="stock_book")
     */
    public function stock(Request $request)
    {
        $this->appendToGoogleSheet([
            $request->query->get('isbn', ''),
            $request->query->get('title', ''),
            $request->query->get('subtitle', ''),
            $request->query->get('author', ''),
            $request->query->get('dimensions', ''),
            $request->query->get('weight', ''),
            $request->query->get('buyPrice', ''),
            $request->query->get('sellPrice', ''),
            $request->query->get('postalFees', ''),
            $request->query->get('quantity', ''),
            getenv("REMOTE_HOST") . $request->query->get('isbn', ''),
        ]);
        return new JsonResponse([]);
    }

    private function getProperty(Crawler $crawler, $cssSelector)
    {
        try {
            return $crawler->filter($cssSelector)->first()->text();
        } catch (\Exception $e) {
            return "";
        }
    }

    private function getPostalFees($weight)
    {
        if (!$weight) {
            return 0;
        }

        if ($weight <= 20) {
            return 0.8;
        }

        if ($weight <= 100) {
            return 1.6;
        }

        if ($weight <= 250) {
            return 3.2;
        }

        if ($weight <= 500) {
            return 4.8;
        }

        return 6.4;
    }

    private function getGoogleClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(\Google_Service_Sheets::SPREADSHEETS);
        $jsonAuth = json_decode(getenv('JSON_AUTH'), true);
        $client->setAuthConfig($jsonAuth);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        return $client;
    }

    private function appendToGoogleSheet($data)
    {
        $client = $this->getGoogleClient();
        $service = new \Google_Service_Sheets($client);
        $spreadsheetId = getenv("SPREADSHEET_ID");
        $range = getenv("SHEET_RANGE");
        $requestBody = new \Google_Service_Sheets_ValueRange([
            "range" => $range,
            'majorDimension' => 'ROWS',
            "values" => [
                ["values" => $data ]
            ]
        ]);
        $response = $service->spreadsheets_values->append($spreadsheetId, $range, $requestBody, ['valueInputOption' => 'USER_ENTERED']);
    }

}

