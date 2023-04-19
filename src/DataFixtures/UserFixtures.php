<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
        
    }
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

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
}
