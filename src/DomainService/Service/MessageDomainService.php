<?php

declare(strict_types=1);

namespace PHPTCloud\TelegramApi\DomainService\Service;

use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\MessageArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Factory\SerializersAbstractFactoryInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\MessageArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\DomainService\Enums\TelegramApiMethodEnum;
use PHPTCloud\TelegramApi\DomainService\Interfaces\Service\MessageDomainServiceInterface;
use PHPTCloud\TelegramApi\Exception\Error\TelegramApiException;
use PHPTCloud\TelegramApi\Exception\Interfaces\ExceptionAbstractFactoryInterface;
use PHPTCloud\TelegramApi\Request\Interfaces\RequestInterface;
use PHPTCloud\TelegramApi\TelegramApiFieldEnum;
use PHPTCloud\TelegramApi\Type\Interfaces\DataObject\MessageInterface;
use PHPTCloud\TelegramApi\Type\Interfaces\Deserializer\MessageDeserializerInterface;
use PHPTCloud\TelegramApi\Type\Interfaces\Factory\DeserializersAbstractFactoryInterface;

/**
 * @author  Юдов Алексей tcloud.ax@gmail.com
 *
 * @version 1.0.0
 */
class MessageDomainService implements MessageDomainServiceInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly DeserializersAbstractFactoryInterface $deserializersAbstractFactory,
        private readonly SerializersAbstractFactoryInterface $serializersAbstractFactory,
        private readonly ExceptionAbstractFactoryInterface $exceptionAbstractFactory,
    ) {
    }

    public function sendMessage(MessageArgumentInterface $argument): MessageInterface
    {
        /** @var MessageArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(MessageArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        if (isset($data[TelegramApiFieldEnum::REPLY_MARKUP->value])) {
            $data[TelegramApiFieldEnum::REPLY_MARKUP->value] = json_encode($data[TelegramApiFieldEnum::REPLY_MARKUP->value]);
        }

        $response = $this->request::post(TelegramApiMethodEnum::SEND_MESSAGE->value, $data);

        if ($response->isError()) {
            $exception = $this->exceptionAbstractFactory->createByApiErrorMessage($response->getErrorMessage());
            if ($exception) {
                throw $exception;
            }
            throw new TelegramApiException($response->getErrorMessage(), $response->getCode());
        }

        /** @var MessageDeserializerInterface $deserializer */
        $deserializer = $this->deserializersAbstractFactory->create(MessageDeserializerInterface::class);

        return $deserializer->deserialize($response->getResponseData()[RequestInterface::RESULT_KEY]);
    }
}
