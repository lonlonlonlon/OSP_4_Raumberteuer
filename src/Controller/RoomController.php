<?php

namespace App\Controller;

use App\EnumClass;
use App\Repository\ErrorReportRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{
    #[Route('/api/v1/rooms', name: 'rooms', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, RoomRepository $roomRepository, ErrorReportRepository $errorReportRepository): Response
    {
        $rooms = $roomRepository->findAll();
        $json = [];
        foreach ($rooms as $room) {
            $json[] = $room->getTract().$room->getRoomNumber();
        }
//        $toSend = str_replace(['{', '}'], ['[', ']'], json_encode($json));
        return new Response(json_encode($json, JSON_OBJECT_AS_ARRAY), 200);
    }
}
