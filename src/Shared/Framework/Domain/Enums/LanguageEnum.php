<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Enums;

enum LanguageEnum: string
{
    use TryFromOrNullTrait;

    case spanish = 'spanish';
    case english = 'english';
    case french = 'french';
    case german = 'german';
    case italian = 'italian';
    case euskera = 'euskera';
    case catalan = 'catalan';
    case japanese = 'japanese';
    case portuguese = 'portuguese';
    case czech = 'czech';
    case galician = 'galician';
    case chinese = 'chinese';
    case polish = 'polish';

}
