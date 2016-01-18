<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Base controller with some handy generic controller functions.
 *
 * This class differs from the Symfony Base Controller in that
 * it does not use the container for dependencies, but has those
 * injected instead
 */
abstract class BaseController
{
    /** @var EngineInterface */
    private $templating;
    /** @var Router */
    private $router;
    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(EngineInterface $templating, Router $router, FormFactoryInterface $formFactory)
    {
        $this->templating = $templating;
        $this->formFactory = $formFactory;
        $this->router = $router;
    }

    /** @see Symfony\Bundle\FrameworkBundle\Controller\Controller::createForm */
    protected function createForm($type, $data = null, array $options = array())
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /** @see Symfony\Bundle\FrameworkBundle\Controller\Controller::redirect */
    protected function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /** @see Symfony\Bundle\FrameworkBundle\Controller\Controller::redirectToRoute */
    protected function redirectToRoute($route, array $parameters = array(), $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /** @see Symfony\Bundle\FrameworkBundle\Controller\Controller::generateUrl */
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /** @see Symfony\Bundle\FrameworkBundle\Controller\Controller::render */
    protected function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->templating->renderResponse($view, $parameters, $response);
    }
}
