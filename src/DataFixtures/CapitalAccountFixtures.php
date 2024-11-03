<?php

namespace App\DataFixtures;

use App\Entity\CapitalAccount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CapitalAccountFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $capitalAccount = new CapitalAccount();
        $capitalAccount->setIsMain(true);
        $capitalAccount->setEmail('2pb8x8qky8@privaterelay.appleid.com');
        $capitalAccount->setAccountName('Sache');
        $capitalAccount->setAvailableBalance(9990.91);
        $capitalAccount->setAllocatedBalance(0);
        $capitalAccount->setAssignedUsersCount(0);
        $capitalAccount->setRestrictUserAssign(false);
        $capitalAccount->setApiIdentifier('LT');
        $capitalAccount->setCreatedAt(new \DateTime());
        $capitalAccount->setUpdatedAt(new \DateTime());

        $manager->persist($capitalAccount);

        $manager->flush();
    }
}
