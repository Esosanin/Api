<?php

namespace App\Controllers;

use App\Models\capitalhumano\Colaborador;
use CodeIgniter\Email\Email;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use ReflectionException;

class Auth extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }
    /**
     * Register a new user
     * @return Response
     * @throws ReflectionException
     */
    // public function register()
    // {
    //     $rules = [
    //         'name' => 'required',
    //         'email' => 'required|min_length[6]|max_length[50]|valid_email|is_unique[user.email]',
    //         'password' => 'required|min_length[8]|max_length[255]'
    //     ];

    //     $input = $this->getRequestInput($this->request);
    //     if (!$this->validateRequest($input, $rules)) {
    //         return $this
    //             ->getResponse(
    //                 $this->validator->getErrors(),
    //                 ResponseInterface::HTTP_BAD_REQUEST
    //             );
    //     }

    //     $userModel = new Colaborador();
    //     $userModel->save($input);




    //     return $this
    //         ->getJWTForUser(
    //             $input['email'],
    //             ResponseInterface::HTTP_CREATED
    //         );
    // }

    /**
     * Authenticate Existing User
     * @return Response
     */
    public function login()
    {
        $rules = [
            'email' => 'required|min_length[6]|max_length[50]|valid_email',
            'password' => 'required|min_length[8]|max_length[255]|validateUser[email, password]'
        ];

        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        $input = $this->getRequestInput($this->request);

        $input['email'] = trim($input['email'] . '@ecnautomation.com');


        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        }
        return $this->getJWTForUser($input['email']);
    }

    public function tokenCheck()
    {
        $json = $this->request->getJSON();
        $tokenRequest = $json->token ? $json->token : NULL;
        $id = $json->id ? $json->id : 0;
        if ($id != 0) {
            $query = $this->db->query("SELECT token FROM Colaboradores WHERE id_colaborador=?", [$id]);
            $result = $query->getResult();
            if (count($result) > 0) {
                $token = $result[0]->token;
                if ($tokenRequest === $token) {
                    $this->db->query("UPDATE Colaboradores SET token=NULL WHERE id_colaborador=?",[$id]);

                    return $this->getResponse(
                        [
                            'token' => true
                        ]
                    );
                } else {
                    return $this->getResponse(
                        [
                            'token' => false
                        ]
                    );
                }
            } else {
                return $this->getResponse(
                    [
                        'token' => false
                    ]
                );
            }
        } else {
            return $this->getResponse(
                [
                    'token' => false
                ]
            );
        }
    }

    public function recoverPass()
    {
        $rules = [
            'newPass' => 'required|min_length[8]|max_length[50]',
            'newPass2' => 'required|min_length[8]|max_length[50]',
        ];

        $errors = [];
        try {
            $input = $this->getRequestInput($this->request);
            if (!$this->validateRequest($input, $rules, $errors)) {
                return $this
                    ->getResponse(
                        $this->validator->getErrors(),
                        ResponseInterface::HTTP_BAD_REQUEST
                    );
            }

            if ($input['newPass'] === $input['newPass2']) {
                $id_colaborador = $input['id'];
                $hash = password_hash($input['newPass'], PASSWORD_BCRYPT);
                if($id_colaborador!=0){
                    $this->db->query("UPDATE Colaboradores SET pass=? WHERE id_colaborador=?", [
                        $hash,
                        $id_colaborador
                    ]);
                    return $this
                    ->getResponse(
                        [
                            'message' => 'Contraseña actualizada.'
                        ]
                    );
                }else{
                    return $this
                    ->getResponse(
                        [
                            'error' => 'Usuario inválido.'
                        ]
                    );
                }
                
            } else {
                return $this
                    ->getResponse(
                        [
                            'error' => 'Las contraseñas no coinciden.'
                        ]
                    );
            }
        } catch (Exception $e) {
            return $this
                ->getResponse(
                    [
                        'error' => $e->getMessage(),
                    ]
                );
        }
    }

    public function recoverPassRequest()
    {
        $rules = [
            'email' => 'required|min_length[6]|max_length[50]|valid_email',
        ];

        $errors = [];
        try {
            $input = $this->getRequestInput($this->request);
            if (!$this->validateRequest($input, $rules, $errors)) {
                return $this
                    ->getResponse(
                        $this->validator->getErrors(),
                        ResponseInterface::HTTP_BAD_REQUEST
                    );
            }

            $model = new Colaborador();
            $user = $model->findUserByEmailAddress($input['email']);
            // $str = rand();
            // $result = md5($str);
            // $contraseña = substr($result, 0, 8);
            $id_colaborador = $user['id_colaborador'];
            //$hash = password_hash($contraseña, PASSWORD_BCRYPT);


            if (count($user) > 0) {
                $email = new Email();
                $email->mailType = 'html';

                function generateRandomString($length = 10)
                {
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $charactersLength = strlen($characters);
                    $randomString = '';
                    for ($i = 0; $i < $length; $i++) {
                        $randomString .= $characters[random_int(0, $charactersLength - 1)];
                    }
                    return $randomString;
                }
                
                $token = md5(generateRandomString());
                $html = "
                <header style='padding:12px;'>
                     <img width='150' src='http://intranet.ecn.com.mx:8060/lineup/public/images/logos/ECN_logo.png'>
                </header>
                <br>
                <body>
                <div style='padding:24px;'>
                <p>Se ha solicitado recuperar la contraseña de Line Up. Para proceder con la solicitud haga click en el siguiente enlace: <a href='http://intranet.ecn.com.mx:8060/app/login?t=".$token."&u=".$id_colaborador."'>Confirmar</a></p>
                <br>
                <p>Puede actualizar su contraseña ingresando a Line Up, en el menú Mi cuenta.</p>
                <p>*Si usted no ha solicitado una nueva contraseña favor de hacer caso omiso a este correo y reportarlo al departamento de TI.</p>
                </div>
                </body>
                <footer style='padding:12px;'>
                </footer>
                ";
                $email->setFrom('intranet@ecnautomation.com');
                $email->setTo($user['email']);
                $email->setSubject('Solicitud de recuperación de contraseña.');
                $email->setMessage($html);
                if ($email->send()) {
                    $this->db->query("UPDATE Colaboradores SET token=? WHERE id_colaborador = ?", [
                        $token,
                        $id_colaborador
                    ]);
                }

                return $this
                    ->getResponse(
                        [
                            'message' => 'Email sent'
                        ]
                    );
            }
        } catch (Exception $e) {
            return $this
                ->getResponse(
                    [
                        'error' => $e->getMessage(),
                    ]
                );
        }
    }

    private function getJWTForUser(
        string $emailAddress,
        int $responseCode = ResponseInterface::HTTP_OK
    ) {
        try {
            $model = new Colaborador();
            $user = $model->findUserByEmailAddress($emailAddress);
            unset($user['pass']);
            unset($user['email']);

            $permisos = $model->getPermisos();

            helper('jwt');

            return $this
                ->getResponse(
                    [
                        'message' => 'User authenticated successfully',
                        'user' => $user,
                        'permisos' => $permisos,
                        'access_token' => getSignedJWTForUser($emailAddress)
                    ]
                );
        } catch (Exception $exception) {
            return $this
                ->getResponse(
                    [
                        'error' => $exception->getMessage(),
                    ],
                    $responseCode
                );
        }
    }
}
