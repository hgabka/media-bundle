<?php

namespace Hgabka\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * IconFontController.
 */
class IconFontController extends AbstractController
{
    #[Route(
        '/chooser-icon',
        name: 'HgabkaMediaBundle_icon_font_chooser'
    )]
    public function iconFontChooserAction(Request $request): Response
    {
        $loader = $request->query->get('loader');
        $loaderData = json_decode($request->query->get('loader_data'), true);

        $iconFontManager = $this->get('hgabka_media.icon_font_manager');
        if (empty($loader)) {
            $loader = $iconFontManager->getDefaultLoader();
        } else {
            $loader = $iconFontManager->getLoader($loader);
        }
        $loader->setData($loaderData);

        return $this->render('@HgabkaMedia/IconFont/iconFontChooser.html.twig', [
            'loader' => $loader,
        ]);
    }
}
