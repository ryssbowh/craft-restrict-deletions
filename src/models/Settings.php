<?php

namespace Ryssbowh\RestrictDeletion\models;

use Ryssbowh\RestrictDeletion\services\Restrict;
use craft\base\Model;

class Settings extends Model
{   
    /**
     * @var array
     */
    public $policies = [];

    /**
     * @var string
     */
    public $userPolicy = Restrict::POLICY_NONE;

    /**
     * @var boolean
     */
    public $adminCanOverride = false;

    /**
     * @var boolean
     */
    public $disableForFrontRequests = false;

    /**
     * @var boolean
     */
    public $disableForConsoleRequests = false;

    /**
     * Get the policy for a uid (section, category group, volume or 'users')
     * 
     * @param  string $uid
     * @return string
     */
    public function getPolicy(string $uid): string
    {
        if ($uid == 'users') {
            return $this->userPolicy;
        }
        return $this->policies[$uid] ?? Restrict::POLICY_NONE;
    }
}