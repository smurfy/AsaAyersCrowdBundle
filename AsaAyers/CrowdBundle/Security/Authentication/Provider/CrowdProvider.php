<?php

namespace AsaAyers\CrowdBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use AsaAyers\CrowdBundle\Security\Authentication\Token\CrowdUserToken;

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
					// something has actually gone wrong
					throw $e;
				}
				// the username/password is wrong. Just fall through.
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
					$new_token = new CrowdUserToken($user->getRoles());
					$new_token->setUser($user);
					return $new_token;
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

