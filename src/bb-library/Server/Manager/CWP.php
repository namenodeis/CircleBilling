<?php
/**
 * BoxBilling
 *
 * LICENSE
 *
 * This source file is subject to the license that is bundled
 * with this package in the file LICENSE.txt
 * It is also available through the world-wide-web at this URL:
 * http://www.boxbilling.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@boxbilling.com so we can send you a copy immediately.
 *
 * @copyright Copyright (c) 2010-2012 BoxBilling (http://www.boxbilling.com)
 * @license   http://www.boxbilling.com/LICENSE.txt
 * @version   $Id$
 */
class Server_Manager_CWP extends Server_Manager 
{
    /**
     * Method is called just after object contruct is complete.
     * Add required parameters checks here.
     */
        public function init()
    {
        if (!extension_loaded('curl')) {
            throw new Server_Exception('cURL extension is not enabled');
        }
        if(empty($this->_config['ip'])) {
            throw new Server_Exception('Server manager "CentOS WebPanel" is not configured properly. Server IP Address is not set');
        }
        if(empty($this->_config['accesshash'])) {
            throw new Server_Exception('Server manager "CentOS WebPanel" is not configured properly. Make sure you have added a key and permissions to your server API manager.');
        }
        }

    /**
     * Return server manager parameters.
     * @return type
     */
    public static function getForm()
    {
        return array(
            'label'     =>  'CentOS WebPanel',
        );
    }

    /**
     * Returns link to account management page
     *
     * @return string
     */
    public function getLoginUrl()
    {
        if ($this->_config['secure']) {
            return 'https://'.$this->_config['host'] . ':2083';
        } else {
            return 'https://'.$this->_config['host'] . ':2082';
        }
    }

    /**
     * Returns link to reseller account management
     * @return string
     */
    public function getResellerLoginUrl()
    {
        if ($this->_config['secure']) {
            return 'https://'.$this->_config['host'] . ':2031';
        } else {
            return 'https://'.$this->_config['host'] . ':2030';
        }
    }

    /**
     * This method is called to check if configuration is correct
     * and class can connect to server
     *
     * @return boolean
     */
    public function testConnection()
    {
        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'list'
        );

        $result= $this->_makeRequestTypeServer($params);

            if (isset($result['return']) && $result['return'] == 'status -> OK') {
                    return true;
            }

