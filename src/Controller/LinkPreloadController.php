<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\WebLink\Link;

class LinkPreloadController extends AbstractController
{
    private const PRELOAD_METHODS = [
        'add_link',
        'send_early_hints',
        'manual_header',
    ];

    #[Route('/', name: 'link_preload')]
    public function __invoke(Request $request): Response
    {
        $method = $request->query->get('method');
        if (!is_string($method) && in_array($method, self::PRELOAD_METHODS)){
            $method = null;
        }

        // directory and handled by the web server
        $absoluteUrl = $request->query->getBoolean('absolute');
        $encodedUrl = false;
        $nopush = false;
        $type = false;

        $url = '/styles.css';
        if ($absoluteUrl) {
            $url =  rtrim($request->getSchemeAndHttpHost()).$url;
        }
        if ($encodedUrl) {
            $url = HeaderUtils::quote($url);
        }

        // Link
        $link = (new Link('preload', $url))->withAttribute('as', 'style');
        if ($nopush) {
            $link = $link->withAttribute('nopush', true);
        }
        if ($type) {
            $link = $link->withAttribute('type', "text/css");
        }

        // Method 1: addLink
        if ('add_link' === $method) {
            $this->addLink($request, $link);

            return  $this->render('base.html.twig');
        }

        // Method 2: sendEarlyHints
        if ('send_early_hints' === $method) {
            $response = $this->sendEarlyHints([$link]);

            return $this->render('base.html.twig', [], $response);
        }

        // Method 3: manual link (no WebLink component)
        if ('manual_header' === $method) {
            $response = new Response('', 200, [
                'Link' => sprintf('<%s>; rel=preload; %s', $url, HeaderUtils::toString($link->getAttributes(), '; ')),
            ]);

            return $this->render('base.html.twig', [], $response);
        }

        // Default: no link
        return $this->render('base.html.twig', []);
    }
}
