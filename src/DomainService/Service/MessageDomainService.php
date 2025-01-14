<?php

declare(strict_types=1);

namespace PHPTCloud\TelegramApi\DomainService\Service;

use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\ArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\CopyMessageArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\CopyMessagesArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\EditMessageCaptionArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\EditMessageMediaArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\EditMessageTextArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\ForwardMessageArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\ForwardMessagesArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\MessageArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendAnimationArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendAudioArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendDocumentArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendMediaGroupArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendPhotoArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendVideoArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendVideoNoteArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SendVoiceArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\SetMessageReactionArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Factory\SerializersAbstractFactoryInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\CopyMessageArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\CopyMessagesArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\EditMessageCaptionArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\EditMessageMediaArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\EditMessageTextArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\ForwardMessageArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\ForwardMessagesArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\MessageArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\MultipartArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendAnimationArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendAudioArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendDocumentArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendMediaGroupArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendPhotoArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendVideoArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendVideoNoteArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SendVoiceArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\SetMessageReactionArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\DomainService\Enums\TelegramApiMethodEnum;
use PHPTCloud\TelegramApi\DomainService\Interfaces\Service\MessageDomainServiceInterface;
use PHPTCloud\TelegramApi\DomainService\Interfaces\Service\SupportsSendingMultipartInterface;
use PHPTCloud\TelegramApi\DomainService\Traits\SupportsSendingMultipartTrait;
use PHPTCloud\TelegramApi\Exception\Error\MessageIdsMustBeInIncreasingOrderException;
use PHPTCloud\TelegramApi\Exception\Error\TelegramApiException;
use PHPTCloud\TelegramApi\Exception\Interfaces\ExceptionAbstractFactoryInterface;
use PHPTCloud\TelegramApi\Request\Interfaces\RequestInterface;
use PHPTCloud\TelegramApi\SerializerInterface;
use PHPTCloud\TelegramApi\TelegramApiFieldEnum;
use PHPTCloud\TelegramApi\Type\DataObject\MessageId;
use PHPTCloud\TelegramApi\Type\Interfaces\DataObject\MessageIdInterface;
use PHPTCloud\TelegramApi\Type\Interfaces\DataObject\MessageInterface;
use PHPTCloud\TelegramApi\Type\Interfaces\Deserializer\MessageDeserializerInterface;
use PHPTCloud\TelegramApi\Type\Interfaces\Deserializer\MessageIdDeserializerInterface;
use PHPTCloud\TelegramApi\Type\Interfaces\Factory\DeserializersAbstractFactoryInterface;
use PHPTCloud\TelegramApi\Utils\Interface\Service\SortingAlgorithmServiceInterface;

/**
 * @author  Юдов Алексей tcloud.ax@gmail.com
 */
