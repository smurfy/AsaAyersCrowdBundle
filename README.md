Provides Atlassian Crowd authorisation AsaAyersCrowdBundle

Features
========
- Standalone SSO support
- Form Login support

Authors
=======

- Original Author AsaAyers (https://bitbucket.org/AsaAyers/crowdbundle/)
- Heavily modified and pushed to github by smurfy

Installation
=============

Add AsaAyersCrowdBundle to your vendor/bundles/ dir
---------------------------------------------------
Using the vendors script

Add the following lines in your ``deps`` file

    [AsaAyersCrowdBundle]
        git=git://github.com/smurfy/AsaAyersCrowdBundle.git
        target=bundles/AsaAyers/CrowdBundle

    [AtlassianServicesCrowd]
        git=git://github.com/smurfy/AtlassianServicesCrowd.git
        target=Atlassian

Run the vendors script

    ./bin/vendors install

Add the AsaAyers namespace to your autoloader
---------------------------------------------

    // app/autoload.php
    $loader->registerNamespaces(array(
        'AsaAyers'         => __DIR__.'/../vendor/bundles',
        // your other namespaces
    );

    $loader->registerPrefixes(array(
        'Services_Atlassian' => __DIR__.'/../vendor/Atlassian/lib',
        //Other prfixes
    ));

    // on the bottom of autoload.php For Atlassian Lib include path
    set_include_path(get_include_path() . ':' . __DIR__ . '/../vendor/Atlassian/lib');

Add AsaAyersCrowdBundle to your application kernel
--------------------------------------------------

    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new AsaAyers\CrowdBundle\AsaAyersCrowdBundle(),
            // ...
        );
    }

Configuration
=============

Configure parameters in config.yml (or in the parameters.ini)
-------------------------------------------------------------

    parameters:
        crowd_application_user: username
        crowd_application_password: password
        crowd_wsdl: https://yourdomain.com/crowd/services/SecurityServer?wsdl

Configure your Firewalls
-------------------------

    security:
        factories:
            - "%kernel.root_dir%/../vendor/bundles/AsaAyers/CrowdBundle/Resources/config/security_factories.xml"

        providers:
            crowd: ~
            # All of a user's Crowd groups will become ROLE_${group_name} with spaces and dashes converted to underscores.
            # crowd-administorators becomes ROLE_CROWD_ADMINISTRATORS
        firewalls:
            main:
                # You can use sso standalone, but the crowd login itself also needs crowd_sso enabled
                crowd_sso: true
                crowd:
                    # You can use here the same as of form_login
                    cookie_domain: yourdomain.com
                logout:
                    delete_cookies:
                        crowd.token_key: { path: /, domain: yourdomain.com }

Use AsaAyersCrowdBundle in combination with FOSUserBundle
=========================================================

This example shows you how you can use AsaAyersCrowdBundle with FOSUserBundle.
The users roles will be merged with the already existing roles from the crowd.
If the user does not exist in the FOSUserBundle Database it will be created.

Create a new UserProvider
-------------------------

    namespace Acme\MyBundle\Security\User\Provider;

    use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
    use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
    use Symfony\Component\Security\Core\User\UserProviderInterface;
    use Symfony\Component\Security\Core\User\UserInterface;

    class CrowdUserProvider implements UserProviderInterface
    {
        protected $crowd;
        protected $userManager;

        /**
         * Cosntructor
         *
         * @param Services_Atlassian_Crowd $crowd       The Crowd
         * @param mixed                    $userManager The Fos UserManager
         *
         * @return void
         */
        public function __construct(\Services_Atlassian_Crowd $crowd, $userManager)
        {
            $this->crowd = $crowd;
            $this->userManager = $userManager;
        }

        /**
         * {@inheritDoc}
         */
        public function supportsClass($class)
        {
            return $this->userManager->supportsClass($class);
        }

        /**
         * Loads the user from the crowd, but other stuff from db over userbundle
         *
         * @param string $username The username
         *
         * @return User
         */
        public function loadUserByUsername($username)
        {
            $groups = $this->crowd->findGroupMemberships($username);

            if (isset($groups->string))
            {
                $user = $this->userManager->findUserByUsername($username);
                if (empty($user)) {
                    $user = $this->userManager->createUser();
                    $user->setEnabled(true);
                    $user->setUsername($username);
                    $user->setPassword('');
                    $user->setEmail($username);
                }

                foreach ($groups->string as $group_name)
                {
                    $group_name = 'ROLE_'.strtoupper($group_name);
                    $group_name = str_replace(array(' ', '-'), '_', $group_name);
                    $user->addRole($group_name);
                }
                $this->userManager->updateUser($user);
                return $user;
            }
            throw new UsernameNotFoundException($username);
        }

        /**
         * {@inheritDoc}
         */
        function refreshUser(UserInterface $user)
        {
            return $this->loadUserByUsername($user->getUsername());
        }
    }

Configure your Services
------------------------

    services:
        my.crowd.user:
            class: Acme\MyBundle\Security\User\Provider\CrowdUserProvider
            arguments:
                crowd: "@crowd"
                userManager: "@fos_user.user_manager"

Configure your Firewalls
-------------------------

    security:
        factories:
            - "%kernel.root_dir%/../vendor/bundles/AsaAyers/CrowdBundle/Resources/config/security_factories.xml"

        providers:
            fos_userbundle:
                id: my.crowd.user
        firewalls:
            main:
                # You can use sso standalone, but the crowd login itself also needs crowd_sso enabled
                crowd_sso: true
                crowd:
                    # You can use here the same as of form_login
                    provider: fos_userbundle
                    cookie_domain: yourdomain.com
                logout:
                    delete_cookies:
                        crowd.token_key: { path: /, domain: yourdomain.com }
