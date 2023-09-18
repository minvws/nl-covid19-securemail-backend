<?php
namespace App\Models\Enums;
/**
 * Login type
 *
 * *** WARNING ***
 * This code is auto-generated. To change the items of this enum. Please edit LoginType.json!
 *
 * @method static LoginType digid() digid() DigiD
 * @method static LoginType sms() sms() Sms

 * @property-read string $value
*/
final class LoginType extends Enum
{

    /**
     * @inheritDoc
     */
    protected static function enumSchema(): object
    {
        return (object) array(
           'phpClass' => 'LoginType',
           'tsConst' => 'loginType',
           'description' => 'Login type',
           'items' => 
          array (
            0 => 
            (object) array(
               'label' => 'DigiD',
               'value' => 'digid',
               'name' => 'digid',
            ),
            1 => 
            (object) array(
               'label' => 'Sms',
               'value' => 'sms',
               'name' => 'sms',
            ),
          ),
        );
    }
}
