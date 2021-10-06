<?php
namespace Terrazza\Component\Http\Message\Uri;

use Psr\Http\Message\UriInterface;

class UriHelper implements IUriHelper {
    private const DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    /**
     * Whether the URI is absolute, i.e. it has a scheme.
     *
     * An instance of UriInterface can either be an absolute URI or a relative reference. This method returns true
     * if it is the former. An absolute URI has a scheme. A relative reference is used to express a URI relative
     * to another URI, the base URI. Relative references can be divided into several forms:
     * - network-path references, e.g. '//example.com/path'
     * - absolute-path references, e.g. '/path'
     * - relative-path references, e.g. 'subpath'
     *
     * @param UriInterface $uri
     * @return bool
     * @see Uri::isRelativePathReference
     * @link https://tools.ietf.org/html/rfc3986#section-4
     * @see Uri::isNetworkPathReference
     * @see Uri::isAbsolutePathReference
     */
    public static function isAbsolute(UriInterface $uri): bool {
        return $uri->getScheme() !== '';
    }

    /**
     * Whether the URI is a absolute-path reference.
     *
     * A relative reference that begins with a single slash character is termed an absolute-path reference.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-4.2
     * @param UriInterface $uri
     * @return bool
     */
    public static function isAbsolutePathReference(UriInterface $uri): bool {
        return $uri->getScheme() === ''
            && $uri->getAuthority() === ''
            && isset($uri->getPath()[0])
            && $uri->getPath()[0] === '/';
    }

    /**
     * Whether the URI is a relative-path reference.
     *
     * A relative reference that does not begin with a slash character is termed a relative-path reference.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-4.2
     * @param UriInterface $uri
     * @return bool
     */
    public static function isRelativePathReference(UriInterface $uri): bool {
        return $uri->getScheme() === ''
            && $uri->getAuthority() === ''
            && (!isset($uri->getPath()[0]) || $uri->getPath()[0] !== '/');
    }

    /**
     * Whether the URI is a network-path reference.
     *
     * A relative reference that begins with two slash characters is termed an network-path reference.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-4.2
     * @param UriInterface $uri
     * @return bool
     */
    public static function isNetworkPathReference(UriInterface $uri): bool {
        return $uri->getScheme() === '' && $uri->getAuthority() !== '';
    }

    /**
     * Whether the URI has the default port of the current scheme.
     *
     * `Psr\Http\Message\UriInterface::getPort` may return null or the standard port. This method can be used
     * independently of the implementation.
     * @param UriInterface $uri
     * @return bool
     */
    public static function isDefaultPort(UriInterface $uri): bool {
        return $uri->getPort() === null
            || (isset(self::DEFAULT_PORTS[$uri->getScheme()]) &&
                $uri->getPort() === self::DEFAULT_PORTS[$uri->getScheme()]);
    }
}