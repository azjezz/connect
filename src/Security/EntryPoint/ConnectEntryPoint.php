<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security\EntryPoint;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCorp\Connect\OAuthConsumer;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConnectEntryPoint implements AuthenticationEntryPointInterface
{
    private $oauthConsumer;
    private $httpUtils;
    private $oauthCallback;

    public function __construct(OAuthConsumer $oauthConsumer, HttpUtils $httpUtils, $oauthCallback = 'login_check')
    {
        $this->oauthConsumer = $oauthConsumer;
        $this->httpUtils = $httpUtils;
        $this->oauthCallback = $oauthCallback;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $session = $request->getSession();
        $session->start();

        if ($request->query->has('target')) {
            $target = $request->query->get('target');
            $parsed = parse_url($target);
            if (!isset($parsed['host']) || $parsed['host'] === $request->getHttpHost()) {
                $session->getFlashBag()->set('symfony_connect.oauth.target_path', $target);
            }
        }

        $session->getFlashBag()->set('symfony_connect.oauth.state', $state = md5(uniqid(mt_rand(), true)));

        return new RedirectResponse($this->oauthConsumer->getAuthorizationUri($this->httpUtils->generateUri($request, $this->oauthCallback), $state));
    }
}
