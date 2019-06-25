<?php
declare(strict_types=1);

namespace LotGD\Core;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\Message;
use LotGD\Core\Models\MessageThread;
use LotGD\Core\Models\SystemCharacter;

/**
 * Manages the message system overall
 * Class MessageManager.
 */
class MessageManager
{
    /**
     * Sends a message to a MessageThread.
     * @param \LotGD\Core\Models\Character $from
     * @param string $message
     * @param \LotGD\Core\Models\MessageThread $thread
     * @param bool $systemMessage
     * @throws Exceptions\CoreException
     * @return \LotGD\Core\Models\Message
     */
    public function send(
        Character $from,
        string $message,
        MessageThread $thread,
        bool $systemMessage = false
    ): Message {
        $message = new Message($from, $message, $thread, $systemMessage);
        $thread->addMessage($message);
        return $message;
    }

    /**
     * Sends a system message to a MessageThread.
     * @param string $message
     * @param \LotGD\Core\Models\MessageThread $thread
     * @throws Exceptions\ArgumentException
     * @throws Exceptions\CoreException
     * @return \LotGD\Core\Models\Message
     */
    public function sendSystemMessage(
        string $message,
        MessageThread $thread
    ): Message {
        $message = new Message(SystemCharacter::getInstance(), $message, $thread, true);
        $thread->addMessage($message);
        return $message;
    }
}
