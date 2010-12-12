<?php

namespace Bundle\DoctrineUserBundle\Tests\Command;

use Bundle\DoctrineUserBundle\Test\WebTestCase;
use Bundle\DoctrineUserBundle\Model\User;
use Bundle\DoctrineUserBundle\Command\ActivateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\ApplicationTester;

class ActivateUserCommandTest extends WebTestCase
{
    public function testUserActivation()
    {
        $kernel = $this->createKernel();
        $command = new ActivateUserCommand();
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $username = 'test_username';
        $password = 'test_password';
        $email    = 'test_email@email.org';

        $userRepo = $this->getService('doctrine_user.repository.user');
        $userClass = $userRepo->getObjectClass();

        $user = $userRepo->createObjectInstance();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setEnabled(false);

        $userRepo->getObjectManager()->persist($user);
        $userRepo->getObjectManager()->flush();

        $this->assertFalse($user->isEnabled());

        $tester->run(array(
            'command'  => $command->getFullName(),
            'username' => $username,
        ), array('interactive' => false, 'decorated' => false, 'verbosity' => Output::VERBOSITY_VERBOSE));

        $userRepo = $this->getService('doctrine_user.repository.user');
        $userRepo->getObjectManager()->clear();
        $user = $userRepo->findOneByUsername($username);

        $this->assertTrue($user instanceof User);
        $this->assertTrue($user->isEnabled());

        $userRepo->getObjectManager()->remove($user);
        $userRepo->getObjectManager()->flush();
    }

    public function tearDown()
    {
        $repo = $this->getService('doctrine_user.repository.user');
        $om = $repo->getObjectManager();
        if ($user = $repo->findOneByUsername('test_username')) {
            $om->remove($user);
        }
        $om->flush();
    }
}
