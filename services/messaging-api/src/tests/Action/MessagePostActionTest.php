<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use MinVWS\MessagingApi\Tests\TestHelper\Faker;
use MinVWS\MessagingApi\Tests\TestHelper\PostMessageBodyCreator;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function array_merge;
use function base64_encode;
use function json_decode;
use function sprintf;

/**
 * @group message-post
 */
class MessagePostActionTest extends ActionTestCase
{
    /**
     * @dataProvider validMessageBodyDataProvider
     */
    public function testMessagePost(array $body): void
    {
        $messageUuid = $this->faker->uuid;
        $this->mockUuid($messageUuid);

        $this->mock(MessageWriteRepository::class)
            ->expects($this->once())
            ->method('save');

        $response = $this->postAuthorized('/api/v1/messages', $body);
        $expectedResponseBody = [
            'id' => $messageUuid,
        ];

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponseBody, $response);
    }

    public function testUnauthorized(): void
    {
        $request = $this->post('/api/v1/messages');

        $this->assertEquals(401, $request->getStatusCode());
    }

    public function testMessagePostWithoutBody(): void
    {
        $request = $this->postAuthorized('/api/v1/messages');

        $this->assertEquals(422, $request->getStatusCode());
    }

    /**
     * @dataProvider invalidMessageBodyDataProvider
     */
    public function testMessagePostWithInvalidBody(
        array $body,
        string $field,
        string $message = 'This field cannot be left empty',
    ): void {
        $request = $this->postAuthorized('/api/v1/messages', $body);

        $body = json_decode((string) $request->getBody());
        $this->assertEquals($field, $body->field);
        $this->assertEquals($message, $body->message);

        $this->assertEquals(422, $request->getStatusCode());
    }

    public function testMessagePostSaveFailed(): void
    {
        $this->mock(MessageWriteRepository::class)
            ->method('save')
            ->willThrowException(new RepositoryException());

        $request = $this->postAuthorized('/api/v1/messages', PostMessageBodyCreator::getValidMessageBody());
        $responseBody = json_decode((string) $request->getBody());

        $this->assertEquals(500, $request->getStatusCode());
        $this->assertEquals('Message could not be saved', $responseBody->Error);
    }

    public function validMessageBodyDataProvider(): array
    {
        $faker = Faker::create();
        $validMessageBody = PostMessageBodyCreator::getValidMessageBody();

        return [
            'valid' => [$validMessageBody],
            'with phoneNumber' => [array_merge($validMessageBody, ['phoneNumber' => $faker->phoneNumber])],
            'with aliasExpiresAt' => [array_merge($validMessageBody, [
                'aliasExpiresAt' => $faker->dateTimeBetween('1 week', '2 weeks')->format('c')]),
            ],
            'with expiresAt' => [array_merge($validMessageBody, [
                'expiresAt' => $faker->dateTimeBetween('1 week', '2 weeks')->format('c')]),
            ],
            'with pseudoBsnToken' => [array_merge($validMessageBody, ['pseudoBsnToken' => $faker->uuid])],
            'null aliasExpiresAt' => [array_merge($validMessageBody, ['aliasExpiresAt' => null])],
            'null expiresAt' => [array_merge($validMessageBody, ['expiresAt' => null])],
            'null phoneNumber' => [array_merge($validMessageBody, ['phoneNumber' => null])],
            'null pseudoBsnToken' => [array_merge($validMessageBody, ['pseudoBsnToken' => null])],
            'with identityRequired requires pseudoBsnToken' => [
                array_merge($validMessageBody, ['identityRequired' => true, 'pseudoBsnToken' => $faker->uuid]),
            ],
            'with attachments, but empty' => [array_merge($validMessageBody, ['attachments' => []])],
            'with one attachment' => [array_merge($validMessageBody, ['attachments' => [
                [
                    'filename' => sprintf('%s.%s', $faker->word, $faker->fileExtension()),
                    'content' => base64_encode($faker->paragraph()),
                    'mime_type' => $faker->mimeType(),
                ],
            ]])],
            'with two attachments' => [array_merge($validMessageBody, ['attachments' => [
                [
                    'filename' => sprintf('%s.%s', $faker->word, $faker->fileExtension()),
                    'content' => base64_encode($faker->paragraph()),
                    'mime_type' => $faker->mimeType(),
                ],
                [
                    'filename' => sprintf('%s.%s', $faker->word, $faker->fileExtension()),
                    'content' => base64_encode($faker->paragraph()),
                    'mime_type' => $faker->mimeType(),
                ],
            ]])],
            'with one attachment and unused extra field' => [array_merge($validMessageBody, ['attachments' => [
                [
                    'filename' => sprintf('%s.%s', $faker->word, $faker->fileExtension()),
                    'content' => base64_encode($faker->paragraph()),
                    'notused' => 'foo',
                    'mime_type' => $faker->mimeType(),
                ],
            ]])],
            'with one attachment without extension' => [array_merge($validMessageBody, ['attachments' => [
                [
                    'filename' => $faker->word,
                    'content' => base64_encode($faker->paragraph()),
                    'mime_type' => $faker->mimeType(),
                ],
            ]])],
        ];
    }

    public function testAttachmentStorage(): void
    {
        $body = PostMessageBodyCreator::getValidMessageBody();
        $attachmentContents = $this->faker->paragraph();
        $body['attachments'] = [
            [
                'filename' => sprintf('%s.%s', $this->faker->word, $this->faker->fileExtension()),
                'content' => base64_encode($attachmentContents),
                'mime_type' => $this->faker->mimeType(),
            ]
        ];

        $attachmentUuid = $this->faker->uuid;

        $this->mockUuid($attachmentUuid);
        $response = $this->postAuthorized('/api/v1/messages', $body);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($this->filesystem->has($attachmentUuid));
        $this->assertNotEquals($attachmentContents, $this->filesystem->read($attachmentUuid)); // should be encrypted
    }

    public function invalidMessageBodyDataProvider(): array
    {
        $validMessageBody = PostMessageBodyCreator::getValidMessageBody();

        return [
            'no type' => [array_merge($validMessageBody, ['type' => null]), 'type'],
            'empty type' => [array_merge($validMessageBody, ['type' => '']), 'type'],
            'invalid type' => [array_merge($validMessageBody, ['type' => 'foo']), 'type', 'Invalid value'],
            'no aliasId' => [array_merge($validMessageBody, ['aliasId' => null]), 'aliasId'],
            'empty aliasId' => [array_merge($validMessageBody, ['aliasId' => '']), 'aliasId'],
            'empty aliasExpiresAt' => [
                array_merge($validMessageBody, ['aliasExpiresAt' => '']),
                'aliasExpiresAt',
                'Invalid value',
            ],
            'invalid aliasExpiresAt' => [
                array_merge($validMessageBody, ['aliasExpiresAt' => 'foo']),
                'aliasExpiresAt',
                'Invalid value',
            ],
            'no fromName' => [array_merge($validMessageBody, ['fromName' => null]), 'fromName'],
            'empty fromName' => [array_merge($validMessageBody, ['fromName' => '']), 'fromName'],
            'no fromEmail' => [array_merge($validMessageBody, ['fromEmail' => null]), 'fromEmail'],
            'empty fromEmail' => [array_merge($validMessageBody, ['fromEmail' => '']), 'fromEmail'],
            'no toName' => [array_merge($validMessageBody, ['toName' => null]), 'toName'],
            'empty toName' => [array_merge($validMessageBody, ['toName' => '']), 'toName'],
            'no toEmail' => [array_merge($validMessageBody, ['toEmail' => null]), 'toEmail'],
            'empty toEmail' => [array_merge($validMessageBody, ['toEmail' => '']), 'toEmail'],
            'no subject' => [array_merge($validMessageBody, ['subject' => null]), 'subject'],
            'empty subject' => [array_merge($validMessageBody, ['subject' => '']), 'subject'],
            'no text' => [array_merge($validMessageBody, ['text' => null]), 'text'],
            'empty text' => [array_merge($validMessageBody, ['text' => '']), 'text'],
            'empty expiresAt' => [
                array_merge($validMessageBody, ['expiresAt' => '']),
                'expiresAt',
                'Invalid value',
            ],
            'invalid expiresAt' => [
                array_merge($validMessageBody, ['expiresAt' => 'foo']),
                'expiresAt',
                'Invalid value',
            ],
            'identityRequired but without pseudoBsnToken' => [
                array_merge($validMessageBody, ['identityRequired' => true]),
                'pseudoBsnToken',
                'This field is required',
            ],
            'attachment, but not an array' => [
                array_merge($validMessageBody, ['attachments' => 'string_instead_of_array']),
                'attachments',
                'The provided value is invalid',
            ],
            'attachment, but missing filename' => [
                array_merge($validMessageBody, ['attachments' => [
                    [
                        'content' => base64_encode('filecontents'),
                        'mime_type' => 'application/pdf',
                    ]
                ]]),
                'attachments.0.filename',
                'This field is required',
            ],
            'attachment, but missing content' => [
                array_merge($validMessageBody, ['attachments' => [
                    [
                        'filename' => 'file.pdf',
                        'mime_type' => 'application/pdf',
                    ]
                ]]),
                'attachments.0.content',
                'This field is required',
            ],
            'attachment, file not base64-encoded' => [
                array_merge($validMessageBody, ['attachments' => [
                    [
                        'filename' => 'file.pdf',
                        'content' => 'not_encoded_string',
                        'mime_type' => 'application/pdf',
                    ],
                ]]),
                'attachments.0.content',
                'The provided value is invalid',
            ],
            'attachment, no mime_type' => [
                array_merge($validMessageBody, ['attachments' => [
                    [
                        'filename' => 'file.pdf',
                        'content' => base64_encode('filecontents'),
                    ],
                ]]),
                'attachments.0.mime_type',
                'This field is required',
            ],
        ];
    }
}
