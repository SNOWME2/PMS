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

    //String Validation Rules
    //Does required data
    public static function requiredString(): array
    {
        return [
            'required',
            'string',
            
        ];
    }
    //Does not required data
    public static function nullableString(): array
    {
        return [
            'nullable',
            'string',
        
        ];
    }
    //Image Validation Rules
    //Does not required data
    public static function nullableImage(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg,gif,svg',
            
        ];
    }
    //Does required data
    public static function requiredImage(): array
    {
        return [
            'required',
            'image',
            'mimes:jpeg,png,jpg,gif,svg',

        ];
    }

    //Date Validation Rules
    //A date before today (e.g. yesterday, last week, last month, etc.)
    public static function dateBeforeToday(): array
    {
        return [
            'nullable',
            'date',
            'before:today',
        ];
    }
    //A date after today (e.g. tomorrow,next week, next month, etc.)
    public static function dateAfterToday(): array
    {
        return [
            'nullable',
            'date',
            'after:today',
        ];
    }
    //A date after or equal to today (e.g. today, tomorrow,next week, next month, etc.)
    public static function dateAfterOrEqualToday(): array
    {
        return [
            'nullable',
            'date',
            'after_or_equal:today',
        ];
    }
}