            return false;
    }

    /**
     * Method retrieves information from server, assigns new values to
     * cloned Server_Account object and returns it.
     * @param Server_Account $a
     * @return Server_Account
     */
    public function synchronizeAccount(Server_Account $a)
    {
        $this->getLog()->info('Synchronizing account with server '.$a->getUsername());
        $new = clone $a;
        //@example - retrieve username from server and set it to cloned object
        //$new->setUsername('newusername');
        return $new;
    }

    /**
     * Create new account on server
     *
     * @param Server_Account $a
     */
    public function createAccount(Server_Account $a)
    {
        if($a->getReseller()) {
            $this->getLog()->info('Creating reseller hosting account');
        } else {
            $this->getLog()->info('Creating shared hosting account');
        }
        $client = $a->getClient();

        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'add',
            "domain" => $a->getDomain(),
            "user" => $a->getUsername(),
            "pass" => $a->getPassword(),
            "email" => $client->getEmail(),
            "package" => '2',
            "inode" => '100000',
            "limit_nproc" => '100',
            "limit_nofile" => '1000',
            "server_ips" => $this->_config['ip'],
        );

        $result = $this->_makeRequestAccount($params);
            
            if (isset($result['return']) && $result['return'] == 'status -> OK') {
                return true;
            }

            return false;
    }
       

    /**
     * Suspend account on server
     * @param Server_Account $a
     */
    public function suspendAccount(Server_Account $a)
    {
        if($a->getReseller()) {
            $this->getLog()->info('Suspending reseller hosting account');
        } else {
            $this->getLog()->info('Suspending shared hosting account');
        }
        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'susp',
            "user" => $a->getUsername(),
        );

        $result = $this->_makeRequestAccount($params);

        return true;
        }

    /**
     * Unsuspend account on server
     * @param Server_Account $a
     */
    public function unsuspendAccount(Server_Account $a)
    {
        if($a->getReseller()) {
            $this->getLog()->info('Unsuspending reseller hosting account');
        } else {
            $this->getLog()->info('Unsuspending shared hosting account');
        }
        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'unsp',
            "user" => $a->getUsername(),
        );

        $result = $this->_makeRequestAccount($params);

        return true;
        }

    /**
     * Cancel account on server
     * @param Server_Account $a
     */
    public function cancelAccount(Server_Account $a)
    {
        if($a->getReseller()) {
            $this->getLog()->info('Cancelling reseller hosting account');
        } else {
            $this->getLog()->info('Cancelling shared hosting account');
        }
        $client = $a->getClient();

        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'del',
            "user" => $a->getUsername(),
            "email" => $client->getEmail(),
        );

        $result = $this->_makeRequestAccount($params);

        return true;
        }

    /**
     * Change account package on server
     * @param Server_Account $a
     * @param Server_Package $p
     */
    public function changeAccountPackage(Server_Account $a, Server_Package $p)
    {
        if($a->getReseller()) {
            $this->getLog()->info('Updating reseller hosting account');
        } else {
            $this->getLog()->info('Updating shared hosting account');
        }
        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'udp',
            "user" => $a->getUsername(),
            "package" => $p->getName(),
        );

        $result = $this->_makeRequestChangePackage($params);

        return true;
        }

    /**
     * Change account username on server
     * @param Server_Account $a
     * @param type $new - new account username
     */
    public function changeAccountUsername(Server_Account $a, $new)
    {
        if($a->getReseller()) {
            $this->getLog()->info('Changing reseller hosting account username');
        } else {
            $this->getLog()->info('Changing shared hosting account username');
        }
        $client = $a->getClient();

        $params = array(
            "key" => $this->_config['accesshash'],
            "action" => 'udp',
            "user" => $new->getUsername(),
            "email" => $client->getEmail(),
            "package" => '@2',
            "backup" => 'on',
            "inode" => '100000',
            "processes" => '100',
            "openfiles" => '1000',
        );

        $result = $this->_makeRequestAccount($params);

        return true;
    }

    /**
     * Change account domain on server
     * @param Server_Account $a
     * @param type $new - new domain name
     */
    public function changeAccountDomain(Server_Account $a, $new)
    {
        {
            throw new Server_Exception('Add and Modify Domains via the Web Panel');
        }
    }

    /**
     * Change account password on server
     * @param Server_Account $a
     * @param type $new - new password
     */
    public function changeAccountPassword(Server_Account $a, $new)
    {
        {
            throw new Server_Exception('Change Password via the Web Panel');
        }
    }

    /**
     * Chonge account IP on server
     * @param Server_Account $a
     * @param type $new - account IP
     */
    public function changeAccountIp(Server_Account $a, $new)
    {
        {
            throw new Server_Exception('Contact your host about changing IP Address');
        }
    }

    /** Methods for connecting to CWP API
    */
    private function _makeRequestAccount($params)
    {
        $host = "https://" . $this->_config['ip'] . ":2304/v1/account";
        $postdata = http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        $result = curl_exec($ch);
        
        if (curl_errno ($ch)) {
            throw new Server_Exception('Error connecting to CentOS WebPanel API: ' . curl_errno ($ch) . ' - ' . curl_error ($ch));
        }
        curl_close($ch);
        return $result;   
    }


    private function _makeRequestChangePackage($params)
    {
        $host = "https://" . $this->_config['ip'] . ":2304/v1/changepack";
        $postdata = http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        $result = curl_exec($ch);
        
        if (curl_errno ($ch)) {
            throw new Server_Exception('Error connecting to CentOS WebPanel API: ' . curl_errno ($ch) . ' - ' . curl_error ($ch));
        }
        curl_close($ch);
        return $result;   
    }

    private function _makeRequestTypeServer($params)
    {
        $host = "https://" . $this->_config['ip'] . ":2304/v1/typeserver";
        $postdata = http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        $result = curl_exec($ch);
        
        if (curl_errno ($ch)) {
            throw new Server_Exception('Error connecting to CentOS WebPanel API: ' . curl_errno ($ch) . ' - ' . curl_error ($ch));
        }
        curl_close($ch);
        return $result;   
    }

}