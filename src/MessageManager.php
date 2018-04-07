<?php
/**
 * Handles the messages in the game
 * User: nekosune
 * Date: 07/04/2018
 * Time: 18:54
 */

namespace LotGD\Core;


use LotGD\Core\Models\Character;
use LotGD\Core\Models\Message;
use LotGD\Core\Models\MessageThread;
use LotGD\Core\Models\SystemCharacter;

class MessageManager
{
    /**
     * Sends a message to a MessageThread
     * @param \LotGD\Core\Models\Character $from
     * @param string $message
     * @param \LotGD\Core\Models\MessageThread $thread
     * @param bool $systemMessage
     * @return \LotGD\Core\Models\Message
     * @throws Exceptions\CoreException
     */
    public function send(
        Character $from,
        string $message,
        MessageThread $thread,
        bool $systemMessage = false
    ) {
        $message=new Message($from, $message, $thread, $systemMessage);
        $thread->addMessage($message);
        return $message;
    }


    /**
     * Sends a system message to a MessageThread
     * @param string $message
     * @param \LotGD\Core\Models\MessageThread $thread
     * @return \LotGD\Core\Models\Message
     * @throws Exceptions\ArgumentException
     * @throws Exceptions\CoreException
     */
    public function sendSystemMessage(
        string $message,
        MessageThread $thread
    ) {
        $message=new Message(SystemCharacter::getInstance(), $message, $thread, true);
        $thread->addMessage($message);
        return $message;
    }
}