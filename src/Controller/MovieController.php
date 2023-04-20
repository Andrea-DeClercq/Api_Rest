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

#[Route('/api', name:'api_')]
class MovieController extends AbstractController
{
    public function __construct(
        public EntityManagerInterface $em,
        public SerializerInterface $serializer,
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
        $movies = $this->em->getRepository(Movie::class)->findAllWithPagination($page, $limit);
        $jsonSerializer = $this->serializer->serialize($movies, 'json');
        return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
    }

    #[Route('/movies/{id}', name:'movies_details', methods: ['GET'])]
    public function getDetailsMovies(int $id): JsonResponse
    {
        $movie = $this->em->getRepository(Movie::class)->findBy(['id'=> $id]);
        if($movie)
        {
            $jsonSerializer = $this->serializer->serialize($movie, 'json');
            return new JsonResponse($jsonSerializer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Pas de film'
        ], Response::HTTP_NOT_FOUND);
    }
}
