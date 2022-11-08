<?php

namespace App\DataFixtures;

use App\Entity\ErrorReport;
use App\Entity\Role;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
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
                ->setPasswordHash(sha1('test'.'secret'))
                ->setRole($role)
                ->setEmail($mail)
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
                ->setCoordinates('0.21312;0.7345453')
                ->setDateTime(new \DateTime('now'))
                ->setMessage('Anschalter funktioniert nicht')
                ->setReportedBy($betreuer[random_int(0, count($betreuer)-1)])
                ->setReportedRoom($rooms[random_int(0, count($rooms)-1)])
                ->setSerialNumber('AAA-BB-5678-1234')
                ->setState('offen')
            ;
            $manager->persist($error);
        }
        $manager->flush();
    }
}
