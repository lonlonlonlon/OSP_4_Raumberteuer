<?php

namespace App\Controller;

use App\Repository\ErrorReportRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\EnumClass;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ErrorReportController extends AbstractController
{
    #[Route('/api/v1/reports', name: 'get_error_reports', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, ErrorReportRepository $errorReportRepository, RoomRepository $roomRepository): Response
    {
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        switch ($user->getRole()) {
            case (EnumClass::$WERKSTATT_ROLE):
            case (EnumClass::$ADMIN_ROLE):
                $reports = $errorReportRepository->findAll();
                break;
            case (EnumClass::$LEHRER_ROLE):
                $reports = [];
                break;
            case (EnumClass::$BETREUER_ROLE):
                // alle Fehler seiner Räume
                $rooms = $roomRepository->findAll();
                $hisRoomsErrors = [];
                foreach ($rooms as $room) {
                    if ($room->getSupervisor() == $user) {
                        $hisRoomsErrors = array_merge($errorReportRepository->findBy(['reportedRoom' => $room]), $hisRoomsErrors);
                    }
                }
                $reports = $hisRoomsErrors;
                break;
        }

        $json = [];
        foreach ($reports as $report) {
            $json[$report->getId()]['category'] = $report->getCategory();
            $json[$report->getId()]['id'] = $report->getId();
            $json[$report->getId()]['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
            $json[$report->getId()]['dateTime'] = $report->getDateTime();
            $json[$report->getId()]['description'] = $report->getMessage();
            $json[$report->getId()]['status'] = $report->getState();
            $json[$report->getId()]['reportedBy'] = $report->getReportedBy()->getId();
            $json[$report->getId()]['reportedRoom'] = $report->getReportedRoom()->getId();
            $json[$report->getId()]['roomType'] = $report->getReportedRoom()->getRoomType();
            $json[$report->getId()]['position'] = (object)[
                'x' => explode(';', $report->getCoordinates())[0],
                'y' => explode(';', $report->getCoordinates())[1]
            ];
        }
        $toSend = '[';
        foreach ($json as $id => $obj) {
            $toSend.=json_encode($obj).',';
        }
        $toSend = substr($toSend, 0, -1);
        $toSend.=']';
        return new Response($toSend, 200, ['content-type' => 'application/json']);
    }

    // Route für /api/v1/reports/room/<tract.roomNumber>
    #[Route('/api/v1/reports/room/{tractString}', name: 'get_error_reports_tract', methods: ['GET'])]
    public function reportsToTract(Request $request, UserRepository $userRepository, ErrorReportRepository $errorReportRepository, RoomRepository $roomRepository, string $tractString): Response
    {
//        return new JsonResponse(['debug' => $tractString]);
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        $tract = str_split( $tractString)[0];
        $number = (int) substr($tractString, 1);

        $room = $roomRepository->findOneBy(['tract' => $tract, 'roomNumber' => $number]);

        $reports = $errorReportRepository->findBy(['reportedRoom' => $room]);

        $json = [];
        foreach ($reports as $report) {
            $json[$report->getId()]['category'] = $report->getCategory();
            $json[$report->getId()]['id'] = $report->getId();
            $json[$report->getId()]['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
            $json[$report->getId()]['dateTime'] = $report->getDateTime();
            $json[$report->getId()]['description'] = $report->getMessage();
            $json[$report->getId()]['reportedBy'] = $report->getReportedBy()->getId();
            $json[$report->getId()]['reportedRoom'] = $report->getReportedRoom()->getId();
            $json[$report->getId()]['roomType'] = $report->getReportedRoom()->getRoomType();
            $json[$report->getId()]['position'] = (object)[
                'x' => explode(';', $report->getCoordinates())[0],
                'y' => explode(';', $report->getCoordinates())[1]
            ];
        }

        $toSend = '[';
        foreach ($json as $id => $obj) {
            $toSend.=json_encode($obj).',';
        }
        $toSend = substr($toSend, 0, 1).']';
        return new Response($toSend, 200, ['content-type' => 'application/json']);
    }
}
