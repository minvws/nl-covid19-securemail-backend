<?php
namespace App\Models\Enums;
/**
 * OTP type
 *
 * *** WARNING ***
 * This code is auto-generated. To change the items of this enum. Please edit OTPType.json!
 *
 * @method static OTPType sms() sms() Sms

 * @property-read string $value
*/
final class OTPType extends Enum
{

    /**
     * @inheritDoc
     */
    protected static function enumSchema(): object
    {
        return (object) array(
           'phpClass' => 'OTPType',
           'tsConst' => 'OTPType',
           'description' => 'OTP type',
           'items' => 
          array (
            0 => 
            (object) array(
               'label' => 'Sms',
               'value' => 'sms',
               'name' => 'sms',
            ),
          ),
        );
    }
}
