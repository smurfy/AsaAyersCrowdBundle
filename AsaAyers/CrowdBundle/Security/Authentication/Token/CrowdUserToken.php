<?php

namespace AsaAyers\CrowdBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class CrowdUserToken extends AbstractToken
{
    public $cookie_token;
    public $remote_addr;
    public $password;
    public $user_agent;

    public function __construct(array $roles = array())
    {
        parent::__construct($roles);
    }

    public function setUser($user)
    {
        parent::setUser($user);
    }

    public function getCredentials()
    {
        return '';
    }
}

