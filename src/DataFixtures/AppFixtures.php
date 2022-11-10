<?php

namespace App\DataFixtures;

use App\Entity\ErrorReport;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $firstnames = [
            "max",
            "karl",
            "bernd",
            "tim",
            "jakob",
            "lisa",
            "marie",
            "sophie",
            "peter",
            "arnold",
        ];

        $lastnames = [
            "mustermann",
            "Schlosser",
            "töpfer",
            "wischnewski",
            "schröder",
            "magd",
            "sonnenbaum",
            "müller",
            "schlossbroch",
            "wiegard",
        ];

        $roles = [
            'admin' => 0,
            'lehrer' => 1,
            'betreuer' => 2,
            'werkstatt' => 3
            ];

        $roomTypes = [
            0 => "pc_circle",
            1 => "pc_sixtable",
            2 => "omnibus",
            3 => "u_big",
            4 => "u_small"
        ];

        $roleData = [2,2,2,1,1,1,3,3,0,0];
        $betreuer = [];
        foreach ($firstnames as $index => $firstname) {
            $lastname = $lastnames[$index];
            $mail = $firstname.'.'.$lastname.'@example.com';
            $role = $roleData[$index];
            $user = new User();
            $user
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setRole($role)
                ->setEmail($mail)
                ->setPassword($this->passwordHasher->hashPassword($user, 'test'))
            ;
            $manager->persist($user);
            if ($role == 2) {
                $betreuer[] = $user;
            }
        }
        $manager->flush();

        $rooms = [];
        for ($i=0;$i<20;$i++) {
            $room = new Room();
            $room
                ->setRoomNumber($i)
                ->setTract('C')
                ->setSupervisor($betreuer[random_int(0, count($betreuer)-1)]);
            $manager->persist($room);
            $rooms[] = $room;
        }
        $manager->flush();

        for ($i=0;$i<20;$i++) {
            $error = new ErrorReport();
            $error
                ->setCategory('hardware')
                ->setCoordinates(random_int(0,500).';'.random_int(0,500))
                ->setDateTime(new \DateTime('now'))
                ->setRoomType(random_int(0, 4))
                ->setMessage('Anschalter funktioniert nicht')
                ->setReportedBy($betreuer[random_int(0, count($betreuer)-1)])
                ->setReportedRoom($rooms[random_int(0, count($rooms)-1)])
                ->setSerialNumber('AAA-BB-5678-1234')
                ->setState(random_int(0,1) ? 'offen': 'geschlossen')
            ;
            $manager->persist($error);
        }
        $manager->flush();
    }
}
