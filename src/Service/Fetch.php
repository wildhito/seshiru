<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;

class Fetch
{
    private $remoteHost;

    public function __construct()
    {
        $this->remoteHost = getenv("REMOTE_HOST");
    }

    public function remoteHost()
    {
        return $this->remoteHost;
    }

    public function bookUrl($isbn)
    {
        return $this->remoteHost . $isbn;
    }

    public function book($isbn)
    {
        if (empty($isbn)) {
            return [
                "error" => "L'isbn est vide",
            ];
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $this->bookUrl($isbn));
        if ($res->getStatusCode() !== 200) {
            return [
                "error" => "Le serveur distant a renvoyé une " . $res->getStatusCode(),
            ];
        }

        $body = $res->getBody()->getContents();
        if (!$body) {
            return [
                "error" => "La réponse du serveur distant est vide",
            ];
        };

        $crawler = new Crawler($body);

        return [
            "isbn" => $isbn,
            "title" => $this->getProperty($crawler, '#book-title-and-details h1'),
            "subtitle" => $this->getProperty($crawler, '#book-title-and-details h2'),
            "author" => $this->getProperty($crawler, '#book-title-and-details #creators a'),
            "weight" => $this->getProperty($crawler, '#weight'),
            "dimensions" => $this->getProperty($crawler, '#dimensions'),
            "postalFees" => $this->getPostalFees(intval($this->getProperty($crawler, '#weight'))),
        ];
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
}
