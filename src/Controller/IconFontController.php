<?php

namespace Hgabka\MediaBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * IconFontController.
 */
class IconFontController extends AbstractController
{
    /**
     * @Route("/chooser", name="HgabkaMediaBundle_icon_font_chooser")
     * @Template()
     *
     * @return array
     */
    public function iconFontChooserAction(Request $request)
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

        return [
            'loader' => $loader,
        ];
    }
}
