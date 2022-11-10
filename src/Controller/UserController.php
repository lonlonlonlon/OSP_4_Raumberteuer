<?php

namespace App\Controller;

use App\Entity\User;
use App\EnumClass;
use App\Manager\EmailManager;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/{e}', name: 'options_wildcard', methods: ['OPTIONS'])]
    #[Route('/{e}/{a}', name: 'options_wildcard1', methods: ['OPTIONS'])]
    #[Route('/{e}/{a}/{b}', name: 'options_wildcard2', methods: ['OPTIONS'])]
    #[Route('/{e}/{a}/{b}/{c}', name: 'options_wildcard3', methods: ['OPTIONS'])]
    #[Route('/{e}/{a}/{b}/{c}/{d}', name: 'options_wildcard4', methods: ['OPTIONS'])]
    public function options(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        return new Response('', 204);
    }

    #[Route('/api/v1/user', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        try {
            $json = json_decode($request->getContent(), true);
            $user = new User();
            $user
                ->setEmail($json['email'])
                ->setRole(4)
                ->setLastname($json['lastName'] ? $json['lastName'] : '')
                ->setFirstname($json['firstName'] ? $json['firstName'] : '')
                ->setPassword($passwordHasher->hashPassword($user, $json['password']))
                ;
            $entityManager->persist($user);
            $entityManager->flush();
            $json['id'] = $user->getId();
        return new Response(json_encode($json), 200);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), 400);
        }
    }

    // TODO: /api/v1/user/password {"oldPassword": "awdawd", "newPassword": "awddwa"}
    #[Route('/api/v1/user/password', name: 'change_password', methods: ['PUT'])]
    public function changePassword(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $inJson = json_decode($request->getContent(), true);
        $oldPwValid = $userPasswordHasher->isPasswordValid($user, $inJson['oldPassword']);
        if (!$oldPwValid) {
            return new JsonResponse(['error' => 'Das alte Passwort ist nicht korrekt.'], 401);
        }
        $user->setPassword($userPasswordHasher->hashPassword($user, $inJson['newPassword']));
        $entityManager->persist($user);
        $entityManager->flush();
        return new JsonResponse([], 204);
    }

    #[Route('/api/v1/user/{userPutId}', name: 'put_user', methods: ['PUT'])]
    public function putUser(Request $request, $userPutId, UserRepository $userRepository, EntityManagerInterface $entityManager, RoomRepository $roomRepository): Response
    {
        // TODO: nochmal kontrollieren ob "role" und "rooms" wirklich im json sind
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        if ($user->getRole() != EnumClass::$ADMIN_ROLE) {
            return new JsonResponse(['error' => 'Sie verfügen nicht über die nötigen Berechtigungen.'], 403);
        }

        try {
            $userObj = $userRepository->findOneBy(['id' => $userPutId]);
            $putJson = json_decode($request->getContent(), true);
            try {
                $rooms = $putJson['rooms'];
            } catch (\Exception $e) {}
            try {
                $role = $putJson['role'];
            } catch (\Exception $e) {}
            if (!empty($role)) {
                $userObj->setRole($role);
                $entityManager->persist($userObj);
                $entityManager->flush();
            }
            if (!empty($rooms)){
                foreach ($rooms as $tractString) {
                    $tract = str_split($tractString)[0];
                    $number = (int)substr($tractString, 1);
                    $room = $roomRepository->findOneBy(['tract' => $tract, 'roomNumber' => $number]);
                    $room->setSupervisor($userObj);
                    $entityManager->persist($room);
                }
                $entityManager->flush();
            }

        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        }

        return new JsonResponse([], 204);
    }

    #[Route('/api/v1/user/{userId}', name: 'get_user', methods: ['GET'])]
    public function retrieveUser(Request $request, $userId, UserRepository $userRepository): Response
    {
        $userIdHeader = $request->headers->get(EnumClass::$USER_HEADER);
        $userIdHeader == $userId ? $isself = true:$isself=false;
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userIdHeader]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        if ($user->getRole() != EnumClass::$ADMIN_ROLE && !$isself) {
            return new JsonResponse(['error' => 'Sie verfügen nicht über die nötigen Berechtigungen.'], 403);
        }
        unset($user);
        $user = $userRepository->findOneBy(['id' => (int)$userId]);
        if ($user) {
            $json = [];
            $json['id'] = $user->getId();
            $json['email'] = $user->getEmail();
            $json['role'] = $user->getRole();
            $json['lastName'] = $user->getLastname();
            $json['firstName'] = $user->getFirstname();
            return new JsonResponse($json, 200);
        }
        return new Response('No user with id '.$userId, 404);
    }

    #[Route('/api/v1/login', name: 'login_user', methods: ['POST'])]
    public function loginUser(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher)
    {
        try {
            $json = json_decode($request->getContent(), true);
            $user = $userRepository->findOneBy(['email' => $json['email']]);
            if ($user) {
                if ($passwordHasher->isPasswordValid($user, $json['password'])){
                    return new JsonResponse(['id' => $user->getId()], 200);
                } else {
                    return new JsonResponse(['error' => 'Das Passwort ist ungültig, bitte geben Sie es erneut ein.', 'id' => -1], 200);
                }
            } else {
                return new JsonResponse(['error' => 'Es existiert kein Benutzer mit der Email-Adresse '.$json['email'], 'id' => -1], 404);
            }
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'id' => -1], 400);
        }
    }

    #[Route('/api/v1/users', name: 'get_user_list', methods: ['GET'])]
    public function getUserList(Request $request, UserRepository $userRepository) {
        $userId = $request->headers->get(EnumClass::$USER_HEADER);
        if (empty($userId)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }
        $user = $userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            return new JsonResponse(['error' => 'Sie sind nicht als gültiger Benutzer angemeldet'], 401);
        }

        if ($user->getRole() != EnumClass::$ADMIN_ROLE) {
            return new JsonResponse(['error' => 'Sie verfügen nicht über die nötigen Berechtigungen.'], 403);
        }

        $users = $userRepository->findAll();
        $json = [];

        foreach ($users as $userObj) {
            $json[$userObj->getId()]['email'] = $userObj->getEmail();
            $json[$userObj->getId()]['role'] = $userObj->getRole();
            $json[$userObj->getId()]['id'] = $userObj->getId();
            $json[$userObj->getId()]['lastName'] = $userObj->getLastname();
            $json[$userObj->getId()]['firstName'] = $userObj->getFirstname();
        }
        $toSend = '[';
        foreach ($json as $id => $obj) {
            $toSend.=json_encode($obj).',';
        }
        $toSend = substr($toSend, 0, -1);
        $toSend.=']';
        return new Response($toSend, 200, ['content-type' => 'application/json']);
    }

    #[Route('/api/v1/user/reset_pw', name: 'pw_reset', methods: ['POST'])]
    public function resetPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher)
    {
        try {
            $json = json_decode($request->getContent(), true);
            $email = $json['email'];
            $user = $userRepository->findOneBy(['email' => $email]);
            if (empty($user)) {
                return new JsonResponse(['error' => 'Es existiert kein Benutzer mit dieser Email Adresse.'], 404);
            }
            $newPw = generateRandomString(30);
            $user->setPassword($userPasswordHasher->hashPassword($user, $newPw));
            $entityManager->persist($user);
            $entityManager->flush();

            // mail mit neuem pw
            $mailManager = new EmailManager();
            $mailManager->sendMail($email, 'Ihr neues Passwort', "Hallo " . $user->getFirstname() . " " . $user->getLastname() . ",\n\nIhr Passwort wurde auf:\n$newPw\nzurückgesetzt. Sie können sich nun anmelden um ein eigenes Passwort zu vergeben.\n\n");
        } catch (\Exception $exception) {
            return new JsonResponse(["error" => $exception->getMessage()], 400);
        }
        return new JsonResponse([], 204);
    }
}

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@%?!?!?!?!?!?@@@@@@@@', ceil($length/strlen($x)) )),1,$length);
}