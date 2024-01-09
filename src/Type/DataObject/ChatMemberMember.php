<?php
declare(strict_types=1);

namespace PHPTCloud\TelegramApi\Type\DataObject;

use PHPTCloud\TelegramApi\Type\Interfaces\ChatMemberMemberInterface;

/**
 * @author  Юдов Алексей tcloud.ax@gmail.com
 * @author  Юдов Никита yudov.nikita@bk.ru
 * @version 1.0.0
 *
 * Представляет участника чата, не имеющего дополнительных привилегий или ограничений.
 * @link    https://core.telegram.org/bots/api#chatmembermember
 */
class ChatMemberMember extends ChatMember implements ChatMemberMemberInterface
{
}
