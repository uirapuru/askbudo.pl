<?php

use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Validator\Constraints as Assert;

set_include_path(implode(PATH_SEPARATOR, array(
    __DIR__ . "/../vendor",
    get_include_path()
)));

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/qa-config.php';


$twigOptions = array(
    'twig.path'    => __DIR__ . '/../views',
    'twig.options' => array(
        'cache' => __DIR__ . '/../views/cache',
    ),
);

$translatorOptions = array(
    'translator.messages' => array(),
);

$doctrineOptions = array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname'   => QA_MYSQL_DATABASE,
        'host'     => QA_MYSQL_HOSTNAME,
        'user'     => QA_MYSQL_USERNAME,
        'password' => QA_MYSQL_PASSWORD,
    ),
);

$app = new Silex\Application();

$app["debug"] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), $twigOptions);
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), $translatorOptions);
$app->register(new Silex\Provider\DoctrineServiceProvider(), $doctrineOptions);

$app->match('/', function (Request $request) use ($app) {
    $data = array(
        'name'  => '',
        'email' => '',
    );

    $form = $app['form.factory']->createBuilder('form', $data)
            ->add('username', "text", array(
                "required"    => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' => 5)
                    ))
            ))
            ->add('email', "email", array(
                "required"    => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array(
                        'min' => 5
                            ))
                )
            ))
            ->add('password', "password", array(
                "required"    => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                            array('min' => 5)
                    )),
            ))
            ->add('send', "submit", array(
                "label" => "Zarejestruj mnie",
                "attr"  => array(
                    "class" => "btn btn-success"
                )
            ))
            ->getForm();

    $form->handleRequest($request);

    if ($form->isValid())
    {
        $checkQuery = "SELECT count(*) as c FROM qa_users WHERE handle = '%s' OR email = '%s'";
        $checkQuery = sprintf($checkQuery, $form["username"]->getData(), $form["email"]->getData());
        $count = $app["db"]->fetchAssoc($checkQuery);

        if ($count["c"] == 0)
        {


            $sql = "INSERT INTO qa_users (created, createip, email, passsalt,"
                    . " passcheck, level, handle, loggedin, loginip)"
                    . " VALUES (NOW(), COALESCE(INET_ATON('%s'), 0), '%s', '%s', UNHEX('%s'),"
                    . " %d, '%s', NOW(), 0)";

            $salt = md5(microtime());

            $sql = sprintf($sql, $_SERVER['REMOTE_ADDR'], $form["email"]->getData(), $salt, sha1(substr($salt, 0, 8) . $form["password"]->getData() . substr($salt, 8)), 0, $form["username"]->getData(), $_SERVER['REMOTE_ADDR']);

            $app['db']->executeQuery($sql);
            $notification = "Dziękujemy za rejestrację!";
        }
        else
        {
            $alert = "Proszę, podaj inną nazwę użytkownika i/lub email. Podane przez Ciebie zostały już użyte.";
        }
    }

    return $app['twig']->render('index.twig', array(
                "alert"        => isset($alert) ? $alert : null,
                "notification" => isset($notification) ? $notification : null,
                "form"         => $form->createView()
    ));
});
$app->run();