class MessageDomainService implements
    MessageDomainServiceInterface,
    SupportsSendingMultipartInterface
{
    use SupportsSendingMultipartTrait;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly DeserializersAbstractFactoryInterface $deserializersAbstractFactory,
        private readonly SerializersAbstractFactoryInterface $serializersAbstractFactory,
        private readonly ExceptionAbstractFactoryInterface $exceptionAbstractFactory,
        private readonly SortingAlgorithmServiceInterface $sortingAlgorithmService,
        private readonly MultipartArraySerializerInterface $multipartArraySerializer,
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

    public function forwardMessage(ForwardMessageArgumentInterface $argument): MessageInterface
    {
        /** @var ForwardMessageArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(ForwardMessageArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        $response = $this->request::post(TelegramApiMethodEnum::FORWARD_MESSAGE->value, $data);

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

    /**
     * @return MessageIdInterface[]
     *
     * @throws MessageIdsMustBeInIncreasingOrderException
     * @throws TelegramApiException
     */
    public function forwardMessages(ForwardMessagesArgumentInterface $argument, bool $sortIds = false): array
    {
        /** @var ForwardMessagesArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(ForwardMessagesArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        if ($sortIds) {
            $data[TelegramApiFieldEnum::MESSAGE_IDS->value]
                = $this->sortingAlgorithmService->sortArrayOfNumbers($data[TelegramApiFieldEnum::MESSAGE_IDS->value]);
        }

        $response = $this->request::post(TelegramApiMethodEnum::FORWARD_MESSAGES->value, $data);

        if ($response->isError()) {
            $exception = $this->exceptionAbstractFactory->createByApiErrorMessage($response->getErrorMessage());
            if ($exception) {
                throw $exception;
            }
            throw new TelegramApiException($response->getErrorMessage(), $response->getCode());
        }

        /** @var MessageIdDeserializerInterface $deserializer */
        $deserializer = $this->deserializersAbstractFactory->create(MessageIdDeserializerInterface::class);
        $messageIds = array_map(static function (array $item) use ($deserializer) {
            return $deserializer->deserialize($item);
        }, $response->getResponseData()[RequestInterface::RESULT_KEY]);

        return $messageIds;
    }

    public function copyMessage(CopyMessageArgumentInterface $argument): MessageId
    {
        /** @var CopyMessageArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(CopyMessageArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        $response = $this->request::post(TelegramApiMethodEnum::COPY_MESSAGE->value, $data);

        if ($response->isError()) {
            $exception = $this->exceptionAbstractFactory->createByApiErrorMessage($response->getErrorMessage());
            if ($exception) {
                throw $exception;
            }
            throw new TelegramApiException($response->getErrorMessage(), $response->getCode());
        }

        /** @var MessageIdDeserializerInterface $deserializer */
        $deserializer = $this->deserializersAbstractFactory->create(MessageIdDeserializerInterface::class);

        return $deserializer->deserialize($response->getResponseData()[RequestInterface::RESULT_KEY]);
    }

    public function copyMessages(CopyMessagesArgumentInterface $argument, bool $sortIds = false): array
    {
        /** @var CopyMessagesArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(CopyMessagesArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        if ($sortIds) {
            $data[TelegramApiFieldEnum::MESSAGE_IDS->value]
                = $this->sortingAlgorithmService->sortArrayOfNumbers($data[TelegramApiFieldEnum::MESSAGE_IDS->value]);
        }

        $response = $this->request::post(TelegramApiMethodEnum::COPY_MESSAGES->value, $data);

        if ($response->isError()) {
            $exception = $this->exceptionAbstractFactory->createByApiErrorMessage($response->getErrorMessage());
            if ($exception) {
                throw $exception;
            }
            throw new TelegramApiException($response->getErrorMessage(), $response->getCode());
        }

        /** @var MessageIdDeserializerInterface $deserializer */
        $deserializer = $this->deserializersAbstractFactory->create(MessageIdDeserializerInterface::class);

        return array_map(static function (array $item) use ($deserializer) {
            return $deserializer->deserialize($item);
        }, $response->getResponseData()[RequestInterface::RESULT_KEY]);
    }

    public function sendPhoto(SendPhotoArgumentInterface $argument): MessageInterface
    {
        /** @var SendPhotoArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendPhotoArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_PHOTO->value);
    }

    public function sendAudio(SendAudioArgumentInterface $argument): MessageInterface
    {
        /** @var SendAudioArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendAudioArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_AUDIO->value);
    }

    public function sendDocument(SendDocumentArgumentInterface $argument): MessageInterface
    {
        /** @var SendDocumentArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendDocumentArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_DOCUMENT->value);
    }

    public function sendVideo(SendVideoArgumentInterface $argument): MessageInterface
    {
        /** @var SendVideoArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendVideoArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_VIDEO->value);
    }

    public function sendAnimation(SendAnimationArgumentInterface $argument): MessageInterface
    {
        /** @var SendAnimationArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendAnimationArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_ANIMATION->value);
    }

    public function sendVoice(SendVoiceArgumentInterface $argument): MessageInterface
    {
        /** @var SendVoiceArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendVoiceArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_VOICE->value);
    }

    public function sendVideoNote(SendVideoNoteArgumentInterface $argument): MessageInterface
    {
        /** @var SendVoiceArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendVideoNoteArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_VIDEO_NOTE->value);
    }

    public function sendMediaGroup(SendMediaGroupArgumentInterface $argument): array
    {
        /** @var SendMediaGroupArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SendMediaGroupArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::SEND_MEDIA_GROUP->value);
    }

    public function setMessageReaction(SetMessageReactionArgumentInterface $argument): bool
    {
        /** @var SetMessageReactionArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(SetMessageReactionArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        $response = $this->request::post(TelegramApiMethodEnum::SET_MESSAGE_REACTION->value, $data);

        if ($response->isError()) {
            $exception = $this->exceptionAbstractFactory->createByApiErrorMessage($response->getErrorMessage());
            if ($exception) {
                throw $exception;
            }
            throw new TelegramApiException($response->getErrorMessage(), $response->getCode());
        }

        return $response->getResponseData()[RequestInterface::RESULT_KEY];
    }

    public function editMessageText(EditMessageTextArgumentInterface $argument): MessageInterface
    {
        /** @var EditMessageTextArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(EditMessageTextArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        if (isset($data[TelegramApiFieldEnum::REPLY_MARKUP->value])) {
            $data[TelegramApiFieldEnum::REPLY_MARKUP->value] = json_encode($data[TelegramApiFieldEnum::REPLY_MARKUP->value]);
        }

        $response = $this->request::post(TelegramApiMethodEnum::EDIT_MESSAGE_TEXT->value, $data);

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

    public function editMessageCaption(EditMessageCaptionArgumentInterface $argument): MessageInterface
    {
        /** @var EditMessageCaptionArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(EditMessageCaptionArgumentArraySerializerInterface::class);
        $data = $serializer->serialize($argument);

        if (isset($data[TelegramApiFieldEnum::REPLY_MARKUP->value])) {
            $data[TelegramApiFieldEnum::REPLY_MARKUP->value] = json_encode($data[TelegramApiFieldEnum::REPLY_MARKUP->value]);
        }

        $response = $this->request::post(TelegramApiMethodEnum::EDIT_MESSAGE_CAPTION->value, $data);

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

    public function editMessageMedia(EditMessageMediaArgumentInterface $argument): MessageInterface
    {
        /** @var EditMessageMediaArgumentArraySerializerInterface $serializer */
        $serializer = $this->serializersAbstractFactory->create(EditMessageMediaArgumentArraySerializerInterface::class);

        return $this->sendMultipartMessage($serializer, $argument, TelegramApiMethodEnum::EDIT_MESSAGE_MEDIA->value);
    }

    /**
     * @return MessageInterface|MessageInterface[]
     */
    private function sendMultipartMessage(
        SerializerInterface $serializer,
        ArgumentInterface $argument,
        string $method,
    ): MessageInterface|array {
        $response = $this->sendMultipart($serializer, $argument, $method);
        /** @var MessageDeserializerInterface $deserializer */
        $deserializer = $this->deserializersAbstractFactory->create(MessageDeserializerInterface::class);

        $data = $response->getResponseData()[RequestInterface::RESULT_KEY];
        if (is_array($data) && !isset($data['message_id'])) {
            $messages = [];
            foreach ($data as $item) {
                $messages[] = $deserializer->deserialize($item);
            }

            return $messages;
        }

        return $deserializer->deserialize($data);
    }
}
