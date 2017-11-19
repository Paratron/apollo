<?php
/**
 * Class User
 * =============
 * This class represents user objects.
 */

namespace Apollo;

class User
{

    /**
     * Id of the object in database.
     * @var {Integer}
     */
    var $id;

    /**
     * Reference to the Database object.
     * @var {\Kiss\MySQLi}
     */
    var $db;

    /**
     * Database sync state
     * @var {Boolean}
     */
    var $dbSync;

    /**
     * This keeps the fields that are used in the database, as well as the
     * data to be expected in each field.
     * @var array
     */
    static $fields = array(
        'creationTime' => 'int',
        'modificationTime' => 'int',
        'mail' => 'mail',
        'mailConfirmed' => 'bool',
        'password' => 'string',
        'oAuthGoogle' => 'string',
        'oAuthFacebook' => 'string',
        'oAuthGithub' => 'string',
        'firstName' => 'string',
        'lastName' => 'string'
    );

    /**
     * Column names that begin with a asterisk (*) are primary keys,
     * names that begin with a dash (#) are normal keys and
     * if they begin with a tilde (~), they are created as unique fields.
     * @var array
     */
    static $sql = array(
        '*id' => 'INT(10) NOT NULL AUTO_INCREMENT',
        'creationTime' => 'INT(15)',
        'modificationTime' => 'INT(15)',
        '#mail' => 'VARCHAR(256)',
        'mailConfirmed' => 'INT(1)',
        'password' => 'VARCHAR(128)',
        '#oAuthGoogle' => 'VARCHAR(128)',
        '#oAuthFacebook' => 'VARCHAR(128)',
        '#oAuthGithub' => 'VARCHAR(128)',
        'firstName' => 'VARCHAR(128)',
        'lastName' => 'VARCHAR(128)'
    );

    var $data = array(
        'creationTime' => 0,
        'modificationTime' => 0,
        'mail' => NULL,
        'mailConfirmed' => FALSE,
        'password' => '',
        'oAuthGoogle' => NULL,
        'oAuthFacebook' => NULL,
        'oAuthGithub' => NULL,
        'firstName' => '',
        'lastName' => '',
    );

    /**
     * Call getJSON() with a specific group name as parameter to control the returned properties.
     * The properties, listed in each group will be _omitted_ from the result of getJSON().
     *
     * For example, the save method of the class omits the fields 'id' and 'creationTime'.
     * @var array
     */
    var $omitFields = array(
        'save' => array('id', 'creationTime'),
        'browser' => array('password', 'oAuthGoogle', 'oAuthFacebook', 'oAuthGithub')
    );

    /**
     * Name of the related table in the database.
     * @var string
     */
    static $dbTable = 'apollo_user';

    function __construct(/* polymorph */)
    {
        $this->db = requireDatabase();

        if (func_num_args() === 1) {
            $arg0 = func_get_arg(0);

            if (is_array($arg0)) {
                $this->assignVars($arg0);
                if ($this->id) {
                    $this->dbSync = TRUE;
                }
            } else {
                if (is_integer($arg0)) {
                    $sql = 'SELECT * FROM ' . self::$dbTable . ' WHERE id = ' . intval($arg0) . ' LIMIT 1;';
                } else {
                    $sql = 'SELECT * FROM ' . self::$dbTable . ' WHERE mail = ' . $this->db->escape($arg0) . ' LIMIT 1;';
                }

                $result = $this->db->queryRow($sql);
                if (!$result) {
                    throw new \ErrorException('Object not found in database');
                }
                $this->assignVars($result);
                $this->dbSync = TRUE;
            }

            return;
        }

        $this->data['creationTime'] = time();
    }

