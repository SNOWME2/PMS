<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Models\UserData;
use App\Rules\ValidatorParamsRule;
class CreateNewUser implements CreatesNewUsers
{
   
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    protected array $requiredString;
  
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required','string','max:100'],
            'first_name' => ['required', 'string', 'max:100',],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            
            'password' => $this->passwordRules(),
        ])->validate();

        UserData::Create([
            'first_name' => $input['']
        ]);
        return User::create([
            // 'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => 'tenant',
        ]);
    }
}
