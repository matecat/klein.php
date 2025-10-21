<?php
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author          Chris O'Hara <cohara87@gmail.com>
 * @author          Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @author          Domenico Lupinetti (Ostico <ostico@gmail.com>) (contributor and v3 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link            https://github.com/klein/klein.php
 * @license         MIT
 */

namespace Klein\DataCollection;

/**
 * HeaderDataCollection
 *
 * A DataCollection for HTTP headers
 */
class HeaderDataCollection extends DataCollection
{

    /**
     * The list of Content related HTTP headers
     *
     * @var string[]
     */
    protected static array $http_content_headers = [
        'CONTENT_LENGTH',
        'CONTENT_TYPE',
        'CONTENT_MD5',
    ];

    public function __construct(array $paramHeaders = [])
    {
        $headers = [];
        // Iterate over server variables to reconstruct headers manually
        foreach ($paramHeaders ?: $_SERVER as $name => $value) {
            // Only consider HTTP_*-prefixed entries, which represent incoming headers
            $isHttpPrefixed = str_starts_with($name, 'HTTP_');
            $isContentHeader = in_array($name, self::$http_content_headers, true);

            if (!$isHttpPrefixed && !$isContentHeader) {
                continue;
            }

            // Normalize a header key: "HTTP_FOO_BAR" or "CONTENT_TYPE" => "Foo-Bar" / "Content-Type"
            // 1) possibly strip "HTTP_"
            // 2) replace underscores with spaces
            // 3) lowercase then ucwords to title-case words
            // 4) replace spaces with hyphens
            $normalizedKey = str_replace(
                ' ',
                '-',
                ucwords(strtolower(str_replace('_', ' ', $isHttpPrefixed ? substr($name, 5) : $name)))
            );

            $headers[$normalizedKey] = $value;
        }

        parent::__construct($headers);
    }
}
