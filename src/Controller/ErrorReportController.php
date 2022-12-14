<?php

namespace App\Controller;

use App\Entity\ErrorReport;
use App\Manager\EmailManager;
use App\Repository\ErrorReportRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Exception;
use PHPUnit\Util\Json;
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
                $tmp = $errorReportRepository->findAll();
                $reports = [];
                foreach ($tmp as $index => $report) {
                    if (trim($report->getState()) != EnumClass::$STATE_OPEN) {
                        $reports[]=$report;
                    }
                }
                break;
            case (EnumClass::$ADMIN_ROLE):
                $reports = $errorReportRepository->findAll();
                break;
            case (EnumClass::$SIMPLE_USER):
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

        // reports mit status abgeschlossen nicht mit raus geben
        foreach ($reports as $index => $report) {
            if ($report->getState() == EnumClass::$STATE_CLOSED) {
                unset($reports[$index]);
            }
        }
        rsort($reports);

        $json = [];
        foreach ($reports as $report) {
            $json[$report->getId()]['category'] = $report->getCategory();
            $json[$report->getId()]['id'] = $report->getId();
            $json[$report->getId()]['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
            $json[$report->getId()]['dateTime'] = $report->getDateTime();
            $json[$report->getId()]['description'] = $report->getMessage();
            $json[$report->getId()]['status'] = $report->getState();
            $json[$report->getId()]['reportedBy'] = $report->getReportedBy()->getId();
            $json[$report->getId()]['roomType'] = $report->getRoomType();
            $json[$report->getId()]['position'] = (object)[
                'x' => (int)explode(';', $report->getCoordinates())[0],
                'y' => (int)explode(';', $report->getCoordinates())[1]
            ];
        }
        $toSend = '[';
        foreach ($json as $id => $obj) {
            $toSend.=json_encode($obj).',';
        }
        $toSend = substr($toSend, 0, -1);
        $toSend.=']';
        if (empty($reports)) {
            $toSend = '[]';
        }
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

        // reports mit status abgeschlossen nicht mit raus geben
        foreach ($reports as $index => $report) {
            if ($report->getState() == EnumClass::$STATE_CLOSED) {
                unset($reports[$index]);
            }
        }
        rsort($reports);

        $json = [];
        foreach ($reports as $report) {
            $json[$report->getId()]['category'] = $report->getCategory();
            $json[$report->getId()]['id'] = $report->getId();
            $json[$report->getId()]['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
            $json[$report->getId()]['dateTime'] = $report->getDateTime();
            $json[$report->getId()]['description'] = $report->getMessage();
            $json[$report->getId()]['status'] = $report->getState();
            $json[$report->getId()]['reportedBy'] = $report->getReportedBy()->getId();
            $json[$report->getId()]['roomType'] = $report->getRoomType();
            $json[$report->getId()]['position'] = (object)[
                'x' => (int)explode(';', $report->getCoordinates())[0],
                'y' => (int)explode(';', $report->getCoordinates())[1]
            ];
        }

        $toSend = '[';
        foreach ($json as $id => $obj) {
            $toSend.=json_encode($obj).',';
        }
        $toSend = substr($toSend, 0, -1).']';
        if (empty($reports)) {
            $toSend = '[]';
        }
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
        $json['status'] = $report->getState();
        $json['id'] = $report->getId();
        $json['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
        $json['dateTime'] = $report->getDateTime();
        $json['description'] = $report->getMessage();
        $json['reportedBy'] = $report->getReportedBy()->getId();
        $json['roomType'] = $report->getRoomType();
        $json['position'] = (object)[
            'x' => (int)explode(';', $report->getCoordinates())[0],
            'y' => (int)explode(';', $report->getCoordinates())[1]
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
            $reportedBy = $userRepository->findOneBy(['id' => $userId]);
            $tract = str_split($json['room'])[0];
            $number = (int) substr($json['room'], 1)+1-1;

            $reportedRoom = $roomRepository->findOneBy(['tract' => $tract, 'roomNumber' => $number]);
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
                ->setRoomType($json['roomType'])
                ->setDateTime(new \DateTime('now'))
                ->setMessage($json['description'])
                ->setReportedBy($reportedBy)
                ->setReportedRoom($reportedRoom)
                ->setSerialNumber($serialNum);

            $entityManager->persist($report);
            $entityManager->flush();

            // mail an betreuer
            $mailManager = new EmailManager();
            if ($reportedRoom) {
                $betreuer = $reportedRoom->getSupervisor();
                $roomName = $reportedRoom->getTract().$reportedRoom->getRoomNumber();
                $mailManager->sendMail(
                    $betreuer->getEmail(),
                    'Neuer Defekt in '.$roomName,
                    "Hallo ".$betreuer->getFirstname().' '.$betreuer->getLastname().",\n\nIn Ihrem Raum $roomName wurde ein neuer Fehler von ".$reportedBy->getFirstname().' '.$reportedBy->getLastname()." erfasst.\nDie Fehlerbeschreibung:\n".$report->getMessage()."\n\nFür weitere Informationen loggen Sie sich bitte in das Raumbetreuer Tool ein."
                );
            }
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        }
        return new JsonResponse([], 204);
    }

    #[Route('/api/v1/report', name: 'put_report', methods: ['PUT'])]
    public function putReport(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, RoomRepository $roomRepository, ErrorReportRepository $errorReportRepository)
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
            $id = $json['id'];
            $report = $errorReportRepository->findOneBy(['id' => $id]);
            if (empty($report)) {
                return new JsonResponse(['error' => 'Ein Fehlerbericht mit der Id '.$id.' existiert nicht.'], 404);
            }
            $reportedBy = $user;
            $tract = str_split($json['room'])[0];
            $number = (int) substr($json['room'], 1)+1-1;

            $reportedRoom = $roomRepository->findOneBy(['tract' => $tract, 'roomNumber' => $number]);
            try {
                $coord = $json['position']['x'] . ';' . $json['position']['y'];
            } catch (\Exception $e) {$coord='';}
            try {
                $serialNum = $json['serialNumber'];
            } catch (\Exception $e) {$serialNum='';}
            try {
                $description = $json['description'];
            } catch (\Exception $e) {$description='';}
            try {
                $category = $json['category'];
            } catch (\Exception $e) {$category='';}
            try {
                $state = $json['status'];
            } catch (\Exception $e) {$state='';}
            $report
                ->setState($state)
                ->setCategory($category)
                ->setCoordinates($coord)
                ->setMessage($description)
                ->setReportedBy($reportedBy)
                ->setReportedRoom($reportedRoom)
                ->setSerialNumber($serialNum);

            $entityManager->persist($report);
            $entityManager->flush();

            if ($report->getState() == EnumClass::$STATE_VERIFIED) {
                $pcServiceStationUsers = $userRepository->findBy(['role' => EnumClass::$WERKSTATT_ROLE]);
                $mailManager = new EmailManager();
                foreach ($pcServiceStationUsers as $user){
                    $roomName = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
                    $mailManager->sendMail($user->getEmail(),
                        'Neuer Defekt GSO Köln '.$roomName,
                        "Hallo ".$user->getFirstname().' '.$user->getLastname().",\n\nEs wurde ein neuer Defekt in Raum $roomName erfasst.\nDie Defektbeschreibung:\n".$report->getMessage()."\n\nBitte loggen sie sich im Raumbetreuer Tool ein um weitere Informationen zu erhalten.\n\n");
                }
            }
            return new JsonResponse([], 204);
        } catch (Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        }
    }

    #[Route('/api/v1/history', name: 'record_history', methods: ['GET'])]
    public function getHistoricalReports(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, RoomRepository $roomRepository, ErrorReportRepository $errorReportRepository)
    {
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        if ($user->getRole() != EnumClass::$ADMIN_ROLE && $user->getRole() != EnumClass::$BETREUER_ROLE && $user->getRole() != EnumClass::$WERKSTATT_ROLE) {
            return new JsonResponse(['error' => 'Sie verfügen nicht über die nötigen Berechtigungen.'], 403);
        }

        $tmp = $errorReportRepository->findAll();
        $reports = [];
        // reports mit status abgeschlossen nicht mit raus geben
        foreach ($tmp as $index => $report) {
            if ($report->getState() == EnumClass::$STATE_CLOSED) {
                $reports[] = $report;
            }
        }
        rsort($reports);

        $json = [];
        foreach ($reports as $report) {
            $json[$report->getId()]['category'] = $report->getCategory();
            $json[$report->getId()]['id'] = $report->getId();
            $json[$report->getId()]['room'] = $report->getReportedRoom()->getTract().$report->getReportedRoom()->getRoomNumber();
            $json[$report->getId()]['dateTime'] = $report->getDateTime();
            $json[$report->getId()]['description'] = $report->getMessage();
            $json[$report->getId()]['status'] = $report->getState();
            $json[$report->getId()]['reportedBy'] = $report->getReportedBy()->getId();
            $json[$report->getId()]['roomType'] = $report->getRoomType();
            $json[$report->getId()]['position'] = (object)[
                'x' => (int)explode(';', $report->getCoordinates())[0],
                'y' => (int)explode(';', $report->getCoordinates())[1]
            ];
        }

        $toSend = '[';
        foreach ($json as $id => $obj) {
            $toSend.=json_encode($obj).',';
        }
        $toSend = substr($toSend, 0, -1).']';
        if (empty($reports)) {
            $toSend = '[]';
        }
        return new Response($toSend, 200, ['content-type' => 'application/json']);
    }
}
