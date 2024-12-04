<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\CapitalAccount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CapitalAccountFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $capitalAccount = (new CapitalAccount())
            ->setIsMain(true)
            ->setEmail('2pb8x8qky8@privaterelay.appleid.com')
            ->setAccountName('Sache')
            ->setAvailableBalance(9990.91)
            ->setAllocatedBalance(0)
            ->setAssignedUsersCount(0)
            ->setRestrictUserAssign(false)
            ->setApiIdentifier('LT')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $manager->persist($capitalAccount);
        $manager->flush();
    }
}
