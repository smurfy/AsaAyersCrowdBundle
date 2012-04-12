<?php

namespace AsaAyers\CrowdBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use AsaAyers\CrowdBundle\Security\Authentication\Token\CrowdUserToken;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CrowdProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $cacheDir;

    public function __construct(UserProviderInterface $userProvider, $crowd, $remote_address = NULL)
    {
        $this->userProvider = $userProvider;
        $this->remote_address = $remote_address;
        $this->crowd = $crowd;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!is_null($this->remote_address))
        {
            $token->remote_addr = $this->remote_address;
        }
        if (!isset($token->cookie_token))
        {
            try
            {
                $token->cookie_token = $this->crowd->authenticatePrincipal(
                        $token->getUsername(),
                        $token->password,
                        $token->user_agent,
                        $token->remote_addr
                        );
            }
            catch (\Services_Atlassian_Crowd_Exception $e)
            {
                if ($e->getMessage() != $token->getUsername())
                {
                    throw new BadCredentialsException('Bad Credentials', 0, $e);
                }
                throw new UsernameNotFoundException('Invalid username');
            }
        }

        if (isset($token->cookie_token))
        {
            try
            {
                $principal = $this->crowd->findPrincipalByToken($token->cookie_token);
                $user = $this->userProvider->loadUserByUsername($principal->name);
                if ($user)
                {
                    $newToken = new CrowdUserToken($user->getRoles());
                    $newToken->setUser($user);
                    $newToken->cookie_token = $token->cookie_token;
                    return $newToken;
                }
            } catch (\Services_Atlassian_Crowd_Exception $e)
            {
                // An exception is thrown if the token is no longer valid.
            }
        }

        throw new AuthenticationException('The Crowd authentication failed.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof CrowdUserToken;
    }
}

