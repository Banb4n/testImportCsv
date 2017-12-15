<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * User controller.
 *
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/", name="user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findAll();

        return $this->render('user/index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Creates a new user entity.
     *
     * @Route("/new", name="user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\UserType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        return $this->render('user/new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $user)
    {
        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
        }

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    public function importAction()
    {

        $utilisateurs = array(); // Tableau qui va contenir les éléments extraits du fichier CSV
        $row = 0; // Représente la ligne
        // Import du fichier CSV
        if (($handle = fopen(__DIR__ . "/../../../../Ressources/Utilisateurs.csv", "r")) !== FALSE) { // Lecture du fichier, à adapter
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) { // Eléments séparés par un point-virgule, à modifier si necessaire
                $num = count($data); // Nombre d'éléments sur la ligne traitée
                $row++;
                for ($c = 0; $c < $num; $c++) {
                    $utilisateurs[$row] = array(
                        "firstname" => $data[0],
                        "lastname" => $data[1],
                        "email" => $data[2],
                    );
                }
            }
            fclose($handle);

        }

        $em = $this->getDoctrine()->getManager(); // EntityManager pour la base de données

        // Lecture du tableau contenant les utilisateurs et ajout dans la base de données
        foreach ($utilisateurs as $utilisateur) {

            // On crée un objet utilisateur
            $user = new User();

            // Encode le mot de passe
            $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
            $plainpassword = $utilisateur["password"];
            $password = $encoder->encodePassword($plainpassword, $user->getSalt());

            // Hydrate l'objet avec les informations provenants du fichier CSV
            $user->setFirstname($utilisateur["nom"]);
            $user->setLastname($utilisateur["prenom"]);
            $user->setEmail($utilisateur["mail"]);

            // Enregistrement de l'objet en vu de son écriture dans la base de données
            $em->persist($user);

        }

        // Ecriture dans la base de données
        $em->flush();

        // Renvoi la réponse (ici affiche un simple OK pour l'exemple)
        return new Response('OK');
    }
}
