<?php

namespace AsaAyers\CrowdBundle\Security\Firewall;

use AsaAyers\CrowdBundle\Security\Authentication\Token\CrowdUserToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


class SsoListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;

    /**
     * This is also declared private in the parent. Both are the same object.
     */
    private $httpUtils;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        // For some reason it has to be accessed as crowd_token_key but
        // is set as crowd.token_key
        if (!$request->cookies->has('crowd_token_key')) {
            return;
        }

        $token = new CrowdUserToken();
        $token->cookie_token = $request->cookies->get('crowd_token_key');
        $token->remote_addr = $request->server->get('REMOTE_ADDR');

        try {
            $returnValue = $this->authenticationManager->authenticate($token);

            if ($returnValue instanceof TokenInterface) {
                $returnValue->setAuthenticated(true);
                return $this->securityContext->setToken($returnValue);
            } else if ($returnValue instanceof Response) {
                return $event->setResponse($returnValue);
            }

        } catch (AuthenticationException $e) {
            // the example showed catching the exception, but i'm
            // not sure what to do with it yet.
            throw $e;
        }

        $response = new Response();
        $response->setStatusCode(403);
        $event->setResponse($response);

    }
}

