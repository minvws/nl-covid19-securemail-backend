<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Middleware;

use Exception;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_key_exists;
use function is_int;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

class JwtAuthenticationHelper
{
    public const PLATFORM_IDENTIFIER_ATTRIBUTE = 'platform_identifier_attribute';

    /**
     * From the docs (https://github.com/tuupola/slim-jwt-auth/#before):
     * This before function is called only when authentication succeeds but before the next incoming middleware is
     * called. You can use this to alter the request before passing it to the next incoming middleware in the stack. If
     * it returns anything else than Psr\Http\Message\ServerRequestInterface the return value will be ignored.
     *
     * @throws JwtAuthenticationException
     */
    public static function before(
        ServerRequestInterface $request,
        array $arguments,
        array $platformSecrets,
        array $algorithms,
        int $maxLifetime,
    ): ServerRequestInterface {
        $iat = self::getIat($arguments['decoded']);
        $exp = self::getExp($arguments['decoded']);

        /**
         * First, validate the given timestamps in the (authenticated) token, to make sure the token is still valid
         * Note: the maxLifetime is a configuration value, it is not a value from the token
         */
        self::validateTimestamps($iat, $exp, $maxLifetime);

        /**
         * Next, we need to know the platform identifier from the token. The slim-jwt-auth library only validates if a
         * valid identifier is given, but there is no way to retrieve the value.
         * We just loop the configured identifiers, and stop if we found the match.
         */
        foreach ($platformSecrets as $platformIdentifier => $platformSecret) {
            try {
                /**
                 * Manually try to decode the token with the current platform-identifier
                 */
                JWT::decode($arguments['token'], $platformSecret, $algorithms);
            } catch (Exception $exception) {
                /**
                 * If decoding fails, this was not the identifier we were looking for. Just continue and try the next
                 */
                continue;
            }

            /**
             * If decoding is a success, we found the identifier we were looking for! We store the platform identifier
             * within the request for later usage (and stop looking for others since there can be only one)
             */
            $request = $request->withAttribute(self::PLATFORM_IDENTIFIER_ATTRIBUTE, $platformIdentifier);
            break;
        }

        return $request;
    }

    public static function error(ResponseInterface $response, array $arguments): int
    {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];

        return $response
            ->withHeader("Content-Type", "application/json")
            ->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * @throws JwtAuthenticationException
     */
    private static function getIat(array $decoded): int
    {
        if (!array_key_exists('iat', $decoded)) {
            throw new JwtAuthenticationException('iat not set');
        }

        if (!is_int($decoded['iat'])) {
            throw new JwtAuthenticationException('iat is not an integer');
        }

        return $decoded['iat'];
    }

    /**
     * @throws JwtAuthenticationException
     */
    private static function getExp(array $decoded): int
    {
        if (!array_key_exists('exp', $decoded)) {
            throw new JwtAuthenticationException('exp not set');
        }

        if (!is_int($decoded['exp'])) {
            throw new JwtAuthenticationException('exp is not an integer');
        }

        return $decoded['exp'];
    }

    /**
     * @throws JwtAuthenticationException
     */
    private static function validateTimestamps(int $iat, int $exp, int $maxLifetime): void
    {
        if ($exp > $iat + $maxLifetime) {
            throw new JwtAuthenticationException('max lifetime exceeded');
        }
    }
}
