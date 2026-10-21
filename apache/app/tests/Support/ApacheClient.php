<?php

// -----------------------------------------------------------
//  [*] ApacheClient — an HTTP client for the real Apache
//
//  Talks to the Apache that run.sh starts on port 8080 the
//  way the Caddy endpoint does in production: every request
//  carries the public host name and says it came in over
//  HTTPS. Redirects are never followed and error statuses
//  never throw, so a test sees exactly what Apache answered.
//  Cookies are kept by hand rather than in Guzzle's jar,
//  which would hold back a Secure cookie over plain HTTP —
//  the session must survive the day the session cookie
//  gets its Secure flag.
//
//  Used by:
//    - ApacheTestCase (tests/) — one client per test
// -----------------------------------------------------------


namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;








// -----------------------------------------------------------
// ApacheClient
// -----------------------------------------------------------
//
// A Guzzle client plus a cookie store keyed by cookie name.
//
//   get, post — one request, cookies sent and kept
//   csrfToken — the CSRF token a page's form holds
//   cookie    — a cookie Apache has set, with its
//               attributes
//
// Used by:
//   - ApacheTestCase (tests/) — one client per test
// -----------------------------------------------------------

class ApacheClient
{

    // Where run.sh starts Apache inside the container
    private const BASE_URI = 'http://127.0.0.1:8080';

    // What the Caddy endpoint adds to every request it
    // forwards in production
    private const PROXY_HEADERS = [
        'Host'              => 'cyberphish.knf.vu.lt',
        'X-Forwarded-Proto' => 'https',
    ];

    // The Guzzle client, and the cookies Apache has set so
    // far — SetCookie objects keyed by name
    private $client;
    private $cookies = [];






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // A client that never throws on an error status and never
    // follows a redirect.
    //
    // Used by:
    //   - ApacheTestCase::setUp (tests/)
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => self::BASE_URI,
            'http_errors'     => false,
            'allow_redirects' => false,
            'timeout'         => 30,
        ]);
    }






    // -----------------------------------------------------------
    // get
    // -----------------------------------------------------------
    //
    // A GET of the path, with any extra headers.
    //
    // Used by:
    //   - the Apache suite (Apache/)
    //   - csrfToken (below)
    // -----------------------------------------------------------

    public function get(string $path, array $headers = []): ResponseInterface
    {
        return $this->request('GET', $path, ['headers' => $headers]);
    }






    // -----------------------------------------------------------
    // post
    // -----------------------------------------------------------
    //
    // A form POST of the fields to the path, url-encoded the
    // way a browser sends a form.
    //
    // Used by:
    //   - ApacheTestCase::signInOverHttp (tests/)
    //   - ApacheDefectsTest, ProtectionTest, SessionTest
    //     (Apache/)
    // -----------------------------------------------------------

    public function post(string $path, array $form, array $headers = []): ResponseInterface
    {
        return $this->request('POST', $path, ['headers' => $headers, 'form_params' => $form]);
    }






    // -----------------------------------------------------------
    // request
    // -----------------------------------------------------------
    //
    // Any request, with Guzzle's request options: the proxy
    // headers and the stored cookies go out with it, and the
    // cookies it sets come back into the store — an expired
    // one leaves the store, the way a browser deletes it.
    //
    // Used by:
    //   - get, post (above)
    // -----------------------------------------------------------

    private function request(string $method, string $path, array $options = []): ResponseInterface
    {
        $headers = array_merge(self::PROXY_HEADERS, $options['headers'] ?? []);
        if ($this->cookies) {
            $pairs = [];
            foreach ($this->cookies as $cookie) {
                $pairs[] = $cookie->getName() . '=' . $cookie->getValue();
            }
            $headers['Cookie'] = implode('; ', $pairs);
        }
        $options['headers'] = $headers;

        $response = $this->client->request($method, $path, $options);
        foreach ($response->getHeader('Set-Cookie') as $line) {
            $cookie = SetCookie::fromString($line);
            if ($cookie->isExpired()) {
                unset($this->cookies[$cookie->getName()]);
            } else {
                $this->cookies[$cookie->getName()] = $cookie;
            }
        }
        return $response;
    }






    // -----------------------------------------------------------
    // csrfToken
    // -----------------------------------------------------------
    //
    // Opens the page and returns the CSRF token of its form —
    // the hidden _token field Laravel's form builder writes —
    // or an empty string when the page has none. Opening it
    // also starts the session the token belongs to.
    //
    // Used by:
    //   - ApacheTestCase::signInOverHttp (tests/)
    //   - ApacheDefectsTest (Apache/)
    // -----------------------------------------------------------

    public function csrfToken(string $path): string
    {
        $html = (string) $this->get($path)->getBody();
        if (preg_match('/<input[^>]*name="_token"[^>]*>/', $html, $input)
                && preg_match('/value="([^"]*)"/', $input[0], $value)) {
            return $value[1];
        }
        return '';
    }






    // -----------------------------------------------------------
    // cookie
    // -----------------------------------------------------------
    //
    // The cookie of that name as Apache last set it, with its
    // attributes, or null when Apache has not set it.
    //
    // Used by:
    //   - SessionTest, ApacheDefectsTest (Apache/)
    // -----------------------------------------------------------

    public function cookie(string $name): ?SetCookie
    {
        return $this->cookies[$name] ?? null;
    }
}
