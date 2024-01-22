<?php
declare(strict_types=1);

namespace PHPTCloud\TelegramApi\Type\DataObject;

use PHPTCloud\TelegramApi\Type\Interfaces\DataObject\InlineKeyboardMarkupInterface;

/**
 * @author  Юдов Алексей tcloud.ax@gmail.com
 * @version 1.0.0
 *
 * Этот объект представляет собой встроенную клавиатуру, которая появляется рядом с сообщением, котором
 * у он принадлежит.
 * @link    https://core.telegram.org/bots/api#inlinekeyboardmarkup
 */
class InlineKeyboardMarkup implements InlineKeyboardMarkupInterface
{
    public function __construct(
        private readonly array $inlineKeyboard,
    ) {}

    public function getInlineKeyboard(): array
    {
        return $this->inlineKeyboard;
    }
}
