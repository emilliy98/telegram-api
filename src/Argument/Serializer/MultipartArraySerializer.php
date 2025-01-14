<?php

declare(strict_types=1);

namespace PHPTCloud\TelegramApi\Argument\Serializer;

use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\InputMediaArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\InputMediaVideoArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\DataObject\LocalFileArgumentInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\InputMediaAudioArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\InputMediaDocumentArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\InputMediaPhotoArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\InputMediaVideoArgumentArraySerializerInterface;
use PHPTCloud\TelegramApi\Argument\Interfaces\Serializer\MultipartArraySerializerInterface;
use PHPTCloud\TelegramApi\TelegramApiFieldEnum;

/**
 * @author  Юдов Алексей tcloud.ax@gmail.com
 */
class MultipartArraySerializer implements MultipartArraySerializerInterface
{
    public function __construct(
        private readonly InputMediaDocumentArgumentArraySerializerInterface $inputMediaDocumentArgumentArraySerializer,
        private readonly InputMediaPhotoArgumentArraySerializerInterface $inputMediaPhotoArgumentArraySerializer,
        private readonly InputMediaAudioArgumentArraySerializerInterface $inputMediaAudioArgumentArraySerializer,
        private readonly InputMediaVideoArgumentArraySerializerInterface $inputMediaVideoArgumentArraySerializer,
    ) {
    }

    public function serialize(array $parameters): array
    {
        $multipart = [];

        foreach ($parameters as $key => $value) {
            if ($value instanceof InputMediaArgumentInterface) {
                $multipart[] = $this->createInputMediaParameters(TelegramApiFieldEnum::MEDIA->value, $value, $multipart);
                continue;
            } elseif ($key === TelegramApiFieldEnum::MEDIA->value) {
                $multipart[] = $this->createMediaGroupParameters($key, $value, $multipart);
                continue;
            } elseif ($value instanceof LocalFileArgumentInterface) {
                $multipart[] = $this->createLocalFileParameter($key, $value);
                continue;
            } elseif (is_array($value)) {
                $multipart[] = $this->createArrayParameter($key, $value);
                continue;
            }
            $multipart[] = $this->createParameter($key, $value);
        }

        return $multipart;
    }

    private function createInputMediaParameters(string $key, InputMediaArgumentInterface $inputMedia, array &$multipart): array
    {
        $contents = [];

        $parameters = [];
        if ($inputMedia instanceof InputMediaVideoArgumentInterface) {
            $parameters = $this->inputMediaVideoArgumentArraySerializer->serialize($inputMedia);
        }

        foreach ($parameters as $index => $value) {
            if ($value instanceof LocalFileArgumentInterface) {
                $multipart[] = $this->createLocalFileParameter($value->getBaseName(), $value);
                $contents[$index] = sprintf('attach://%s', $value->getBaseName());
                continue;
            }
            $contents[$index] = $value;
        }

        return [
            'name' => $key,
            'contents' => json_encode($contents),
        ];
    }

    private function createMediaGroupParameters(string $name, array $contents, array &$originMultipart): array
    {
        $multipart = [];

        foreach ($contents as $index => $media) {
            $_multipart = [];
            foreach ($media as $key => $value) {
                if ($value instanceof LocalFileArgumentInterface) {
                    $_multipart[$key] = sprintf('attach://%s', $value->getBaseName());
                    $originMultipart[] = [
                        'name' => $value->getBaseName(),
                        'contents' => fopen($value->getFilePath(), 'r+'),
                    ];
                    continue;
                }
                $_multipart[$key] = $value;
                continue;
            }

            $multipart[] = $_multipart;
        }

        return [
            'name' => $name,
            'contents' => json_encode($multipart),
        ];
    }

    private function createArrayParameter(string $name, array $contents): array
    {
        return [
            'name' => $name,
            'contents' => json_encode($contents),
        ];
    }

    private function createParameter(string $name, string|int|float|bool|null $contents): array
    {
        return [
            'name' => $name,
            'contents' => $contents,
        ];
    }

    private function createLocalFileParameter(string $name, LocalFileArgumentInterface $contents): array
    {
        return [
            'name' => $name,
            'contents' => fopen($contents->getFilePath(), 'r'),
        ];
    }
}
