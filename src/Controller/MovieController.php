<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name:'api_')]
class MovieController extends AbstractController
{
    public function __construct(
        public EntityManagerInterface $em
    )
    {
        
    }

    #[Route('/welcome', name: 'welcome')]
    public function index(): Response
    {
        return $this->json([
            'message' => 'Bienvenue sur l\'API DigitalMovie',
            'path' => 'src/Controller/MovieController.php'
        ]);
    }

    #[Route('/movies', name:"movies")]
    public function getMovies(): Response
    {
        $movies = $this->em->getRepository(Movie::class)->findAll();

        return $this->json($movies);
    }
}
