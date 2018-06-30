<?php
namespace App\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;
use Tk\Event\ControllerEvent;
use Tk\Event\GetResponseEvent;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{

    /**
     * do any auth init setup
     *
     * @param GetResponseEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Exception
     */
    public function onRequest(GetResponseEvent $event)
    {
        // if a user is in the session add them to the global config
        // Only the identity details should be in the auth session not the full user object, to save space and be secure.
        $config = \App\Config::getInstance();
        $auth = $config->getAuth();
        $user = null;                       // public user
        if ($auth->getIdentity()) {         // Check if user is logged in
            $user = \App\Db\UserMap::create()->findByUsername($auth->getIdentity());
            $config->setUser($user);
        }

        // Get page access permission from route params (see config/routes.php)
        $role = $event->getRequest()->getAttribute('role');

        // no role means page is publicly accessible
        if (!$role || empty($role)) return;
        if ($user) {
            if (!$user->hasRole($role)) {
                // Could redirect to a authentication error page.
                \Tk\Alert::addWarning('You do not have access to the requested page.');
                \Tk\Uri::create($user->getHomeUrl())->redirect();
            }
        } else {
            \Tk\Uri::create('/login.html')->redirect();
        }
    }


    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $config = \App\Config::getInstance();
        $auth = $config->getAuth();

        $result = null;
        if (!$event->getAdapter()) {
            $adapterList = $config->get('system.auth.adapters');
            foreach ($adapterList as $name => $class) {
                $event->setAdapter($config->getAuthAdapter($class, $event->all()));
                if (!$event->getAdapter()) continue;
                $result = $auth->authenticate($event->getAdapter());
                $event->setResult($result);
                if ($result && $result->isValid()) {
                    break;
                }
            }
        }

        if (!$result) {
            throw new \Tk\Auth\Exception('Invalid username or password');
        }
        if (!$result->isValid()) {
            return;
        }
        
        $user = \App\Db\UserMap::create()->findByUsername($result->getIdentity());
        if (!$user) {
            throw new \Tk\Auth\Exception('User not found: Contact Your Administrator');
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        $result = $event->getResult();
        if (!$result) {
            throw new \Tk\Auth\Exception('Invalid login credentials');
        }
        if (!$result->isValid()) {
            return;
        }

        /* @var \App\Db\User $user */
        $user = \App\Db\UserMap::create()->findByUsername($result->getIdentity());
        if (!$user) {
            throw new \Tk\Auth\Exception('Invalid user login credentials');
        }
        if (!$user->active) {
            throw new \Tk\Auth\Exception('Inactive account, please contact your administrator.');
        }

        if($user && $event->getRedirect() == null) {
            $event->setRedirect($user->getHomeUrl());
        }

        // Update the user record.
        $user->lastLogin = \Tk\Date::create();
        $user->save();

    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        $config = \App\Config::getInstance();
        $auth = $config->getAuth();
        $url = $event->getRedirect();
        if (!$url) {
            $url = \Tk\Uri::create('/index.html');
            $event->setRedirect($url);
        }

        $auth->clearIdentity();
        $config->getSession()->destroy();
    }


    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRegister(\Tk\Event\Event $event)
    {
        /** @var \App\Db\User $user */
        $user = $event->get('user');
        $config = \App\Config::getInstance();

        $url = \Tk\Uri::create('/register.html')->set('h', $user->hash);

        $message = $config->createMessage('account.activated');
        $message->setSubject('Account Registration.');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('activate-url', $url->toString());
        \App\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRegisterConfirm(\Tk\Event\Event $event)
    {
        /** @var \App\Db\User $user */
        $user = $event->get('user');
        $config = \App\Config::getInstance();

        // Send an email to confirm account active
        $url = \Tk\Uri::create('/login.html');

        $message = $config->createMessage('account.activated');
        $message->setSubject('Account Activation.');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('login-url', $url->toString());
        \App\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRecover(\Tk\Event\Event $event)
    {
        /** @var \App\Db\User $user */
        $user = $event->get('user');
        $pass = $event->get('password');
        $config = \App\Config::getInstance();

        $url = \Tk\Uri::create('/login.html');

        $message = $config->createMessage('account.activated');
        $message->setSubject('Password Recovery');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('password', $pass);
        $message->set('login-url', $url->toString());
        \App\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onRequest',
            AuthEvents::LOGIN => 'onLogin',
            AuthEvents::LOGIN_SUCCESS => 'onLoginSuccess',
            AuthEvents::LOGOUT => 'onLogout',
            AuthEvents::REGISTER => 'onRegister',
            AuthEvents::REGISTER_CONFIRM => 'onRegisterConfirm',
            AuthEvents::RECOVER => 'onRecover'
        );
    }


}