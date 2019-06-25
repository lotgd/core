<?php
declare(strict_types=1);

namespace LotGD\Core\Exceptions;

/**
 * Exception if a requested permission id has not been found.
 */
class PermissionIdNotFoundException extends EntityDoesNotExistException
{
}
