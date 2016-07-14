<?php

namespace humhub\modules\logicenter\models;

use humhub\modules\user\libs\Ldap;
use Yii;
use humhub\models\Setting;
use humhub\modules\user\models\User;
use yii\db\Expression;


class LogicUserIdentity extends User
{

    const ERROR_NOT_APPROVED = 10;
    const ERROR_SUSPENDED = 11;

    /**
     * @var Integer Users id
     */
    private $_id;

    /**
     * Returns users id
     *
     * @return Integer
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Authenticates a user based on {@link username} and {@link password}.
     *
     * @return boolean whether authentication succeeds.
     */
    public function authenticate()
    {
        $user = $this->getUser();
        
        if ($user === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        } elseif (!$this->validatePassword($user)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } elseif ($user->status == User::STATUS_DISABLED) {
            $this->errorCode = self::ERROR_SUSPENDED;
        } elseif ($user->status == User::STATUS_NEED_APPROVAL) {
            $this->errorCode = self::ERROR_NOT_APPROVED;
        } else {
            $this->onSuccessfulAuthenticate($user);
        }

        return !$this->errorCode;
    }

    /**
     * Returns user records
     *
     * @return User
     */
    private function getUser()
    {
        // Find User
        $user = User::find()->andWhere(['secondery_email' => $this->username])->one();
        // If user not found in db and ldap is enabled, do ldap lookup and create it when found
        if ($user === null && Setting::Get('enabled', 'authentication_ldap')) {
            try {
                $usernameDn = Ldap::getInstance()->ldap->getCanonicalAccountName($this->username, Zend_Ldap::ACCTNAME_FORM_DN);
                Ldap::getInstance()->handleLdapUser(Ldap::getInstance()->ldap->getNode($usernameDn));
                $user = User::find()->andFilterWhere(array('username' => $this->username));
            } catch (Exception $ex) {
                ;
            }
        }

        return $user;
    }

    /**
     * Validates password of user against internal database or ldap
     *
     * @param User $user
     * @return Succeess
     * @throws CException
     */
    private function validatePassword(User $user)
    {
        if ($user->auth_mode == User::AUTH_MODE_LOCAL) {
            // Authenticate via Local DB
            if ($user->secondery_password != null &&  $user->secondery_password == hash('sha512', hash('whirlpool', $this->password))) {
                return true;
            }
        } elseif ($user->auth_mode == User::AUTH_MODE_LDAP) {
            // Authenticate via LDAP
            if (HLdap::getInstance()->authenticate($user->username, $this->password)) {

                // Reload user object - because we may updated the user while LDAP authentication
                $user = $this->getUser();

                return true;
            }
        } else {
            throw new CException("Invalid user authentication mode!");
        }

        return false;
    }

    /**
     * Executed after successful authenticating a user
     *
     * @param User $user
     */
    private function onSuccessfulAuthenticate($user)
    {
        $user->last_login = new Expression('NOW()');
        $user->save();

        $this->_id = $user->id;
        $this->setState('title', $user->profile->title);
    }
}