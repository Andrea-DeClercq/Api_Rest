<?php

namespace App\Controller;

use App\Entity\Movie;
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
class MovieController extends AbstractController
{
    public function __construct(
        public EntityManagerInterface $em,
        public SerializerInterface $serializer,
        public TagAwareCacheInterface $cachePool,
    )
    {

    }

    #[Route('/welcome', name: 'welcome', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Bienvenue sur l\'API DigitalMovie',
            'path' => 'src/Controller/MovieController.php'
        ]);
    }

    /**
     * Get all movies
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des films",
     *     @OA\JsonContent(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Movies::class))
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
     * @OA\Tag(name="Movies")
     */
    #[Route('/movies', name:"movies", methods: ['GET'])]
    public function getAllMovies(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllMovies-" . $page . "-" . $limit;
        $movieList = $this->cachePool->get($idCache, function (ItemInterface $item) use ($page,$limit){
            $item->tag("moviesCache");
            $item->expiresAfter(900);
            return $this->em->getRepository(Movie::class)->findAllWithPagination($page, $limit);
        });
        if(!$movieList){
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Il est possible qu\'il n\'y ait pas assez de résultat pour être affiché sur cette page'
            ]);
        }
        $context = SerializationContext::create()->setGroups(['getMovies']);
        $jsonSerializer = $this->serializer->serialize($movieList, 'json', $context);
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
     *     @OA\Items(ref=@Model(type=Movies::class))
     *     )
     * )
     * @OA\Tag(name="Movies")
     */
    #[Route('/movies/{id}', name:'movies_details', methods: ['GET'])]
    public function getDetailsMovies(int $id): JsonResponse
    {
        $idCache = 'getMoviesDetails-' . $id;
        $movieDetails = $this->cachePool->get($idCache, function (ItemInterface $item) use($id){
            $item->tag('moviesDetailsCache');
            $item->expiresAfter(900);
            return $this->em->getRepository(Movie::class)->findBy(['id'=> $id]);
        });
        
        if($movieDetails)
        {
            $jsonSerializer = $this->serializer->serialize($movieDetails, 'json');
            return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Pas de film'
        ], Response::HTTP_NOT_FOUND);
    }
}
