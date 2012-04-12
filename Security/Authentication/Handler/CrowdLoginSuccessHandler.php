<?php
namespace AsaAyers\CrowdBundle\Security\Authentication\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Special Success handler for setting the crowd_token cookie
 */
class CrowdLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $httpUtils;
    protected $options;
    private $successHandler;

    /**
     * Constructor
     *
     * @param array                                 $options        The options of the listener
     * @param HttpUtils                             $httputils      Httputils to create the responseurl
     * @param AuthenticationSuccessHandlerInterface $successHandler The real successhandler
     *
     * @return void
     */
    public function __construct(array $options = array(), HttpUtils $httputils, AuthenticationSuccessHandlerInterface $successHandler = null)
    {
        $this->options = $options;
        $this->httpUtils = $httputils;
        $this->successHandler = $successHandler;
    }

    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return Response the response to return
     */
    function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        if (null !== $this->successHandler) {
            $response = $this->successHandler->onAuthenticationSuccess($request, $token);
        } else {
            $response = $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
        }

        if (empty($this->options['cookie_domain'])) {
            $domain = explode('.', $request->server->get('HTTP_HOST'));
            while (count($domain) > 2) {
                array_shift($domain);
            }
            $domain = implode('.', $domain);
        } else {
            $domain = $this->options['cookie_domain'];
        }
        $cookie = new Cookie('crowd.token_key', $token->cookie_token, 0, '/', '.'. $domain);
        $response->headers->setCookie($cookie);
        return $response;
    }

    /**
     * Builds the target URL according to the defined options.
     *
     * @param Request $request
     *
     * @return string
     */
    private function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        if ($targetUrl = $request->get($this->options['target_path_parameter'], null, true)) {
            return $targetUrl;
        }

        $session = $request->getSession();
        if ($targetUrl = $session->get('_security.target_path')) {
            $session->remove('_security.target_path');

            return $targetUrl;
        }

        if ($this->options['use_referer'] && $targetUrl = $request->headers->get('Referer')) {
            return $targetUrl;
        }

        return $this->options['default_target_path'];
    }
}
