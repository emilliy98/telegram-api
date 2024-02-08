<?php

declare(strict_types=1);

namespace PHPTCloud\TelegramApi\Type\Interfaces\DataObject;

/**
 * @author  Юдов Алексей tcloud.ax@gmail.com
 * @author  Пешко Илья peshkoi@mail.ru
 *
 * @version 1.0.0
 *
 * При получении сообщения с этим объектом клиенты Telegram удалят текущую пользовательскую клавиатуру
 * и отобразят стандартную клавиатуру с буквами. По умолчанию пользовательские клавиатуры отображаются
 * до тех пор, пока бот не отправит новую клавиатуру. Исключение составляют одноразовые клавиатуры,
 * которые скрываются сразу после нажатия пользователем кнопки (см. ReplyKeyboardMarkup).
 *
 * @see https://core.telegram.org/bots/api#replykeyboardremove
 */
interface ReplyKeyboardRemoveInterface
{
    /**
     * Запрос клиентов на удаление пользовательской клавиатуры (пользователь не сможет вызвать эту клавиату
     * ру; если вы хотите скрыть клавиатуру из виду, но оставить ее доступной, используйте параметр one_tim
     * e_keyboard в ReplyKeyboardMarkup).
     */
    public function wantRemoveKeyboard(): bool;

    /**
     * Необязательный. Используйте этот параметр, если вы хотите показывать клавиатуру только определенным
     * пользователям. Цели:
     * 1) пользователи, @mentioned в тексте объекта «Message»;
     * 2) если сообщение бота является ответом на сообщение в том же чате и теме форума.
     *
     * Пример: пользователь запрашивает изменение языка бота, бот отвечает на запрос с помощью клавиатуры д
     * ля выбора нового языка. Другие пользователи в группе не видят клавиатуру.
     */
    public function isSelective(): ?bool;
}
