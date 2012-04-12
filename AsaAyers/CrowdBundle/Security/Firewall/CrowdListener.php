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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;


class CrowdListener implements ListenerInterface
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
		if (!$request->request->has('_username')
				|| !$request->request->has('_password')) {
			return;
		}

		$token = new CrowdUserToken();
		$token->setUser($request->request->get('_username'));
		$token->remote_addr = $request->server->get('REMOTE_ADDR');
		$token->user_agent = $request->server->get('HTTP_USER_AGENT');
		$token->password = $request->request->get('_password');

		try {
			$returnValue = $this->authenticationManager->authenticate($token);



			if ($returnValue instanceof TokenInterface) {
				$returnValue->setAuthenticated(true);
				$this->securityContext->setToken($returnValue);

				$session = $request->getSession();
				if ($targetUrl = $session->get('_security.target_path')) {
					$session->remove('_security.target_path');

					$response = new RedirectResponse($targetUrl, 302);
				}
				else
				{
					$response = new RedirectResponse('/', 302);
				}
				$domain = explode('.', $request->server->get('HTTP_HOST'));
				while (count($domain) > 2)
				{
					array_shift($domain);
				}
				$cookie = new Cookie('crowd.token_key', $token->cookie_token, 0, '/', '.'.implode('.', $domain));
				$response->headers->setCookie($cookie);
				return $event->setResponse($response);
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

