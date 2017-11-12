<?php
/**
 * Simple Message sending class for Mailgun
 *
 * @author Christian Engel <hello@wearekiss.com>
 * @version 1 31.05.2016
 */

namespace Kiss;

class Mailgun
{
    private $apiKey = NULL;
    /**
     * @var \Kiss\CurlInterface
     */
    private $c;

    var $replyTo = NULL;
    var $tracking = FALSE;
    var $fromMail = NULL;

    function __construct($apiKey, $domain)
    {
        $this->apiKey = $apiKey;
        $this->fromMail = 'mail@' . $domain;

        $this->c = new CurlInterface('https://api.mailgun.net/v3/' . $domain);
        $this->c->basicAuth('api', $apiKey);
    }

    /**
     * Simple send mail method.
     * @param $target
     * @param $subject
     * @param $html
     * @param $text
     */
    function plainSend($target, $subject, $html, $text)
    {
        $sendConf = array(
            'html' => $html,
            'text' => $text,
            'subject' => $subject,
            'from' => $this->fromMail,
            'o:tracking-opens' => $this->tracking ? 'yes' : 'no',
            'o:tracking-clicks' => $this->tracking ? 'htmlonly' : 'no',
            'to' => $target
        );

        //Enforcing undisclosed recipients
        if (is_array($target)) {
            $sendConf['to'] = implode(', ', $target);
            $obj = array();

            foreach($target as $k){
                $obj[$k] = array('id' => uniqid());
            }

            $sendConf['recipient-variables'] = json_encode($obj);
        }

        if ($this->replyTo) {
            $sendConf['h:Reply-To'] = $this->replyTo;
        }

        return $this->c->post('/messages', $sendConf);
    }
}