<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name:'api_')]
class UserController extends AbstractController
{

    public function __construct(
        public EntityManagerInterface $em,
        public SerializerInterface $serializer,
    )
    {

    }

    #[Route('/users', name:"users", methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $users = $this->em->getRepository(User::class)->findAll();
        $jsonSerializer = $this->serializer->serialize($users, 'json', ['groups' => 'userInfo']);
        return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
    }

    #[Route('/users/{id}', name:'users_details', methods: ['GET'])]
    public function getDetailsUsers(int $id): JsonResponse
    {
        $movie = $this->em->getRepository(User::class)->findBy(['id'=> $id]);
        if($movie)
        {
            $jsonSerializer = $this->serializer->serialize($movie, 'json', ['groups' => 'userInfo']);
            return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Pas d\'utilisateur'
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/create/user', name:'create_user', methods:['POST'])]
    public function createNewUser(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {  
        $userName = $request->request->get('username');
        $password = $request->request->get('password');
        $firstName = $request->request->get('firstname');
        $lastName = $request->request->get('lastname');
        $phoneNumber = $request->request->get('phonenumber');

        if(isset($userName) && !empty ($userName) && isset($password) && !empty($password))
        {
            $user = new User();
            $user->setUsername($userName);
            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPhoneNumber($phoneNumber);

            $this->em->persist($user);
            $this->em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'User added to database'
            ]);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Username ou password can\'t be null'
        ]);
    }

    #[Route('/delete/user/{id}', name:'delete_user', methods: ['DELETE'])]
    public function deleteUserFromId(int $id): JsonResponse
    {
        $movie = $this->em->getRepository(User::class)->findBy(['id'=> $id]);
        if($movie)
        {
            $this->em->remove($movie[0]);
            $this->em->flush();
            return new JsonResponse([
                'status' => 'success',
                'message' => 'User deleted'
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Pas d\'utilisateur'
        ], Response::HTTP_NOT_FOUND);
    }
}
