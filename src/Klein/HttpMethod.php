<?php
/**
 *
 * @author Domenico Lupinetti (Ostico) domenico@translated.net / ostico@gmail.com
 * Date: 15/10/25
 * Time: 16:03
 *
 */

namespace Klein;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case PATCH = 'PATCH';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

}