    function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        throw new \ErrorException('Invalid field');
    }

    function __set($name, $value)
    {
        if (isset(self::$fields[$name])) {
            switch (self::$fields[$name]) {
                case 'int':
                    $this->data[$name] = (int)$value;
                    break;
                case 'bool':
                    $this->data[$name] = (bool)$value;
                    break;
                default:
                    $this->data[$name] = $value;
            }
            $this->dbSync = FALSE;
        }
    }

    /**
     * Overwrites multiple properties of the object.
     * @param $data
     */
    function set($data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        if (isset($data['password'])) {
            $data['password'] = \Kiss\Utils::hash_password($data['password']);
        }

        foreach ($data as $k => $v) {
            if (isset(self::$fields[$k])) {
                switch (self::$fields[$k]) {
                    case 'int':
                        $this->data[$k] = (int)$v;
                        break;
                    case 'bool':
                        $this->data[$k] = (bool)$v;
                        break;
                    case 'array':
                        if (is_array($v)) {
                            $this->data[$k] = $v;
                        } else {
                            if (is_string($v)) {
                                $this->data[$k] = json_decode($v, TRUE);
                            }
                        }
                        break;
                    default:
                        $this->data[$k] = $v;
                }
            }
        }
    }

    /**
     * Takes an array and assigns the properties to this object instance.
     * @param {Array} $a
     */
    private function assignVars($a)
    {
        if (isset($a['id'])) {
            $this->id = (int)$a['id'];
        }
        foreach ($a as $k => $v) {
            if (isset(self::$fields[$k])) {
                switch (self::$fields[$k]) {
                    case 'int':
                        $this->data[$k] = (int)$v;
                        break;
                    case 'bool':
                        $this->data[$k] = (bool)$v;
                        break;
                    case 'array':
                        $this->data[$k] = json_decode($v, TRUE);
                        break;
                    default:
                        $this->data[$k] = $v;
                }
            }
        }
    }

    //-----------------------------------------------------------------------------------------------------

    /**
     * Returns a Array representation of the object to - for instance - be returned as a JSON object.
     * @param array [$omitGroup=NULL]
     * @return array
     */
    function getJSON($omitGroup = NULL)
    {
        $output = array_merge($this->data, array('id' => $this->id));

        if (isset($omitGroup)) {
            foreach ($this->omitFields[$omitGroup] as $k) {
                unset($output[$k]);
            }
        }

        return $output;
    }

    function login($password)
    {
        return \Kiss\Utils::hash_password($password, $this->data['password']) === $this->data['password'];
    }

    /**
     * Sets the user as "logged in". You'd normally call this after a successful
     * call to login() or a successful oAuth call .
     */
    function enableSession()
    {
        $_SESSION['apolloUser'] = $this->id;
    }

    /**
     * Terminates the current user session.
     */
    function disableSession()
    {
        unset($_SESSION['apolloUser']);
    }

    /**
     * Returns a digitally signed version of this users ID.
     * Pass $challenge = TRUE to create a more secure id hash.
     */
    function getIdHash($challenge = FALSE)
    {
        global $conf;
        return ($challenge ? $this->id . '.' : '') . \Kiss\Utils::number_encode(
                $this->id,
                $conf['system']['cryptoKey'],
                $challenge ? $this->creationTime : NULL,
                FALSE
            );
    }

    /**
     * Will write the objects information to the database.
     * If the object doesn't exist in the database, it will be created and the returned Id will be set as $id property.
     * Saving the object will set the $dbSync status to TRUE.
     * @throws \ErrorException
     */
    function save()
    {
        $this->data['modificationTime'] = time();

        $put = $this->getJSON('save');

        foreach (self::$fields as $k => $v) {
            if ($v === 'array' && isset($put[$k])) {
                $put[$k] = json_encode($put[$k]);
            }

            if ($v === 'bool' || $v === 'boolean') {
                $put[$k] = $v ? 1 : 0;
            }
        }

        if ($this->id) {
            $sql = 'UPDATE ' . self::$dbTable . ' SET ' . $this->db->makeSqlSetString($put) . ' WHERE id = ' . $this->id . ' LIMIT 1;';
            $this->db->query($sql);
        } else {
            $put['creationTime'] = $this->data['creationTime'];
            $sql = 'INSERT INTO ' . self::$dbTable . $this->db->makeSqlValueString($put);
            $this->id = $this->db->queryInsert($sql);
        }

        $this->dbSync = TRUE;
    }

    /**
     * Removes this object from the database.
     */
    function destroy()
    {
        if (!$this->id) {
            throw new \ErrorException('Can only performed for previously saved objects.');
        }

        $sql = 'DELETE FROM ' . self::$dbTable . ' WHERE id = ' . $this->id . ' LIMIT 1;';
        $this->db->query($sql);
        $this->id = NULL;
        $this->dbSync = FALSE;
        return TRUE;
    }

    /**
     * Will try to send a confirmation mail for this user.
     */
    function sendConfirmationMail()
    {
        $mailgun = requireMailgun();


    }

    function setMailAddressConfirmed()
    {
        $this->data['mailConfirmed'] = TRUE;
        $this->save();
    }

    // ===========================================================================

    /**
     * Returns the currently logged in user.
     * @return User|null
     */
    public static function getBySession()
    {
        if (isset($_SESSION['apolloUser'])) {
            try {
                $user = new self($_SESSION['apolloUser']);
            } catch (\ErrorException $e) {
                return NULL;
            }

            return $user;
        }

        return NULL;
    }

    /**
     * Returns an account by id hash.
     * @param $idHash
     * @return User|null
     */
    public static function getByIdHash($idHash)
    {
        global $conf;

        $idHash = explode('.', $idHash);

        if (count($idHash) === 1) {
            try {
                $id = \Kiss\Utils::number_decode($idHash, $conf['system']['cryptoKey']);
            } catch (\ErrorException $e) {
                return NULL;
            }

            return new self($id);
        }

        $id = (int)$idHash[0];

        if (!$id) {
            return NULL;
        }

        $user = new self($id);

        if (!$user->id) {
            return NULL;
        }

        $idCheck = \Kiss\Utils::number_decode($idHash[1], $conf['system']['cryptoKey'], $user->creationTime);

        if ($idCheck !== $id) {
            return NULL;
        }

        return $user;
    }

    /**
     * Returns an account by Google User Id.
     * @param $userId
     * @return User|null
     */
    public static function getByGoogle($userId)
    {
        $db = requireDatabase();

        $sql = 'SELECT * FROM ' . self::$dbTable . ' WHERE oAuthGoogle = ' . $db->escape($userId) . ' LIMIT 1;';

        $result = $db->queryRow($sql);

        if ($result) {
            return new self($result);
        }

        return NULL;
    }

    /**
     * Returns an account by Github User Id.
     * @param $userId
     * @return User|null
     */
    public static function getByGithub($userId)
    {
        $db = requireDatabase();

        $sql = 'SELECT * FROM ' . self::$dbTable . ' WHERE oAuthGithub = ' . $db->escape($userId) . ' LIMIT 1;';

        $result = $db->queryRow($sql);

        if ($result) {
            return new self($result);
        }

        return NULL;
    }

    /**
     * Returns an account by Facebook User Id.
     * @param $userId
     * @return User|null
     */
    public static function getByFacebook($userId)
    {
        $db = requireDatabase();

        $sql = 'SELECT * FROM ' . self::$dbTable . ' WHERE oAuthFacebook = ' . $db->escape($userId) . ' LIMIT 1;';

        $result = $db->queryRow($sql);

        if ($result) {
            return new self($result);
        }

        return NULL;
    }

    /**
     * Checks if a given mail address exists in the user database.
     * @param $mail
     * @return bool
     */
    public static function exists($mail)
    {
        $db = requireDatabase();

        $sql = 'SELECT id FROM ' . self::$dbTable . ' WHERE mail = ' . $db->escape($mail) . ' LIMIT 1;';

        return $db->queryValue($sql) > 0;
    }
}