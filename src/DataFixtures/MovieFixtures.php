<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MovieFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = \Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 20; $i++)
        {
            $movie = new Movie();
            $movie->setTitle($faker->sentence());
            $movie->setAuthor($faker->name());
            $movie->setDescription($faker->paragraph());
            $manager->persist($movie);
        }

        $manager->flush();
    }
}
