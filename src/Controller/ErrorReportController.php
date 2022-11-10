<?php

namespace App\Controller;

use App\Entity\ErrorReport;
use App\Repository\ErrorReportRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        $tract = str_split( $tractString)[0];
        $number = (int) substr($tractString, 1)+1-1;

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
        $toSend = substr($toSend, 0, -1).']';
        return new Response($toSend, 200, ['content-type' => 'application/json']);
    }

    #[Route('/api/v1/report/{reportId}', name: 'get_one_report', methods: ['GET'])]
    public function getReport(Request $request, ErrorReportRepository $errorReportRepository, UserRepository $userRepository, $reportId)
    {
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        $report = $errorReportRepository->findOneBy(['id' => $reportId]);

        $json = [];
        $json['category'] = $report->getCategory();
        $json['id'] = $report->getId();
        $json['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
        $json['dateTime'] = $report->getDateTime();
        $json['description'] = $report->getMessage();
        $json['reportedBy'] = $report->getReportedBy()->getId();
        $json['reportedRoom'] = $report->getReportedRoom()->getId();
        $json['roomType'] = $report->getReportedRoom()->getRoomType();
        $json['position'] = (object)[
            'x' => explode(';', $report->getCoordinates())[0],
            'y' => explode(';', $report->getCoordinates())[1]
        ];
        return new JsonResponse($json, 200);
    }

    #[Route('/api/v1/report', name: 'post_report', methods: ['POST'])]
    public function postReport(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, RoomRepository $roomRepository)
    {
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        try {
            $json = json_decode($request->getContent(), true);

            $report = new ErrorReport();
            $reportedBy = $userRepository->findOneBy(['id' => (int)$json['reportedBy']]);
            $reportedRoom = $roomRepository->findOneBy(['id' => (int)$json['reportedRoom']]);
            try {
                $coord = $json['position']['x'] . ';' . $json['position']['y'];
            } catch (\Exception $e) {$coord='';}
            try {
                $serialNum = $json['serialNumber'];
            } catch (\Exception $e) {$serialNum='';}
            $report
                ->setState($json['status'])
                ->setCategory($json['category'])
                ->setCoordinates($coord)
                ->setDateTime(new \DateTime('now'))
                ->setMessage($json['description'])
                ->setReportedBy($reportedBy)
                ->setReportedRoom($reportedRoom)
                ->setSerialNumber($serialNum);

            $entityManager->persist($report);
            $entityManager->flush();
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        }
        return new JsonResponse([], 204);
    }
}
