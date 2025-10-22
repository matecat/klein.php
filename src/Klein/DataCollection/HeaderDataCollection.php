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
    public function __construct(array $paramHeaders = [])
    {
        $headers = $paramHeaders ?: $_SERVER;
        $headers = array_change_key_case($headers);

        // Normalize a header key: "HTTP_FOO_BAR" or "CONTENT_TYPE" => "Foo-Bar" / "Content-Type"
        // 1) possibly strip "HTTP_"
        // 2) replace underscores with hyphens
        // 3) title-case words
        foreach ($headers as $key => $value) {
            unset($headers[$key]);

            $strArr = explode("_", $key);

            if ($strArr[0] == "http") {
                array_shift($strArr);
            } elseif ($strArr[0] !== 'content') {
                continue;
            }

            foreach ($strArr as &$str) {
                $str = ucfirst($str);
            }

            $headers[implode("-", $strArr)] = $value;
        }

        parent::__construct($headers);
    }
}
