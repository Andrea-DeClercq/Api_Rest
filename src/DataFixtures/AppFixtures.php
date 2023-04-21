<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Movie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements FixtureGroupInterface
{

    public function __construct(private UserPasswordHasherInterface $hasher)
    {
        
    }
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        for($u = 0; $u < 10; $u++)
        {
            $author = new Author();

            $author->setFirstName($faker->firstName());
            $author->setLastName($faker->lastName());
            $author->setRegion($faker->region());
            $manager->persist($author);

            $listAuthor[] = $author;
        }

        for ($i = 0; $i < 20; $i++)
        {
            $movie = new Movie();
            $movie->setTitle($faker->sentence());
            $movie->setDescription($faker->paragraph());
            $movie->setAuthor($listAuthor[array_rand($listAuthor)]);
            $manager->persist($movie);
        }

        for($u = 0; $u < 10; $u++)
        {
            $user = new User();

            $username = $faker->firstName();

            $user->setUserName($username);
            $password = $this->hasher->hashPassword($user, $username);
            $user->setPassword($password);
            $user->setFirstName($username);
            $user->setLastName($faker->lastName());
            $user->setPhoneNumber($faker->phoneNumber());
            $manager->persist($user);
        }


        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['allFixtures'];
    }
}
