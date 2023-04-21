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
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api', name:'api_')]
class UserController extends AbstractController
{

    public function __construct(
        public EntityManagerInterface $em,
        public SerializerInterface $serializer,
        public TagAwareCacheInterface $cachePool,
    )
    {

    }

    /**
     * Get all users
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des users",
     *     @OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="Le numéro de la page voulu",
     *      @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Le nombre de résultat par page",
     *      @OA\Schema(type="int")
     * )
     * @OA\Tag(name="User")
     */
    #[Route('/users', name:"users", methods: ['GET'])]
    public function getAllUsers(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = 'getAllUsers-' . $page . '-' . $limit;
        $userList = $this->cachePool->get($idCache, function (ItemInterface $item) use ($page,$limit){
            $item->tag('usersCache');
            $item->expiresAfter(900);
            return $this->em->getRepository(User::class)->findAllWithPagination($page, $limit);
        });

        if(!$userList){
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Il est possible qu\'il n\'y ait pas assez de résultat pour être affiché sur cette page'
            ]);
        }

        $jsonSerializer = $this->serializer->serialize($userList, 'json', ['groups' => 'userInfo']);
        return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
    }


    /**
     * Get Movies details
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails d'un film",
     *     @OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Tag(name="User")
     */
    #[Route('/users/{id}', name:'users_details', methods: ['GET'])]
    public function getDetailsUsers(int $id): JsonResponse
    {

        $idCache = 'getUsersDetails-' . $id;
        $userDetails = $this->cachePool->get($idCache, function(ItemInterface $item) use ($id){
            $item->tag('usersDetailsCache');
            $item->expiresAfter(900);
            return $this->em->getRepository(User::class)->findBy(['id'=> $id]);
        });

        if($userDetails)
        {
            $jsonSerializer = $this->serializer->serialize($userDetails, 'json', ['groups' => 'userInfo']);
            return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Pas d\'utilisateur'
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * @OA\Tag(name="User")
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @return JsonResponse
     */
    #[Route('/users/create', name:'create_user', methods:['POST'])]
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

    /**
     * @OA\Tag(name="User")
     */
    #[Route('/users/{id}/delete', name:'delete_user', methods: ['DELETE'])]
    public function deleteUserFromId(int $id): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->findBy(['id'=> $id]);
        if($user)
        {
            $this->cachePool->invalidateTags(['usersCache', 'usersDetailsCache']);
            $this->em->remove($user[0]);
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
