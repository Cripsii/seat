<?php namespace App\Services\Validators;
 
class SeatUserPasswordValidator extends Validator {
 
    public static $rules = array(
        'oldPassword' => 'required|min:6',
        'newPassword'  => 'required|min:6|confirmed',
        'newPassword_confirmation' => 'required|min:6'
    );
 
}