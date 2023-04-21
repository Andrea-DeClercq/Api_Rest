<?php

namespace App\Controller;

use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api', name:'api_')]
class AuthorController extends AbstractController
{

    public function __construct(
        public EntityManagerInterface $em,
        public SerializerInterface $serializer,
        public TagAwareCacheInterface $cachePool,
    )
    {

    }

    /**
     * Get all authors
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des films",
     *     @OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Author::class))
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
     * @OA\Tag(name="Authors")
     */
    #[Route('/authors', name:"authors", methods: ['GET'])]
    public function getAllMovies(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllAuthors-" . $page . "-" . $limit;
        $authorsList = $this->cachePool->get($idCache, function (ItemInterface $item) use ($page,$limit){
            $item->tag("authorsCache");
            $item->expiresAfter(900);
            return $this->em->getRepository(Author::class)->findAllWithPagination($page, $limit);
        });
        if(!$authorsList){
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Il est possible qu\'il n\'y ait pas assez de résultat pour être affiché sur cette page'
            ]);
        }
        $context = SerializationContext::create()->setGroups(['authorDetails']);
        $jsonSerializer = $this->serializer->serialize($authorsList, 'json', $context);
        return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
    }

    /**
     * Get Author Details
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails d'un film",
     *     @OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Author::class))
     *     )
     * )
     * @OA\Tag(name="Authors")
     */
    #[Route('/authors/{id}', name:'authors_details', methods: ['GET'])]
    public function getDetailsAuthors(int $id): JsonResponse
    {

        $idCache = 'getAuthorsDetails-' . $id;
        $userDetails = $this->cachePool->get($idCache, function(ItemInterface $item) use ($id){
            $item->tag('authorsDetailsCache');
            $item->expiresAfter(900);
            return $this->em->getRepository(Author::class)->findBy(['id'=> $id]);
        });

        if($userDetails)
        {
            $jsonSerializer = $this->serializer->serialize($userDetails, 'json', ['groups' => 'authorDetails']);
            return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Pas d\'auteurs'
        ], Response::HTTP_NOT_FOUND);
    }
}
