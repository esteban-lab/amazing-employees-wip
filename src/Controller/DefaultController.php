<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use App\Service\EmployeeNormalize;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// AbstractController es un controlador de Symfony
// que pone a disposición nuestra multitud de características.
class DefaultController extends AbstractController
{
    /**
     * @Route("/default", name="default_index")
     * 
     * La clase ruta debe estar precedida en los comentario por una arroba.
     * El primer parámetro de Route es la URL a la que queremos asociar la acción.
     * El segundo parámetro de Route es el nombre que queremos dar a la ruta.
     */
    public function index(Request $request, EmployeeRepository $employeeRepository): Response
    {
        if($request->query->has('term')) {
            $people = $employeeRepository->findByTerm($request->query->get('term'));

            return $this->render('default/index.html.twig', [
                'people' => $people
             ]);
        }

        // Una acción siempre debe devolver una respesta.
        // Por defecto deberá ser un objeto de la clase,
        // Symfony\Component\HttpFoundation\Response

        // render() es un método hereado de AbstractController
        // que devuelve el contenido declarado en una plantillas de Twig.
        // https://twig.symfony.com/doc/3.x/templates.html
        
        // symfony console
        // es un comando equivalente a 
        // php bin/console

        // Mostrar las rutas disonibles en mi navegador:
        // - symfony console debug:router
        // - symfony console debug:router default_index
        // - symfony console router --help
        // - symfony console router:match /
        
        // Acceso y propiedades del objeto Request.
        // https://symfony.com/doc/current/controller.html#the-request-and-response-object
        // echo '<pre>query: '; var_dump($request->query); echo '</pre>'; // Equivalente a $_GET, pero supervitaminado.
        // echo '<pre>post: '; var_dump($request->request); echo '</pre>'; // Equivalente a $_POST, pero supervitaminado.
        // echo '<pre>server: '; var_dump($request->server); echo '</pre>'; // Equivalente a $_SERVER, pero supervitaminado.
        // echo '<pre>files: '; var_dump($request->files); echo '</pre>'; // Equivalente a $_FILES, pero supervitaminado.
        // echo '<pre>idioma prefererido: '; var_dump($request->getPreferredLanguage()); echo '</pre>';
        
        // Metodo 1: accediendo al rpositorio a través de AbstractController.
        // $people = $this->getDoctrine()->getRepository(Employee::class)->findAll(); // Employee::class = App\Entity\Employee

        $order = [];
        
        if($request->query->has('orderBy')) {
            $order[$request->query->get('orderBy')] = $request->query->get('orderDir', 'ASC');
            // $order = ['email' => 'DESC'];
        }

        // Metodo 2: creando un parámetro indicando el tipo (type hint).
        $people = $employeeRepository->findBy([], $order); // Employee::class = App\Entity\Employee

        return $this->render('default/index.html.twig', [
           'people' => $people
        ]);
    }

    /**
     * @Route("/hola", name="default_hola")
     */
    public function hola(): Response {
        return new Response('<html><body>hola</body></html>');
    }

    /**
     * @Route(
     *      "/default.{_format}",
     *      name="default_index_json",
     *      requirements = {
     *          "_format": "json"
     *      }
     * )
     * 
     * El comando:
     * symfony console router:match /default.json
     * buscará la acción coincidente con la ruta indicada
     * y mostrará la información asociada.
     */
    public function indexJson(
        Request $request,
        EmployeeRepository $employeeRepository,
        EmployeeNormalize $employeeNormalize
    ): JsonResponse {
        $result = $request->query->has('id') ? 
            $employeeRepository->find($request->query->get('id')) :
            $employeeRepository->findAll();

        if ($result instanceof Employee) {
            return $this->json($employeeNormalize->employeeNormalize($result));
        }

        $data = [];

        foreach ($result as $employee) {
            array_push($data, $employeeNormalize->employeeNormalize($employee));
        }

        return $this->json($data);
    }


    /**
     * @Route(
     *      "/default/{id}",
     *      name="default_show",
     *      requirements = {
     *          "id": "\d+"
     *      }
     * )
     */
    // La técinca ParamConverte inyecta directamente,
    // un objeto del tipo indicado como parámetro
    // intentando hacer un match del parámetro de la ruta
    // con alguna de las propiedades del objeto requerido.
    // https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
    public function show(Employee $employee): Response {
        dump($this->getUser());
        $this->isGranted('ROLE_ADMIN');
        
        return $this->render('default/show.html.twig', [
            'person' => $employee
        ]);
    }

    /**
     * @Route("/redirect-to-home", name="default_redirect_to_home")
     */
    public function redirectToHome(): RedirectResponse {
        // Redirigir a la URL /
        // return $this->redirect('/');

        // Redirigir a una ruta utilizando su nombre.
        // return $this->redirectToRoute('default_show', ['id' => 1]);

        // Devolver directamente un objeto RedirectResponse.
        return new RedirectResponse('/', Response::HTTP_TEMPORARY_REDIRECT);
    }

    
        private function normalizeEmployee (Employee $employee): ?array {
            $projects = [];
    
            foreach($employee->getProjects() as $project) {
                array_push($projects, [
                    'id' => $project->getId(),    
                    'name' => $project->getName(),    
                ]);
            }
    
            return [
                'name' => $employee->getName(),
                'email' => $employee->getEmail(),
                'city' => $employee->getCity(),
                'department' => [
                    'id' => $employee->getDepartment()->getId(),
                    'name' => $employee->getDepartment()->getName(),
                ],
                'projects' => $projects
            ];
        }
    
    // EJERCICIO
    // Crear la el recurso para obtener una representación
    // de "UN" empleado en formato JSON.
}
