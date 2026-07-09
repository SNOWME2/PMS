<?php

namespace App\Rules;

use Closure;

use Illuminate\Translation\PotentiallyTranslatedString;

class ValidatorParamsRule 
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public static function name(): array
    {
        return [
            'required',
            'string',
            'max:255',
        ];
    }

    public static function optionalName(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
        ];
    }
}
