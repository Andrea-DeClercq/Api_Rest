<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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

    #[Route('/movies', name:"movies", methods: ['GET'])]
    public function getAllMovies(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllMovies-" . $page . "-" . $limit;
        $movieList = $this->cachePool->get($idCache, function (ItemInterface $item) use ($page,$limit){
            $item->tag("moviesCache");
            return $this->em->getRepository(Movie::class)->findAllWithPagination($page, $limit);
        });

        // $movies = $this->em->getRepository(Movie::class)->findAllWithPagination($page, $limit);

        if(!$movieList){
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Il est possible qu\'il n\'y ait pas assez de résultat pour être affiché sur cette page'
            ]);
        }

        $jsonSerializer = $this->serializer->serialize($movieList, 'json');
        return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
    }

    #[Route('/movies/{id}', name:'movies_details', methods: ['GET'])]
    public function getDetailsMovies(int $id): JsonResponse
    {

        $idCache = 'getMoviesDetails-' . $id;
        $movieDetails = $this->cachePool->get($idCache, function (ItemInterface $item) use($id){
            $item->tag('moviesDetailsCache');
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
