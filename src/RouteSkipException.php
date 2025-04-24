<?php
declare(strict_types=1);
namespace Coroq\Router;

use RuntimeException;

/**
 * Thrown by a RouterInterface implementation when it inspected
 * the current way-points and decided they are not applicable.
 */
class RouteSkipException extends RuntimeException {
}
