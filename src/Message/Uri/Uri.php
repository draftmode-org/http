<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Message\Uri;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface {
    /**
     * Unreserved characters for use in a regex.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.3
     */
    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * Sub-delims for use in a regex.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     */
    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /** @var string Uri scheme. */
	private string $scheme = '';

    /** @var string Uri user info. */
	private string $userInfo = '';

    /** @var string Uri host. */
	private string $host = '';

    /** @var int|null Uri port. */
	private ?int $port = null;

    /** @var string Uri path. */
	private string $path = '';

    /** @var string Uri query string. */
	private string $query = '';

    /** @var string Uri fragment. */
	private string $fragment = '';

    /** @var string|null String representation */
	private ?string $composedComponents = null;

    public function __construct(string $uri = '') {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new InvalidArgumentException("Unable to parse URI: $uri");
            }
            $this->applyParts($parts);
        }
    }

    public function __toString(): string {
        return $this->composedComponents ??= $this->composeComponents(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    public function getScheme(): string {
        return $this->scheme;
    }

    public function getAuthority(): string {
        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string {
        return $this->userInfo;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getPort(): ?int {
        return $this->port;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getQuery(): string {
        return $this->query;
    }

    public function getFragment(): string {
        return $this->fragment;
    }

	/**
	 * @param string $scheme
	 * @return static
	 */
    public function withScheme($scheme): self {
        $scheme                                     = $this->filterScheme($scheme);
        if ($this->scheme === $scheme) {
            return $this;
        }

        $new                                        = clone $this;
        $new->scheme                                = $scheme;
        $new->composedComponents                    = null;
        $new->validateState();
        return $new;
    }

	/**
	 * @param string $user
	 * @param null|string $password
	 * @return static
	 */
    public function withUserInfo($user, $password = null): self {
        $info                                       = $this->filterUserInfoComponent($user);
        if ($password !== null) {
            $info                                   .= ':' . $this->filterUserInfoComponent($password);
        }
        if ($this->userInfo === $info) {
            return $this;
        }

        $new                                        = clone $this;
        $new->userInfo                              = $info;
        $new->composedComponents                    = null;
        $new->validateState();
        return $new;
    }

	/**
	 * @param string $host
	 * @return static
	 */
    public function withHost($host): self {
        $host                                       = $this->filterHost($host);
        if ($this->host === $host) {
            return $this;
        }
        $new                                        = clone $this;
        $new->host                                  = $host;
        $new->composedComponents                    = null;
        $new->validateState();
        return $new;
    }

	/**
	 * @param int|null $port
	 * @return static
	 */
    public function withPort($port): self {
        $port                                       = $this->filterPort($port);
        if ($this->port === $port) {
            return $this;
        }

        $new                                        = clone $this;
        $new->port                                  = $port;
        $new->composedComponents                    = null;
        $new->validateState();
        return $new;
    }

	/**
	 * @param string $path
	 * @return static
	 */
    public function withPath($path): self {
        $path                                       = $this->filterPath($path);
        if ($this->path === $path) {
            return $this;
        }

        $new                                        = clone $this;
        $new->path                                  = $path;
        $new->composedComponents                    = null;
        $new->validateState();
        return $new;
    }

	/**
	 * @param string $query
	 * @return static
	 */
    public function withQuery($query): self {
        $query                                      = $this->filterQueryAndFragment($query);
        if ($this->query === $query) {
            return $this;
        }
        $new                                        = clone $this;
        $new->query                                 = $query;
        $new->composedComponents                    = null;
        return $new;
    }

	/**
	 * @param string $fragment
	 * @return static
	 */
    public function withFragment($fragment): self {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        $new->composedComponents = null;

        return $new;
    }

    /**
     * Composes a URI reference string from its various components.
     *
     * Usually this method does not need to be called manually but instead is used indirectly via
     * `Psr\Http\Message\UriInterface::__toString`.
     *
     * PSR-7 UriInterface treats an empty component the same as a missing component as
     * getQuery(), getFragment() etc. always return a string. This explains the slight
     * difference to RFC 3986 Section 5.3.
     *
     * Another adjustment is that the authority separator is added even when the authority is missing/empty
     * for the "file" scheme. This is because PHP stream functions like `file_get_contents` only work with
     * `file:///myfile` but not with `file:/myfile` although they are equivalent according to RFC 3986. But
     * `file:///` is the more common syntax for the file scheme anyway (Chrome for example redirects to
     * that format).
     *
     * @link https://tools.ietf.org/html/rfc3986#section-5.3
     * @param string|null $scheme
     * @param string|null $authority
     * @param string $path
     * @param string|null $query
     * @param string|null $fragment
     * @return string
     */
    private function composeComponents(?string $scheme, ?string $authority, string $path, ?string $query, ?string $fragment): string {
        $uri = '';
        // weak type checks to also accept null until we can add scalar type hints
        if ($scheme != '') {
            $uri                                    .= ($scheme ?? '') . ':';
        }
        if ($authority != '' || $scheme === 'file') {
            $uri                                    .= '//' . ($authority ?? '');
        }
        $uri                                        .= $path;
        if ($query != '') {
            $uri                                    .= '?' . ($query ?? '');
        }
        if ($fragment != '') {
            $uri                                    .= '#' . ($fragment ?? '');
        }
        return $uri;
    }

    /**
     * Apply parse_url parts to a URI.
     *
     * @param array $parts Array of parse_url parts to apply.
     */
    private function applyParts(array $parts): void {
        $this->scheme = isset($parts['scheme'])
            ? $this->filterScheme($parts['scheme'])
            : '';
        $this->userInfo = isset($parts['user'])
            ? $this->filterUserInfoComponent($parts['user'])
            : '';
        $this->host = isset($parts['host'])
            ? $this->filterHost($parts['host'])
            : '';
        $this->port = isset($parts['port'])
            ? $this->filterPort($parts['port'])
            : null;
        $this->path = isset($parts['path'])
            ? $this->filterPath($parts['path'])
            : '';
        $this->query = isset($parts['query'])
            ? $this->filterQueryAndFragment($parts['query'])
            : '';
        $this->fragment = isset($parts['fragment'])
            ? $this->filterQueryAndFragment($parts['fragment'])
            : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $this->filterUserInfoComponent($parts['pass']);
        }
    }

	/**
	 * @param mixed $scheme
	 *
	 * @return string
	 * @throws InvalidArgumentException If the scheme is invalid.
	 */
    private function filterScheme($scheme): string {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('scheme must be a string');
        }
        return strtolower($scheme);
    }

	/**
	 * @param mixed $component
	 *
	 * @return string
	 * @throws InvalidArgumentException If the user info is invalid.
	 */
    private function filterUserInfoComponent($component): string {
        if (!is_string($component)) {
            throw new InvalidArgumentException('user info must be a string');
        }
        return preg_replace_callback(
            '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $component
        ) ?? '';
    }

	/**
	 * @param mixed $host
	 *
	 * @return string
	 * @throws InvalidArgumentException If the host is invalid.
	 */
    private function filterHost($host): string {
        if (!is_string($host)) {
            throw new InvalidArgumentException('Host must be a string');
        }
        return strtolower($host);
    }

	/**
	 * @param mixed $port
	 *
	 * @return int|null
	 * @throws InvalidArgumentException If the port is invalid.
	 */
    private function filterPort($port): ?int {
        if ($port === null) {
            return null;
        }
        $port                                       = (int) $port;
        if (0 > $port || 0xffff < $port) {
            throw new InvalidArgumentException(
                sprintf('invalid port: %d. must be between 0 and 65535', $port)
            );
        }
        return $port;
    }

	/**
	 * Filters the path of a URI
	 *
	 * @param mixed $path
	 *
	 * @return string
	 * @throws InvalidArgumentException If the path is invalid.
	 */
    protected function filterPath($path): string {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }
        $pattern                                    = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback(
            $pattern,
            [__CLASS__, 'rawurlencodeMatchZero'],
            $path
        ) ?? '';
    }

	/**
	 * Filters the query string or fragment of a URI.
	 *
	 * @param mixed $str
	 *
	 * @return string
	 * @throws InvalidArgumentException If the query or fragment is invalid.
	 */
    protected function filterQueryAndFragment($str): string {
        if (!is_string($str)) {
            throw new InvalidArgumentException('query and fragment must be a string');
        }
        $pattern                                    = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback(
            $pattern,
            [__CLASS__, 'rawurlencodeMatchZero'],
            $str
        ) ?? '';
    }

    /**
     * @param array $match
     * @return string
     * @codeCoverageIgnore
     */
    protected function rawurlencodeMatchZero(array $match): string {
        return rawurlencode($match[0]);
    }

    private function validateState(): void {
        /*if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
            $this->host = self::HTTP_DEFAULT_HOST;
        }*/
        if ($this->getAuthority() === '') {
            if (0 === strpos($this->path, '//')) {
                throw new InvalidArgumentException('The path of a URI without an authority must not start with two slashes "//"');
            }
            if ($this->scheme === '' && strpos(explode('/', $this->path, 2)[0], ':') !== false) {
                throw new InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        } elseif (isset($this->path[0]) && $this->path[0] !== '/') {
            throw new InvalidArgumentException('The path of a URI with an authority must start with a slash "/" or be empty');
        }
    }
}
