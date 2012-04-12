<?php

namespace AsaAyers\CrowdBundle\Security\User\Provider;

use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserNameNotFoundException;
use Symfony\Component\Security\Core\User\UnsupportedUserException;

class CrowdProvider implements UserProviderInterface
{
    private $crowd;

    public function __construct(\Services_Atlassian_Crowd $crowd)
    {
        $this->crowd = $crowd;
    }

    /**
     * {@inheritDoc}
     */
    function loadUserByUsername($username)
    {
        $groups = $this->crowd->findGroupMemberships($username);

        if (isset($groups->string))
        {
            // If a user is disabled in crowd the authentication should fail
            // and then findGroupMemberships should fail
            $enabled = true;
            // The password isn't available and shouldn't be needed.
            $password = null;
            $roles = array(
                'ROLE_USER',
            );

            foreach ($groups->string as $group_name)
            {
                $group_name = 'ROLE_'.strtoupper($group_name);
                $group_name = str_replace(array(' ', '-'), '_', $group_name);
                $roles[] = $group_name;
            }

            return new User($username, $password, $roles, $enabled, true, true, true);
        }
        throw new UsernameNotFoundException($username);
    }

    /**
     * {@inheritDoc}
     */
    function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritDoc}
     */
    function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}

