<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/rating/debug', name: 'app_rating_debug')]
    public function debug(): Response
    {
        return $this->render('rating_debug_form.twig');
    }
}
