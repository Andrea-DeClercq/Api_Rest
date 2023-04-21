<?php

namespace App\DataFixtures;

use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AuthorFixtures extends Fixture
{
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
        
        $manager->flush();
    }
}
