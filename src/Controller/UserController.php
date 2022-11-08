<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/api/v1/user', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        try {
            $json = json_decode($request->getContent(), true);
            $user = new User();
            $user
                ->setEmail($json['email'])
                ->setRole($json['role'])
                ->setLastname($json['lastName'])
                ->setFirstname($json['firstName'])
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

    #[Route('/api/v1/user/{userId}', name: 'get_user', methods: ['GET'])]
    public function retrieveUser(Request $request, $userId, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['id' => $userId]);
        if ($user) {
            $json = [];
            $json['id'] = $user->getId();
            $json['email'] = $user->getEmail();
            $json['role'] = $user->getRole();
            $json['lastName'] = $user->getLastname();
            $json['firstName'] = $user->getFirstname();
            $json['password'] = $user->getPassword();
            return new JsonResponse($json, 200);
        }
        return new Response('No user with id '.$userId, 404);
    }

    #[Route('/api/v1/login', name: 'login_user', methods: ['POST'])]
    public function loginUser(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher)
    {
        file_put_contents('tmp.log', get_class($passwordHasher));
        try {
            $json = json_decode($request->getContent(), true);
            $user = $userRepository->findOneBy(['email' => $json['email']]);
            if ($user) {
                if ($passwordHasher->isPasswordValid($user, $json['password'])){
                    return new JsonResponse(['id' => $user->getId()], 200);
                } else {
                    return new JsonResponse(['error' => 'wrong pw'], 200);
                }
            } else {
                return new Response('No user with email '.$json['email'], 404);
            }
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), 400);
        }
    }
}
