<?php

namespace ApplicationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ApplicationBundle\Form\ApplicationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 * @package ApplicationBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $form = $this->createForm(ApplicationType::class);
        return $this->render(
            'ApplicationBundle:Default:index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $form = $this->createForm(ApplicationType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $application = $form->getData();
            $doctrineManager = $this->getDoctrine()->getManager();
            $doctrineManager->persist($application);
            $doctrineManager->flush();

            $this->get('application.application_uploader')->upload($form['file']->getData(), $application->getId());
        }

        return $this->render(
            'ApplicationBundle:Default:index.html.twig',
            [
                'form' => $form->createView(),
                'success' => true,
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function adminAction()
    {
        $doctrineManager = $this->getDoctrine()->getManager();
        $list = $doctrineManager->getRepository('ApplicationBundle:Application')->findBy(
            [],
            ['id' => 'DESC']
        );

        return $this->render(
            'ApplicationBundle:Default:admin.html.twig',
            [
                'applications' => $list,
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('ApplicationBundle:Default:login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    public function downloadAction(Request $request, $id)
    {
        $doctrineManager = $this->getDoctrine()->getManager();
        $application = $doctrineManager->getRepository('ApplicationBundle:Application')
            ->findOneBy(['id' => $id]);

        $fileName = $application->getFile()->getName();
        $file = $this->get('application.application_uploader')->getPath($fileName, $id);

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ];

        return new Response(file_get_contents($file), 200, $headers);
    }
}
