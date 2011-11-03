<?php

namespace EdpUser\Service;

use Doctrine\ORM\EntityManager,
    Zend\Authentication\AuthenticationService,
    SpiffyDoctrine\Authentication\Adapter\DoctrineEntity as DoctrineAuthAdapter,
    Zend\Authentication\Adapter as AuthAdapter,
    Zend\Form\Form,
    DateTime;

class User
{
    /**
     * @var AuthAdapter
     */
    protected $authAdapter;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $entityClass = 'EdpUser\Entity\User';

    /**
     * @var Zend\Authentication\AuthenticationService
     */
    protected $authService;

    /**
     * authenticate 
     * 
     * @param string $identity 
     * @param string $credential 
     * @return bool
     */
    public function authenticate($identity, $credential)
    {
        $adapter     = $this->getAuthAdapter($identity, $credential);
        $authService = $this->getAuthService();
        $result      = $authService->authenticate($adapter);
        if (!$result->isValid()) {
            // Try username...
            // @TODO: Check settings
            $adapter = $this->getAuthAdapter($identity, $credential, 'username'); 
            $result  = $authService->authenticate($adapter);
            if (!$result->isValid()) {
                return false;
            }
        }
        return true;
    }

    public function createFromForm(Form $form)
    {
        $user = new $this->entityClass;
        $user->setEmail($form->getValue('email'))
             ->setUsername($form->getValue('username'))
             ->setDisplayName($form->getValue('display_name'))
             ->setSalt($this->randomBytes(16))
             ->setPassword($this->hashPassword($form->getValue('password'), $user->getSalt()))
             ->setRegisterIp($_SERVER['REMOTE_ADDR'])
             ->setRegisterTime(new DateTime('now'));
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
        return $user;
    }

    /**
     * getEntityManager 
     * 
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * setEntityManager 
     * 
     * @param EntityManager $entityManager 
     * @return User
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * getAuthAdapter 
     * 
     * @param string $identity 
     * @param string $credential 
     * @param string $identitycolumn 
     * @return DoctrineAuthAdapter
     */
    public function getAuthAdapter($identity, $credential, $identityColumn = 'email')
    {
        if (null === $this->authAdapter) {
            $authAdapter = new DoctrineAuthAdapter(
                $this->getEntityManager(),
                $this->entityClass
            );
            $this->setAuthAdapter($authAdapter);
        }
        $this->authAdapter->setIdentity($identity)
                          ->setCredential($password)
                          ->setIdentityColumn($identityColumn);
        return $this->authAdapter;
    }

    /**
     * setAuthAdapter 
     * 
     * @param AuthAdapter $authAdapter 
     * @return User
     */
    public function setAuthAdapter(AuthAdapter $authAdapter)
    {
        $this->authAdapter = $authAdapter;
        return $this;
    }

    /**
     * getAuthService 
     * 
     * @return mixed
     */
    public function getAuthService()
    {
        if (null === $this->authService) {
            $this->authService = new AuthenticationService;
        }
        return $this->authService;
    }
    /**
     * setAuthenticationService 
     * 
     * @param mixed $authService 
     * @return User
     */
    public function setAuthService($authService)
    {
        $this->authService = $authService;
        return $this;
    }

    /**
     * hashPassword
     *
     * @param string $password
     * @param string $salt
     * @return string
     */
    public function hashPassword($password, $salt)
    {
        return hash('sha512', $password.$salt);
    }

    /**
     * randomBytes
     *
     * returns X random raw binary bytes
     *
     * @param int $byteLength
     * @return string
     */
    public function randomBytes($byteLength)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes($byteLength);
        } elseif (is_readable('/dev/urandom')) {
            $fp = fopen('/dev/urandom','rb');
            if ($fp !== false) {
                $data = fread($fp, $byteLength);
                fclose($fp);
            }
        } elseif(function_exists('mcrypt_create_iv') && version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $data = mcrypt_create_iv($byteLength, MCRYPT_DEV_URANDOM);
        } elseif (class_exists('COM')) {
            try {
                $capi = new \COM('CAPICOM.Utilities.1');
                $data = $capi->GetRandom($btyeLength,0);
            } catch (\Exception $ex) {} // Fail silently
        }
        if(empty($data)) {
            throw new \Exception(
                'Unable to find a secure method for generating random bytes.'
            );
        }
        return $data;
    }
}