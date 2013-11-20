<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;

set_include_path(implode(PATH_SEPARATOR, array(
    __DIR__ . "/../vendor",
    get_include_path()
)));

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

// debug

$app["debug"] = true;

// twig

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'    => __DIR__ . '/../views',
    'twig.options' => array(
        'cache' => __DIR__ . '/../views/cache',
    )
));

// session

$app->register(new Silex\Provider\SessionServiceProvider());

// formularz

$app->register(new FormServiceProvider());

$data = array(
    'email'   => '',
    'message' => '',
);

$form = $app['form.factory']->createBuilder('form', $data)
        ->add('name', "text", array(
            "label"    => false,
            "required" => true,
            "attr"     => array(
                "placeholder" => "Twoje imie"
            )
        ))
        ->add('email', "email", array(
            "label"    => false,
            "required" => true,
            "attr"     => array(
                "placeholder" => "Twój email"
            )
        ))
        ->add('message', "textarea", array(
            "label"    => false,
            "required" => true,
            "attr"     => array(
                "placeholder" => "Treść wiadomości"
            )
        ))
        ->getForm();

// routingi

$app->get('/language/{lang}', function ($lang) use ($app) {
    $app['session']->set('language', $lang);
    return $app->redirect('/');
})->value("lang", "pl");

$app->get('/{page}', function ($page) use ($app, $form) {
            $pages = array(
                "company.html",
                "realisations.html",
                "clients.html",
                "contact.html",
            );

            $index = array_search($page, $pages);

            $pages_temp = array();

            foreach ($pages as $i => $link) {
                if ($index === false)
                {
                    $pages_temp[$link] = "right";
                }
                else if ($i < $index && $index !== false)
                {
                    $pages_temp[$link] = "left";
                }
                else if ($i == $index)
                {
                    $pages_temp[$link] = "active";
                }
                else if ($i > $index)
                {
                    $pages_temp[$link] = "right";
                }
            }

            return $app['twig']->render('layout.twig', array(
                        'form'     => $form->createView(),
                        "page"     => $page ? $page : null, "pages"    => $pages_temp,
                        "language" => $app['session']->get('language', "pl")
                            )
            );
        })
        ->value("page", "company.html")
;

$app->run();
