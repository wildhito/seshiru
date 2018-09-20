<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\Fetch;
use App\Service\Stock;

class BookController extends AbstractController
{
    private $fetchService;

    public function __construct(Fetch $fetchService, Stock $stockService)
    {
        $this->fetchService = $fetchService;
        $this->stockService = $stockService;
    }

    /**
     * @Route("/", name="home")
     * @Template
     */
    public function home()
    {
        return [
            "remoteHost" => $this->fetchService->remoteHost(),
            "sheetUrl" => "https://docs.google.com/spreadsheets/d/".getenv("SPREADSHEET_ID")."/edit#gid=1141467398"
        ];
    }

    /**
     * @Route("/book/search", name="search_book")
     */
    public function search(Request $request)
    {
        $isbn = $request->query->get("isbn");

        $res = $this->fetchService->book($isbn);

        $status = 200;
        if (!empty($res["error"])) {
            $status = 400;
        }

        return new JsonResponse($res, $status);
    }

    /**
     * @Route("/book/stock", name="stock_book")
     */
    public function stock(Request $request)
    {
        $this->stockService->append([
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
            $this->fetchService->bookUrl($request->query->get('isbn', '')),
        ]);
        return new JsonResponse([]);
    }

}
