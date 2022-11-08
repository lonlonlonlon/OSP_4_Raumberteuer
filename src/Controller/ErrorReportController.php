<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorReportController extends AbstractController
{
    #[Route('/error/report', name: 'app_error_report')]
    public function index(): Response
    {
        // TODO: WIP
    }
}
